<?php

declare(strict_types=1);

namespace Akeneo\Category\Infrastructure\Storage\Save\Query;

use Akeneo\Category\Application\Query\IsTemplateDeactivated;
use Akeneo\Category\Application\Storage\Save\Query\UpsertCategoryBase;
use Akeneo\Category\Domain\Model\Enrichment\Category;
use Akeneo\Category\Domain\Query\GetCategoryInterface;
use Akeneo\Category\Domain\ValueObject\ValueCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;

/**
 * Save values from model into pim_catalog_category table:
 * The values are inserted if the id is new, they are updated if the id already exists.
 *
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class UpsertCategoryBaseSql implements UpsertCategoryBase
{
    public function __construct(
        private readonly Connection $connection,
        private readonly GetCategoryInterface $getCategory,
        private readonly IsTemplateDeactivated $isTemplateDeactivated,
    ) {
    }

    /**
     * @throws Exception
     */
    public function execute(Category $categoryModel): void
    {
        if ($this->getCategory->byCode((string) $categoryModel->getCode())) {
            $this->updateEnrichedCategory($categoryModel);
        } else {
            $this->insertCategory($categoryModel);
        }
    }

    /**
     * @throws Exception
     */
    private function insertCategory(Category $categoryModel): void
    {
        $query = <<< SQL
            INSERT INTO pim_catalog_category
                (parent_id, code, created, root, lvl, lft, rgt, value_collection)
            VALUES
                (:parent_id, :code, NOW(), :root, :lvl, :lft, :rgt, :value_collection)
            ;
        SQL;

        $this->connection->executeQuery(
            $query,
            [
                'parent_id' => $categoryModel->getParentId()?->getValue(),
                'code' => (string) $categoryModel->getCode(),
                'root' => 0,
                'lvl' => 0,
                'lft' => 1,
                'rgt' => 2,
                'value_collection' => $this->normalizeValueCollection(
                    $categoryModel->getAttributeCodes(),
                    $categoryModel->getAttributes(),
                ),
            ],
            [
                'parent_id' => \PDO::PARAM_INT,
                'code' => \PDO::PARAM_STR,
                'root' => \PDO::PARAM_INT,
                'lvl' => \PDO::PARAM_INT,
                'lft' => \PDO::PARAM_INT,
                'rgt' => \PDO::PARAM_INT,
                'value_collection' => Types::JSON,
            ],
        );

        // We cannot access newly auto incremented id during the insert query. We have to update root in a second query
        $newCategoryId = $this->connection->lastInsertId();
        $this->connection->executeQuery(
            <<< SQL
                UPDATE pim_catalog_category
                SET root=:root
                WHERE code=:category_code
            SQL,
            [
                'category_code' => (string) $categoryModel->getCode(),
                'root' => $newCategoryId,
            ],
            [
                'category_code' => \PDO::PARAM_STR,
                'root' => \PDO::PARAM_INT,
            ],
        );
    }

    /**
     * @throws Exception
     */
    private function updateEnrichedCategory(Category $categoryModel): void
    {
        $templateUuid = $categoryModel->getTemplateUuid();
        if ($templateUuid && ($this->isTemplateDeactivated)($templateUuid)) {
            return;
        }

        $query = <<<SQL
            UPDATE pim_catalog_category
            SET
                created = pim_catalog_category.created,
                updated = NOW(),
                value_collection = :value_collection
            WHERE code = :category_code;
        SQL;

        $this->connection->executeQuery(
            $query,
            [
                'category_code' => (string) $categoryModel->getCode(),
                'value_collection' => $this->normalizeValueCollection(
                    $categoryModel->getAttributeCodes(),
                    $categoryModel->getAttributes(),
                ),
            ],
            [
                'category_code' => \PDO::PARAM_STR,
                'value_collection' => Types::JSON,
            ],
        );
    }

    private function normalizeValueCollection(array $attributeCodes, ?ValueCollection $valueCollection): ?array
    {
        if (null === $valueCollection) {
            return null;
        }

        $attributeValues = array_filter(
            $valueCollection->normalize(),
            fn (array $attributeValue) => null !== $attributeValue['data'],
        );

        $attributeValues['attribute_codes'] = $attributeCodes;

        return $attributeValues;
    }
}
