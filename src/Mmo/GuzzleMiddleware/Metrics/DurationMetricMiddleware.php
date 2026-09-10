<?php

namespace Mmo\GuzzleMiddleware\Metrics;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Mmo\GuzzleMiddleware\Metrics\Duration\DurationMetricCollectorInterface;
use Mmo\GuzzleMiddleware\Metrics\Duration\DurationMetricLabelsDto;

class DurationMetricMiddleware
{
    public const string URI_PATH_TEMPLATE = 'request_path_template';

    public function __construct(private readonly DurationMetricCollectorInterface $metrics) {}

    /**
     * Middleware that calculates the duration of a guzzle request.
     * After calculation it updates histogram.
     *
     * @param callable $handler
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public function __invoke(callable $handler): callable
    {
        return function (Request $request, array $options) use ($handler) {
            $start = hrtime(true);
            return $handler($request, $options)->then(
                function (Response $response) use ($request, $start, $options) {
                    $uri = $request->getUri();
                    if (!empty($options[self::URI_PATH_TEMPLATE])) {
                        $baseUri = $options['base_uri'] ?? $request->getUri();
                        $uri = UriResolver::resolve(Utils::uriFor($baseUri), Utils::uriFor($options[self::URI_PATH_TEMPLATE]));
                    }

                    $this->metrics->collect(
                        (hrtime(true) - $start) / 1e+6,
                        DurationMetricLabelsDto::create(
                            $request->getMethod(),
                            $uri->getHost() . $this->normalizePath(rawurldecode($uri->getPath())),
                            $response->getStatusCode()
                        ),
                    );
                    return $response;
                },
            );
        };
    }

    private function normalizePath(string $path): string
    {
        if (!empty($path) && $path[0] !== '/') {
            return '/'.$path;
        }

        return $path;
    }
}
