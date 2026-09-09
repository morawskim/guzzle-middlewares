<?php

namespace Mmo\GuzzleMetricsMiddleware\Test;

use Mmo\GuzzleMetricsMiddleware\Metric\Duration\DurationMetricCollectorInterface;
use Mmo\GuzzleMetricsMiddleware\Metric\Duration\DurationMetricLabelsDto;

class MemoryDurationMetricCollector implements DurationMetricCollectorInterface
{
    /**
     * @var array{float, DurationMetricLabelsDto}
     */
    public array $metrics = [];

    public function collect(float $duration, DurationMetricLabelsDto $dto): void
    {
        $this->metrics[] = [$duration, $dto];
    }
}
