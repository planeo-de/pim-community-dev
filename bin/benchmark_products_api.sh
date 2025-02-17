#!/usr/bin/env bash

set -eu

command -v jq >/dev/null 2>&1 || { echo >&2 "I require jq but it's not installed.  Aborting."; exit 1; }

START=$(date +%s)
SCRIPT_DIR=$(dirname $0)
DOCKER_BRIDGE_IP=$(ip address show | grep "global docker" | cut -c10- | cut -d '/' -f1)
WORKING_DIRECTORY="$SCRIPT_DIR/../var/benchmarks"

PIM_PATH="$SCRIPT_DIR/.."
CONFIG_PATH="$PIM_PATH/tests/benchmarks/"

if [ $# -eq 0 ]; then
    REFERENCE_CATALOG_FILE="$CONFIG_PATH/product_api_catalog.yml"
else
    if [ ! -f "$CONFIG_PATH/$1" ]; then
        echo >&2 "The file does not exist"; exit 1;
    fi;
    REFERENCE_CATALOG_FILE="$CONFIG_PATH/$1"
fi;

message()
{
    echo ""
    echo "[$(date +"%H:%M:%S")] ========== $1 =========="
    echo ""
}

boot_and_install_pim()
{
    message "Boot and install PIM in test environment"
    cd $PIM_PATH
    export ES_JAVA_OPTS='-Xms2g -Xmx2g'
    docker-compose up -d --remove-orphans
    PUBLIC_PIM_HTTP_PORT=$(docker-compose port httpd-behat 80 | cut -d ':' -f 2)
    rm -rf var/cache/*
    bin/docker/pim-setup.sh
    docker-compose exec -T fpm bin/console cache:warmup -e behat
    docker-compose exec -T fpm bin/console doctrine:database:drop --force
    docker-compose exec -T fpm bin/console doctrine:database:create --if-not-exists
    docker-compose exec -T fpm bin/console pim:installer:db -e behat
    CREDENTIALS=$(docker-compose exec -T fpm bin/console pim:oauth-server:create-client --no-ansi -e behat generator | tr -d '\r ')
    export API_CLIENT=$(echo $CREDENTIALS | cut -d " " -f 2 | cut -d ":" -f 2)
    export API_SECRET=$(echo $CREDENTIALS | cut -d " " -f 3 | cut -d ":" -f 2)
    export API_URL="http://$DOCKER_BRIDGE_IP:$PUBLIC_PIM_HTTP_PORT"
    export API_USER="admin"
    export API_PASSWORD="admin"

    docker pull akeneo/data-generator:2.0
    docker pull akeneo/benchmark-api:3.0
}

generate_reference_catalog()
{
    message "Generate the catalog"
    ABSOLUTE_CATALOG_FILE=$(realpath $REFERENCE_CATALOG_FILE)

    docker run \
        -t \
        -e API_CLIENT -e API_SECRET -e API_URL -e API_USER -e API_PASSWORD \
        -v "$ABSOLUTE_CATALOG_FILE:/app/akeneo-data-generator/app/catalog/product_api_catalog.yml" \
        akeneo/data-generator:2.0 akeneo:api:generate-catalog --with-products --check-minimal-install product_api_catalog.yml
}

launch_bench()
{
    docker run -t -e API_CLIENT -e API_SECRET -e API_URL -e API_USER -e API_PASSWORD akeneo/benchmark-api:3.0 akeneo:api:launch-benchmarks -i 10 -b $1 -vv > "$WORKING_DIRECTORY/raw_results/$1.txt"

    echo $(cat "$WORKING_DIRECTORY/raw_results/$1.txt" | grep 'Mean speed' | cut -d '"' -f 2)
}

mkdir -p "$WORKING_DIRECTORY/raw_results"

boot_and_install_pim
generate_reference_catalog

cd $PIM_PATH
PRODUCT_SIZE=$(docker-compose exec -T mysql mysql -uakeneo_pim -pakeneo_pim akeneo_pim -N -s -e "SELECT AVG(JSON_LENGTH(JSON_EXTRACT(raw_values, '$.*.*.*'))) avg_product_values FROM pim_catalog_product;" | tail -n 1 | tr -d '\r \n')
PRODUCT_COUNT=$(docker-compose exec -T mysql mysql -uakeneo_pim -pakeneo_pim akeneo_pim -N -s -e "SELECT COUNT(*) FROM pim_catalog_product;" | tail -n 1 | tr -d '\r \n')
message "Start bench products with 120 attributes"

sleep 10

GET=$(launch_bench "get_many_products")
CREATE=$(launch_bench "create_many_products")
UPDATE=$(launch_bench "update_many_products")

echo "Currently with $PRODUCT_COUNT products of $PRODUCT_SIZE values in average, the api speeds are:"
echo "- GET: $GET products/second"
echo "- CREATE: $CREATE products/second"
echo "- UPDATE: $UPDATE products/second"
echo ""
echo "You can see the detailed results under the folder $WORKING_DIRECTORY/raw_results and the JSON at the name of results.json"
echo ""

JSON_STRING=$(
    jq -n \
    --arg gp $GET \
    --arg cp $CREATE \
    --arg up $UPDATE \
    '{get_result: $gp, create_result: $cp, update_result: $up}'
)

echo $JSON_STRING > $WORKING_DIRECTORY/results.json

END=$(date +%s)
RUNTIME=$(expr $END - $START)
echo "The bench is finished, it took $RUNTIME seconds to run"
