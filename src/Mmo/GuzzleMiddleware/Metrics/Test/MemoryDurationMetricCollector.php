<?php

namespace Mmo\GuzzleMiddleware\Metrics\Test;

use Mmo\GuzzleMiddleware\Metrics\Duration\DurationMetricCollectorInterface;
use Mmo\GuzzleMiddleware\Metrics\Duration\DurationMetricLabelsDto;

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
