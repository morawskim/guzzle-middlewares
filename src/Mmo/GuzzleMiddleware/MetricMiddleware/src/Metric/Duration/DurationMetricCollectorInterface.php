<?php

namespace Mmo\GuzzleMetricsMiddleware\Metric\Duration;

interface DurationMetricCollectorInterface
{
    public function collect(float $duration, DurationMetricLabelsDto $dto): void;
}
