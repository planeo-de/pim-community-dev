<?php

declare(strict_types=1);

namespace Akeneo\Pim\Platform\Messaging\Infrastructure\Symfony\DependencyInjection;

use Akeneo\Pim\Automation\DataQualityInsights\Domain\Message\LaunchProductAndProductModelEvaluationsMessage;
use Akeneo\Pim\Automation\DataQualityInsights\Infrastructure\Handler\LaunchProductAndProductModelEvaluationsHandler;
use Akeneo\Pim\Platform\Messaging\Infrastructure\Handler\PimMessageHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class AkeneoMessagingExtension  extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $pimHandler = $container->getDefinition(PimMessageHandler::class);
        $pimHandler->addMethodCall('registerHandler', [
            LaunchProductAndProductModelEvaluationsMessage::class,
            new Reference(LaunchProductAndProductModelEvaluationsHandler::class),
        ]);

    }
}
