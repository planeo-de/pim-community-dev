<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Application\Persistence\ProductMappingSchema;

use Akeneo\Catalogs\Application\Exception\ProductMappingSchemaNotFoundException;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @phpstan-type ProductMappingSchema array{
 *      properties: array<array-key, mixed>
 * }
 */
interface GetProductMappingSchemaQueryInterface
{
    /**
     * @return ProductMappingSchema
     *
     * @throws ProductMappingSchemaNotFoundException
     */
    public function execute(string $catalogId): array;
}
