<?php

declare(strict_types=1);

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Akeneo\Pim\Structure\Bundle\Infrastructure\Job\AttributeGroup;

use Akeneo\Pim\Structure\Component\Model\Attribute;
use Akeneo\Pim\Structure\Component\Model\AttributeGroup;
use Akeneo\Pim\Structure\Component\Repository\AttributeGroupRepositoryInterface;
use Akeneo\Tool\Component\Batch\Item\DataInvalidItem;
use Akeneo\Tool\Component\Batch\Item\TrackableTaskletInterface;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\Connector\Step\TaskletInterface;
use Akeneo\Tool\Component\StorageUtils\Cache\EntityManagerClearerInterface;
use Akeneo\Tool\Component\StorageUtils\Remover\BulkRemoverInterface;

final class DeleteAttributeGroupsTasklet implements TaskletInterface, TrackableTaskletInterface
{
    private ?StepExecution $stepExecution = null;

    public function __construct(
        private readonly AttributeGroupRepositoryInterface $attributeGroupRepository,
        private readonly BulkRemoverInterface $remover,
        private readonly EntityManagerClearerInterface $cacheClearer,
        private readonly int $batchSize = 100,
    ) {
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    public function execute()
    {
        if (null === $this->stepExecution) {
            throw new \InvalidArgumentException(sprintf('In order to execute "%s" you need to set a step execution.', DeleteAttributeGroupsTasklet::class));
        }

        $attributeGroupsToDelete = $this->getAttributeGroupsToDelete();

        $this->stepExecution->setTotalItems(count($attributeGroupsToDelete));
        $this->stepExecution->addSummaryInfo('deleted_attribute_groups', 0);

        foreach ($attributeGroupsToDelete as $key => $attributeGroup) {
            if ($attributeGroup->getAttributes()->count() > 0) {
                $this->stepExecution->addWarning('The attribute group "code" cannot be deleted because it contains attributes.', ['code' => $attributeGroup->getCode()], new DataInvalidItem($attributeGroup));
                unset($attributeGroupsToDelete[$key]);
            }
        }

        foreach (array_chunk(array_values($attributeGroupsToDelete), $this->batchSize) as $batchAttributeGroups) {
            $this->delete($batchAttributeGroups);
        }
    }

    /**
     * @return Attribute[]
     */
    private function getAttributeGroupsToDelete(): array
    {
        $filters = $this->stepExecution->getJobParameters()->get('filters');

        return $this->attributeGroupRepository->findBy(['code' => $filters['codes']]);
    }

    /**
     * @param AttributeGroup[] $attributeGroups
     */
    private function delete(array $attributeGroups): void
    {
        $this->remover->removeAll($attributeGroups);

        $this->stepExecution->incrementSummaryInfo('deleted_attribute_groups', count($attributeGroups));
        $this->stepExecution->incrementProcessedItems(count($attributeGroups));

        $this->cacheClearer->clear();
    }

    public function isTrackable(): bool
    {
        return true;
    }
}
