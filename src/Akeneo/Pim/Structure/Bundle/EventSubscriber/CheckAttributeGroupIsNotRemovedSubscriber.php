<?php

declare(strict_types=1);

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2022 Akeneo SAS (https://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Akeneo\Pim\Structure\Bundle\EventSubscriber;

use Akeneo\Pim\Structure\Component\Model\AttributeGroupInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Tool\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\Tool\Component\StorageUtils\StorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckAttributeGroupIsNotRemovedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            StorageEvents::PRE_REMOVE => 'preRemove',
        ];
    }

    public function preRemove(RemoveEvent $event): void
    {
        $attributeGroup = $event->getSubject();
        if (!$attributeGroup instanceof AttributeGroupInterface) {
            return;
        }

        if ('other' === $attributeGroup->getCode()) {
            return new JsonResponse(
                [
                    'message' => 'Attribute group "other" cannot be removed.',
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
