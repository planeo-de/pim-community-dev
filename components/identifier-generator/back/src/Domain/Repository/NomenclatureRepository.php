<?php

declare(strict_types=1);

namespace Akeneo\Pim\Automation\IdentifierGenerator\Domain\Repository;

use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\NomenclatureDefinition;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface NomenclatureRepository
{
    public function get(string $propertyCode): ?NomenclatureDefinition;

    public function update(string $propertyCode, NomenclatureDefinition $nomenclatureDefinition): void;
}
