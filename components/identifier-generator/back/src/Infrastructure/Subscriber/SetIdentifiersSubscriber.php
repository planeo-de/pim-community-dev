<?php

declare(strict_types=1);

namespace Akeneo\Pim\Automation\IdentifierGenerator\Infrastructure\Subscriber;

use Akeneo\Pim\Automation\IdentifierGenerator\Application\Exception\UnableToSetIdentifierException;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\Generate\GenerateIdentifierCommand;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\Generate\GenerateIdentifierHandler;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\Validation\Error;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\Validation\ErrorList;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\IdentifierGenerator;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\ProductProjection;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Repository\IdentifierGeneratorRepository;
use Akeneo\Pim\Automation\IdentifierGenerator\Infrastructure\Event\UnableToSetIdentifierEvent;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Validator\Constraints\Product\UniqueProductEntity;
use Akeneo\Pim\Enrichment\Component\Product\Value\ScalarValue;
use Akeneo\Tool\Component\StorageUtils\StorageEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class SetIdentifiersSubscriber implements EventSubscriberInterface
{
    /** @var IdentifierGenerator[]|null */
    private ?array $identifierGenerators = null;

    public function __construct(
        private IdentifierGeneratorRepository $identifierGeneratorRepository,
        private GenerateIdentifierHandler $generateIdentifierCommandHandler,
        private ValidatorInterface $validator,
        private MetadataFactoryInterface $metadataFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            /**
             * This event has to be executed
             * - after AddDefaultValuesSubscriber (it adds default values from parent and may set values for the
             *   computation of the match or the generation)
             * - before ComputeEntityRawValuesSubscriber (it generates the raw_values)
             */
            StorageEvents::PRE_SAVE => ['setIdentifier', 90],
        ];
    }

    public function setIdentifier(GenericEvent $event): void
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductInterface) {
            return;
        }

        foreach ($this->getIdentifierGenerators() as $identifierGenerator) {
            $productProjection = new ProductProjection(
                $product->isEnabled(),
                $product->getFamily()?->getCode(),
                $this->flatValues($product),
            );
            if ($identifierGenerator->match($productProjection)) {
                try {
                    $this->setGeneratedIdentifier($identifierGenerator, $productProjection, $product);
                } catch (UnableToSetIdentifierException $e) {
                    $this->eventDispatcher->dispatch(new UnableToSetIdentifierEvent($e));
                }
            }
        }
    }

    private function setGeneratedIdentifier(
        IdentifierGenerator $identifierGenerator,
        ProductProjection $productProjection,
        ProductInterface $product
    ): void {
        $command = GenerateIdentifierCommand::fromIdentifierGenerator($identifierGenerator, $productProjection);
        $newIdentifier = ($this->generateIdentifierCommandHandler)($command);

        $value = ScalarValue::value($identifierGenerator->target()->asString(), $newIdentifier);
        Assert::isInstanceOf($value, ScalarValue::class);
        $product->addValue($value);
        $product->setIdentifier($newIdentifier);

        $violations = $this->updatePropertyPath(
            $this->validator->validate($product, $this->getProductConstraints($product)),
            'identifier'
        );

        $violations->addAll($this->updatePropertyPath(
            $this->validator->validate($value, $this->getValueConstraints($value)),
            $identifierGenerator->target()->asString()
        ));

        if (count($violations) > 0) {
            $product->removeValue($value);
            $product->setIdentifier(null);

            throw new UnableToSetIdentifierException(
                $newIdentifier,
                $identifierGenerator->target()->asString(),
                new ErrorList(\array_map(
                    fn (ConstraintViolationInterface $violation): Error => new Error(
                        (string) $violation->getMessage(),
                        $violation->getParameters(),
                        $violation->getPropertyPath()
                    ),
                    \iterator_to_array($violations)
                ))
            );
        }
    }

    /**
     * @return IdentifierGenerator[]
     */
    private function getIdentifierGenerators(): array
    {
        if (null === $this->identifierGenerators) {
            $this->identifierGenerators = $this->identifierGeneratorRepository->getAll();
        }

        return $this->identifierGenerators;
    }

    /**
     * Returns Symfony constraints defined here:
     * src/Akeneo/Pim/Enrichment/Bundle/Resources/config/validation/product.yml
     *
     * @return Constraint[]
     */
    private function getProductConstraints(ProductInterface $product): array
    {
        $metadata = $this->metadataFactory->getMetadataFor($product);
        Assert::isInstanceOf($metadata, ClassMetadataInterface::class);
        $propertiesMetadata = $metadata->getPropertyMetadata('identifier');
        $constraints = [new UniqueProductEntity()];
        foreach ($propertiesMetadata as $propertyMetadata) {
            $constraints = \array_merge($constraints, $propertyMetadata->getConstraints());
        }

        return $constraints;
    }

    /**
     * Returns user defined constraints (Regex, max length, etc).
     *
     * @return Constraint[]
     */
    private function getValueConstraints(ScalarValue $value): array
    {
        $metadata = $this->metadataFactory->getMetadataFor($value);
        Assert::isInstanceOf($metadata, ClassMetadataInterface::class);
        $membersMetadata = $metadata->getPropertyMetadata('data');
        $constraints = [];
        foreach ($membersMetadata as $memberMetadata) {
            $constraints = \array_merge($constraints, $memberMetadata->getConstraints());
        }

        return $constraints;
    }

    private function updatePropertyPath(
        ConstraintViolationListInterface $constraintViolationList,
        string $propertyPath
    ): ConstraintViolationListInterface {
        return new ConstraintViolationList(
            \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => new ConstraintViolation(
                    $constraintViolation->getMessage(),
                    $constraintViolation->getMessageTemplate(),
                    $constraintViolation->getParameters(),
                    $constraintViolation->getRoot(),
                    $propertyPath,
                    $constraintViolation->getInvalidValue(),
                    $constraintViolation->getPlural(),
                    $constraintViolation->getCode()
                ),
                \iterator_to_array($constraintViolationList)
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function flatValues(ProductInterface $product): array
    {
        return \array_map(
            static fn (ValueInterface $value) => $value->getData(),
            $product->getValues()->toArray()
        );
    }
}
