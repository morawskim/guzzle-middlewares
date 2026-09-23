<?php

namespace Mmo\GuzzleMiddleware\Metrics\Duration;

use Prometheus\CollectorRegistry;
use Prometheus\Histogram;

readonly class PrometheusRegistryCollectorDurationMetric implements DurationMetricCollectorInterface
{
    private const DEFAULT_BUCKET = [50, 100, 250, 500, 1000, 2500, 5000, 7500, 10000, 30000];

    public function __construct(private CollectorRegistry $collectorRegistry, private string $namespace, private array $buckets = self::DEFAULT_BUCKET) {}

    public function collect(float $duration, DurationMetricLabelsDto $dto): void
    {
        $this->getHistogram()->observe($duration, [$dto->method, $dto->uri, $dto->statusCode]);
    }

    private function getHistogram(): Histogram
    {
        return $this->collectorRegistry->getOrRegisterHistogram(
            $this->namespace,
            'guzzle_response_duration_ms',
            'Guzzle response duration histogram',
            ['method', 'url', 'status_code'],
            $this->buckets,
        );
    }
}
