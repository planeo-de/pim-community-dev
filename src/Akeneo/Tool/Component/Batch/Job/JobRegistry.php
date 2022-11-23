<?php

namespace Akeneo\Tool\Component\Batch\Job;

use Akeneo\Platform\Bundle\FeatureFlagBundle\FeatureFlags;

/**
 * A runtime service registry for registering job by name.
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobRegistry
{
    /** @var JobInterface[] */
    protected array $jobs = [];

    /**
     * @param FeatureFlags $featureFlags
     */
    public function __construct(private FeatureFlags $featureFlags)
    {
    }

    /**
     * @throws DuplicatedJobException
     */
    public function register(\Closure $jobLoader, string $jobName, string $jobType, string $connector, ?string $feature = null)
    {
        if (isset($this->jobs[$jobName])) {
            throw new DuplicatedJobException(sprintf('The job "%s" is already registered', $jobName));
        }

        $this->jobs[$jobName] = [
            'job_loader' => $jobLoader,
            'type' => $jobType,
            'connector' => $connector,
            'feature' => $feature,
        ];
    }

    /**
     * @param string $jobName
     *
     * @throws UndefinedJobException
     *
     * @return JobInterface
     */
    public function get(string $jobName): JobInterface
    {
        if (!isset($this->jobs[$jobName])) {
            throw new UndefinedJobException(sprintf('The job "%s" is not registered', $jobName));
        }

        return $this->jobs[$jobName]['job_loader']();
    }

    /**
     *
     * @throws UndefinedJobException
     */
    public function isEnabled(string $jobName): bool
    {
        if (!isset($this->jobs[$jobName])) {
            throw new UndefinedJobException(sprintf('The job "%s" is not registered', $jobName));
        }

        $feature = $this->jobs[$jobName]['feature'];

        return null === $feature || $this->featureFlags->isEnabled($feature);
    }

    public function has(string $jobName): bool
    {
        return isset($this->jobs[$jobName]);
    }

    /**
     * @return JobInterface[]
     */
    public function all(): array
    {
        return array_map(static fn (array $job) => $job['job_loader'](), $this->getAllEnabledJobs());
    }

    /**
     * @throws UndefinedJobException
     *
     * @return JobInterface[]
     */
    public function allByType(string $jobType): array
    {
        $jobs = array_filter(
            $this->getAllEnabledJobs(),
            function ($job) use ($jobType) {
                return $job['type'] === $jobType;
            }
        );

        if (empty($jobs)) {
            throw new UndefinedJobException(
                sprintf('There is no registered job with the type "%s"', $jobType)
            );
        }

        return array_map(static fn (array $job) => $job['job_loader'](), $jobs);
    }

    /**
     * @throws UndefinedJobException
     *
     * @return JobInterface[][]
     */
    public function allByTypeGroupByConnector(string $jobType): array
    {
        $jobs = array_filter($this->getAllEnabledJobs(), fn (array $job) => $job['type'] === $jobType);

        if (empty($jobs)) {
            throw new UndefinedJobException(
                sprintf('There is no registered job with the type "%s"', $jobType)
            );
        }

        return array_reduce(
            $jobs,
            function ($groupedJobs, $job) {
                $job = $job['job_loader']();
                $groupedJobs[$job['connector']][$job->getName()] = $job;

                return $groupedJobs;
            },
            []
        );
    }

    /**
     * @return string[]
     */
    public function getConnectors(): array
    {
        return array_unique(array_map(static fn (array $job) => $job['connector'], $this->getAllEnabledJobs()));
    }

    private function getAllEnabledJobs(): array
    {
        return array_filter(
            $this->jobs,
            fn (array $job) => null === $job['feature'] || $this->featureFlags->isEnabled($job['feature'])
        );
    }
}
