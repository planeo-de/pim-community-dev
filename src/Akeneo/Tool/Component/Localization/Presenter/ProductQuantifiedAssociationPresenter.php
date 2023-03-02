<?php

declare(strict_types=1);

namespace Akeneo\Tool\Component\Localization\Presenter;

use Akeneo\Pim\Enrichment\Component\Product\Query\FindIdentifier;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductQuantifiedAssociationPresenter implements PresenterInterface
{
    public function __construct(private readonly FindIdentifier $findIdentifier)
    {
    }

    /**
     * @param string $value
     * @param array $options
     * @return string
     */
    public function present($value, array $options = []): string
    {
        Assert::string($value);
        if (empty($value)) {
            return $value;
        }
        $values = explode(',', $value);
        $formattedValues = [];
        $validUuids = \array_filter(\array_map(fn (string $uuid) => Uuid::isValid($uuid) ? $uuid : null, $values));
        $identifiersFromUuids = $this->findIdentifier->fromUuids($validUuids);
        foreach ($values as $key) {
            if (isset($identifiersFromUuids[$key])) {
                $formattedValues[] = $identifiersFromUuids[$key];
            } else {
                $formattedValues[] = in_array($key, $validUuids) ? sprintf('[%s]', $key) : $key;
            }
        }

        return implode(',', $formattedValues);
    }

    public function supports($attributeType): bool
    {
        return 1 === \preg_match('/(.*)-products$/', $attributeType);
    }
}
