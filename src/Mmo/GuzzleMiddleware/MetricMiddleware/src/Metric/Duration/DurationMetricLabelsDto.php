<?php

namespace Mmo\GuzzleMetricsMiddleware\Metric\Duration;

readonly class DurationMetricLabelsDto
{
    public string $method;
    public string $uri;
    public int $statusCode;

    public static function create(string $method, string $uri, int $statusCode): self
    {
        $obj = new self();
        $obj->method = $method;
        $obj->uri = $uri;
        $obj->statusCode = $statusCode;

        return $obj;
    }
}
