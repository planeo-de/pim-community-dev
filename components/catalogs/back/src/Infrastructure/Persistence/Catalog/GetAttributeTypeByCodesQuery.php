<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Infrastructure\Persistence\Catalog;

use Akeneo\Catalogs\Application\Persistence\Catalog\GetAttributeTypeByCodesQueryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class GetAttributeTypeByCodesQuery implements GetAttributeTypeByCodesQueryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function execute(array $codes): array
    {
        $query = <<<SQL
        SELECT
            code,
            attribute_type
        FROM pim_catalog_attribute
        WHERE code in (:codes)
        SQL;

        /** @var array<string, string> $attributeTypeByCodes */
        $attributeTypeByCodes = $this->connection->executeQuery(
            $query,
            ['codes' => $codes],
            ['codes' => Connection::PARAM_STR_ARRAY],
        )->fetchAllKeyValue();
        return $attributeTypeByCodes;
    }
}
