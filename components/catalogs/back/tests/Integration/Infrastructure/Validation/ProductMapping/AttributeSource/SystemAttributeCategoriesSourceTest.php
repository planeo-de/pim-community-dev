<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Test\Integration\Infrastructure\Validation\ProductMapping\AttributeSource;

use Akeneo\Catalogs\Infrastructure\Validation\ProductMapping\AttributeSource\SystemAttributeCategoriesSource;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @covers \Akeneo\Catalogs\Infrastructure\Validation\ProductMapping\AttributeSource\SystemAttributeCategoriesSource
 */
class SystemAttributeCategoriesSourceTest extends AbstractAttributeSourceTest
{
    private ?ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAttribute([
            'code' => 'categories',
            'type' => 'categories',
            'scopable' => false,
            'localizable' => true,
            'attribute_group_code' => 'system',
            'attribute_group_label' => 'System',
        ]);
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testItReturnsNoViolation(): void
    {
        $source = [
            'source' => 'categories',
            'scope' => null,
            'locale' => 'en_US',
        ];
        $violations = $this->validator->validate($source, new SystemAttributeCategoriesSource());

        $this->assertEmpty($violations);
    }

    /**
     * @dataProvider invalidDataProvider
     * @param array<string|class-string<\locale>, mixed> $source
     */
    public function testItReturnsViolationsWhenInvalid(
        array $source,
        string $expectedMessage,
    ): void {
        $violations = $this->validator->validate($source, new SystemAttributeCategoriesSource());

        $this->assertViolationsListContains($violations, $expectedMessage);
    }

    public function invalidDataProvider(): array
    {
        return [
            'missing source value' => [
                'source' => [
                    'scope' => null,
                    'locale' => 'en_US',
                ],
                'expectedMessage' => 'This field is missing.',
            ],
            'blank source value' => [
                'source' => [
                    'source' => '',
                    'scope' => null,
                    'locale' => 'en_US',
                ],
                'expectedMessage' => 'This value should not be blank.',
            ],
            'invalid source value' => [
                'source' => [
                    'source' => 42,
                    'scope' => null,
                    'locale' => 'en_US',
                ],
                'expectedMessage' => 'This value should be of type string.',
            ],
            'non null scope' => [
                'source' => [
                    'source' => 'categories',
                    'scope' => 'e-commerce',
                    'locale' => 'en_US',
                ],
                'expectedMessage' => 'This value should be null.',
            ],
            'missing scope field' => [
                'source' => [
                    'source' => 'categories',
                    'locale' => 'en_US',
                ],
                'expectedMessage' => 'This field is missing.',
            ],
            'missing locale' => [
                'source' => [
                    'source' => 'categories',
                    'scope' => null,
                ],
                'expectedMessage' => 'This field is missing.',
            ],
            'blank locale' => [
                'source' => [
                    'source' => 'categories',
                    'scope' => null,
                    'locale' => '',
                ],
                'expectedMessage' => 'This value should not be blank.',
            ],
            'invalid locale' => [
                'source' => [
                    'source' => 'categories',
                    'scope' => null,
                    'locale' => 'zz_ZZ',
                ],
                'expectedMessage' => 'This locale is disabled or does not exist anymore. Please check your channels and locales settings.',
            ],
            'disabled locale' => [
                'source' => [
                    'source' => 'categories',
                    'scope' => null,
                    'locale' => 'kz_KZ',
                ],
                'expectedMessage' => 'This locale is disabled or does not exist anymore. Please check your channels and locales settings.',
            ],
        ];
    }
}
