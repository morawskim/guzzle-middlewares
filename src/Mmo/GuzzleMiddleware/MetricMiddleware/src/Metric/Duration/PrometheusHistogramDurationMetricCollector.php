<?php

namespace Mmo\GuzzleMetricsMiddleware\Metric\Duration;

use Prometheus\Histogram;

readonly class PrometheusHistogramDurationMetricCollector implements DurationMetricCollectorInterface
{
    public function __construct(private Histogram $histogram) {}

    public function collect(float $duration, DurationMetricLabelsDto $dto): void
    {
        $this->histogram->observe(
            $duration,
            [
                $dto->method,
                $dto->uri,
                $dto->statusCode,
            ],
        );
    }
}
