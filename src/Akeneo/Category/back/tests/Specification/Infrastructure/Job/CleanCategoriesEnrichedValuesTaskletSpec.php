<?php

declare(strict_types=1);

namespace Specification\Akeneo\Category\Infrastructure\Job;

use Akeneo\Category\Application\Enrichment\CleanCategoryDataLinkedToChannel;
use Akeneo\Category\Infrastructure\Job\CleanCategoriesEnrichedValuesTasklet;
use Akeneo\Tool\Component\Batch\Job\JobParameters;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\Connector\Step\TaskletInterface;
use PhpSpec\ObjectBehavior;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CleanCategoriesEnrichedValuesTaskletSpec extends ObjectBehavior
{
    function let(
        CleanCategoryDataLinkedToChannel $cleanCategoryDataLinkedToChannel,
    )
    {
        $this->beConstructedWith(
            $cleanCategoryDataLinkedToChannel,
        );
    }

    function it_is_initializable()
    {
        $this->shouldImplement(TaskletInterface::class);
        $this->shouldHaveType(CleanCategoriesEnrichedValuesTasklet::class);
    }

    function it_calls_cleaning_service_with_deleted_channel_code(
        StepExecution $stepExecution,
        JobParameters $jobParameters,
        CleanCategoryDataLinkedToChannel $cleanCategoryDataLinkedToChannel,
    ) {
        $stepExecution->getJobParameters()->willReturn($jobParameters);

        $deletedChannel = 'deleted_channel_code';
        $localesCodes = ['en_US'];
        $jobParameters->get('channel_code')->willReturn($deletedChannel);
        $jobParameters->get('locales_codes')->willReturn($localesCodes);
        $jobParameters->get('action')->willReturn(CleanCategoryDataLinkedToChannel::CLEAN_CHANNEL_ACTION);

        $cleanCategoryDataLinkedToChannel->__invoke($deletedChannel, $localesCodes,CleanCategoryDataLinkedToChannel::CLEAN_CHANNEL_ACTION)->shouldBeCalled();

        $this->setStepExecution($stepExecution);
        $this->execute();
    }
}
