<?php

declare(strict_types=1);

namespace Akeneo\Test\Pim\Automation\IdentifierGenerator\Acceptance\Context;

use Akeneo\Pim\Automation\IdentifierGenerator\Application\CreateGeneratorCommand;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\CreateGeneratorHandler;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\IdentifierGenerator;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\Property\FreeText;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Repository\IdentifierGeneratorRepository;
use Akeneo\Pim\Automation\IdentifierGenerator\Infrastructure\Exception\ViolationsException;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\Attribute;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Behat\Behat\Context\Context;
use Webmozart\Assert\Assert;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class CreateIdentifierGeneratorContext implements Context
{
    private ?ViolationsException $violations = null;

    public function __construct(
        private CreateGeneratorHandler $createGeneratorHandler,
        private IdentifierGeneratorRepository $generatorRepository,
        private SaverInterface $attributeRepository
    ) {
    }

    /**
     * @When I create an identifier generator
     */
    public function iCreateAnIdentifierGenerator(): void
    {
        ($this->createGeneratorHandler)(new CreateGeneratorCommand(
            '2038e1c9-68ff-4833-b06f-01e42d206002',
            'abcdef',
            [],
            [FreeText::fromString('abcdef')],
            ['fr' => 'Générateur'],
            'sku',
            '-'
        ));
    }

    /**
     * @Then The identifier generator is saved in the repository
     */
    public function identifierGeneratorIsSavedInTheRepository(): void
    {
        $identifierGenerator = $this->generatorRepository->get('abcdef');
        Assert::isInstanceOf($identifierGenerator, IdentifierGenerator::class);
    }

    /**
     * @Given the :attributeCode identifier attribute
     */
    public function theIdentifierAttribute(string $attributeCode): void
    {
        $identifierAttribute = new Attribute();
        $identifierAttribute->setType(AttributeTypes::IDENTIFIER);
        $identifierAttribute->setCode($attributeCode);
        $identifierAttribute->setScopable(false);
        $identifierAttribute->setLocalizable(false);
        $identifierAttribute->setBackendType(AttributeTypes::BACKEND_TYPE_TEXT);
        $this->attributeRepository->save($identifierAttribute);
    }

    /**
     * @When I try to create an identifier generator with not existing target :target
     */
    public function iTryToCreateAnIdentifierGeneratorWithNotExistingTarget(string $target): void
    {
        try {
            ($this->createGeneratorHandler)(new CreateGeneratorCommand(
                'abcdef',
                [],
                [FreeText::fromString('abcdef')],
                ['fr' => 'Générateur'],
                $target,
                '-'
            ));
        } catch (ViolationsException $exception) {
            $this->violations = $exception;
        }
    }

    /**
     * @Then I should get an error with message ':message'
     */
    public function iShouldGetAnErrorWithMessage(string $message): void
    {
        Assert::notNull($this->violations);
        Assert::contains($this->violations->violations()->__toString(), $message);
    }

    /**
     * @Then the identifier should not be created
     */
    public function theIdentifierShouldNotBeCreated(): void
    {
        Assert::null($this->generatorRepository->get('abcdef'));
    }

    /**
     * @Given the :attributeCode text attribute
     */
    public function theOtherTextAttribute(string $attributeCode): void
    {
        $identifierAttribute = new Attribute();
        $identifierAttribute->setType(AttributeTypes::TEXT);
        $identifierAttribute->setCode($attributeCode);
        $identifierAttribute->setScopable(false);
        $identifierAttribute->setLocalizable(false);
        $identifierAttribute->setBackendType(AttributeTypes::BACKEND_TYPE_TEXT);
        $this->attributeRepository->save($identifierAttribute);
    }

    /**
     * @When I try to create an identifier generator with target ':target'
     */
    public function iTryToCreateAnIdentifierGeneratorWithTarget(string $target): void
    {
        try {
            ($this->createGeneratorHandler)(new CreateGeneratorCommand(
                'abcdef',
                [],
                [FreeText::fromString('abcdef')],
                ['fr' => 'Générateur'],
                $target,
                '-'
            ));
        } catch (ViolationsException $exception) {
            $this->violations = $exception;
        }
    }
}
