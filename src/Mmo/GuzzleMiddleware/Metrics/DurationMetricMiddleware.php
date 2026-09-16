<?php

namespace Mmo\GuzzleMiddleware\Metrics;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Mmo\GuzzleMiddleware\Metrics\Duration\DurationMetricCollectorInterface;
use Mmo\GuzzleMiddleware\Metrics\Duration\DurationMetricLabelsDto;
use Psr\Http\Message\UriInterface;

class DurationMetricMiddleware
{
    public const string URI_RESOLVER = 'uri_resolver_for_duration_metric';

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
                    if (!empty($options[self::URI_RESOLVER])) {
                        $uri = $this->buildUri($options[self::URI_RESOLVER], $request->getUri(), $options);
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

    private function buildUri($value, UriInterface $uri, array $options): UriInterface
    {
        if (is_callable($value)) {
            $newUri = $value(UriResolver::resolve(Utils::uriFor($options['base_uri'] ?? ''), $uri));
        } else if (is_string($value)) {
            $newUri = UriResolver::resolve(Utils::uriFor($options['base_uri'] ?? $uri), Utils::uriFor($value));
        } else {
            throw new \RuntimeException(sprintf('Unsupported value "%s"', gettype($value)));
        }

        if (!$newUri instanceof UriInterface) {
            throw new \RuntimeException(sprintf('Unsupported return value "%s"', gettype($newUri)));
        }

        return $newUri;
    }

    private function normalizePath(string $path): string
    {
        if (!empty($path) && $path[0] !== '/') {
            return '/'.$path;
        }

        return $path;
    }
}
