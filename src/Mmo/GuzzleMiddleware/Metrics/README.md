# Guzzle Metrics Middleware

A Guzzle HTTP client middleware that measures the duration of requests and collects metrics. It helps in monitoring external service calls by tracking request execution time, HTTP methods, target URIs, and response status codes.

## Features

- **Duration Tracking**: Measures the time elapsed for each request (in milliseconds).
- **Metric Abstraction**: Decoupled from specific monitoring systems through the `DurationMetricCollectorInterface`.
- **Prometheus Support**: Includes a collector for Prometheus (using `promphp/prometheus_client_php`).
- **Path Normalization**: Supports URI path templates to prevent high cardinality in metrics (e.g., grouping `/users/1` and `/users/2` under `/users/<id>`).

## Installation

```bash
composer require mmo/guzzle-metrics-middleware
```

## Usage

### 1. Initialize the Collector

You need a collector that implements `DurationMetricCollectorInterface`.

### Prometheus

For Prometheus, you can use `PrometheusHistogramDurationMetricCollector`.

```php
use Mmo\GuzzleMiddleware\Metrics\Duration\PrometheusHistogramDurationMetricCollector;
use Prometheus\CollectorRegistry;

/** @var CollectorRegistry $registry */
$histogram = $registry->getOrRegisterHistogram(
    'myapp',
    'request_duration_ms',
    'Guzzle request duration',
    ['method', 'uri', 'status_code']
);

$collector = new PrometheusHistogramDurationMetricCollector($histogram);
```

### 2. Set up the Middleware

Create the middleware and push it onto the Guzzle handler stack.

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Mmo\GuzzleMiddleware\Metrics\DurationMetricMiddleware;

$middleware = new DurationMetricMiddleware($collector);

$stack = HandlerStack::create();
$stack->push($middleware);

$client = new Client(['handler' => $stack]);
```

### 3. Path Templating (Optional)

To avoid creating a separate metric for every unique URL (which can lead to performance issues in monitoring systems), you can provide a template for the URI path using the `DurationMetricMiddleware::URI_PATH_TEMPLATE` option.

```php
$client->request('GET', '/users/123', [
    DurationMetricMiddleware::URI_PATH_TEMPLATE => '/users/<id>'
]);
```

The metric will then use `example.com/users/<id>` instead of `example.com/users/123`.

#### Relative vs Absolute Templates

- **Relative templates**: If you use a `base_uri` in Guzzle, a relative template will be appended to it.
- **Absolute templates**: Starting with a `/` will replace the path portion of the URI in the metrics.

## License

MIT
