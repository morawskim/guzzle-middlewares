<?php

namespace Mmo\GuzzleMiddleware\Metrics\Duration;

interface DurationMetricCollectorInterface
{
    public function collect(float $duration, DurationMetricLabelsDto $dto): void;
}
