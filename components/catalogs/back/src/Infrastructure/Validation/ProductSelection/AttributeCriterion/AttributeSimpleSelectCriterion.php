<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Infrastructure\Validation\ProductSelection\AttributeCriterion;

use Akeneo\Catalogs\Domain\Operator;
use Akeneo\Catalogs\Infrastructure\Validation\ProductSelection\AttributeCriterionContainsValidAttributeOptions;
use Akeneo\Catalogs\Infrastructure\Validation\ProductSelection\AttributeCriterionContainsValidLocale;
use Akeneo\Catalogs\Infrastructure\Validation\ProductSelection\AttributeCriterionContainsValidScope;
use Akeneo\Catalogs\Infrastructure\Validation\ProductSelection\CriterionOperatorsRequireEmptyValue;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class AttributeSimpleSelectCriterion extends Compound
{
    /**
     * @param array<array-key, mixed> $options
     *
     * @return array<array-key, Constraint>
     */
    protected function getConstraints(array $options = []): array
    {
        return [
            new Assert\Sequentially([
                new Assert\Collection([
                    'fields' => [
                        'field' => [
                            new Assert\Type('string'),
                            new Assert\NotBlank(),
                        ],
                        'operator' => [
                            new Assert\Choice([
                                Operator::IN_LIST,
                                Operator::NOT_IN_LIST,
                                Operator::IS_EMPTY,
                                Operator::IS_NOT_EMPTY,
                            ]),
                        ],
                        'value' => [
                            new Assert\Type('array'),
                            new Assert\All([
                                new Assert\Type('string'),
                                new Assert\NotBlank(),
                            ]),
                        ],
                        'scope' => [
                            new Assert\Type('string'),
                        ],
                        'locale' => [
                            new Assert\Type('string'),
                        ],
                    ],
                    'allowMissingFields' => false,
                    'allowExtraFields' => false,
                ]),
                new CriterionOperatorsRequireEmptyValue([
                    Operator::IS_EMPTY,
                    Operator::IS_NOT_EMPTY,
                ]),
                new AttributeCriterionContainsValidScope(),
                new AttributeCriterionContainsValidLocale(),
                new AttributeCriterionContainsValidAttributeOptions(),
            ]),
        ];
    }
}
