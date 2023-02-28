<?php

declare(strict_types=1);

namespace Akeneo\Pim\Automation\DataQualityInsights\Infrastructure\Message;

use Akeneo\Tool\Component\Messenger\NormalizableMessageInterface;
use Akeneo\Tool\Component\Messenger\TraceableMessageInterface;
use Akeneo\Tool\Component\Messenger\TraceableMessageTrait;
use Webmozart\Assert\Assert;

/**
 * @TODO JEL-228
 *
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class LaunchProductAndProductModelEvaluationsMessage implements TraceableMessageInterface, NormalizableMessageInterface
{
    use TraceableMessageTrait;

    public function __construct(public readonly string $text)
    {
    }

    public function normalize(): array
    {
        return ['text' => $this->text];
    }

    public static function denormalize(array $normalized): NormalizableMessageInterface
    {
        Assert::keyExists($normalized, 'text');
        Assert::string($normalized['text']);

        return new LaunchProductAndProductModelEvaluationsMessage($normalized['text']);
    }
}
