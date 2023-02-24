<?php

declare(strict_types=1);

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Akeneo\Pim\Structure\Bundle\Infrastructure\Controller;

use Akeneo\Tool\Component\BatchQueue\Queue\PublishJobToQueueInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MassDeleteAttributeGroupsController
{
    public function __construct(
        private readonly PublishJobToQueueInterface $publishJobToQueue
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        $attributeGroupCodes = $request->get('codes');

        // TODO add code list validation

        $config = [
            'filters' => [
                'codes' => $attributeGroupCodes,
            ],
        ];

        $this->publishJobToQueue->publish('delete_attribute_groups', $config);

        return new Response(status: Response::HTTP_OK);
    }
}
