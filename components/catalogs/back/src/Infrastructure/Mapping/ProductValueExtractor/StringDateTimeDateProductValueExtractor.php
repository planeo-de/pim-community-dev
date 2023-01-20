<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Infrastructure\Mapping\ProductValueExtractor;

use Akeneo\Catalogs\Application\Mapping\ProductValueExtractorInterface;

/**
 * @copyright 2023 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class StringDateTimeDateProductValueExtractor implements ProductValueExtractorInterface
{
    public function extract(
        array $product,
        string $attributeCode,
        ?string $locale,
        ?string $scope,
        ?array $parameters,
    ): null | string {
        // @todo transform date in ISO 8601
        return $product['raw_values'][$attributeCode][$scope][$locale] ?? null;
    }

    public function supports(string $attributeType, string $targetType, ?string $targetFormat): bool
    {
        return 'pim_catalog_date' === $attributeType
            && 'string' === $targetType
            && 'date-time' === $targetFormat;
    }
}
