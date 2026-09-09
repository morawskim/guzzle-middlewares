<?php

namespace Mmo\GuzzleMetricsMiddlewareTest;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mmo\GuzzleMetricsMiddleware\DurationMetricMiddleware;
use Mmo\GuzzleMetricsMiddleware\Metric\Duration\DurationMetricLabelsDto;
use Mmo\GuzzleMetricsMiddleware\Test\MemoryDurationMetricCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DurationMetricMiddlewareTest extends TestCase
{
    private MemoryDurationMetricCollector $collector;
    private DurationMetricMiddleware $middleware;

    protected function setUp(): void
    {
        $this->collector = new MemoryDurationMetricCollector();
        $this->middleware = new DurationMetricMiddleware($this->collector);
    }

    public function testDefaultPath(): void
    {
        $request = new Request('GET', 'https://api.example.com/users/123');
        $options = [];

        $handler = function (Request $req, array $opts) {
            $this->assertEquals('https://api.example.com/users/123', (string) $req->getUri());
            return new FulfilledPromise(new Response(200));
        };

        $middlewareCallable = ($this->middleware)($handler);
        $promise = $middlewareCallable($request, $options);
        $promise->wait();

        $this->assertCount(1, $this->collector->metrics);
        $this->assertEquals(
            DurationMetricLabelsDto::create('GET', 'api.example.com/users/123', 200),
            $this->collector->metrics[0][1]
        );
    }

    #[DataProvider('providerForTestPathTemplate')]
    public function testPathTemplate(string $uri, string $pathTemplate, string $expectedMetricUri): void
    {
        $request = new Request('POST', $uri);
        $options = [
            DurationMetricMiddleware::URI_PATH_TEMPLATE => $pathTemplate,
        ];

        $handler = function (Request $req, array $opts) use ($uri) {
            $this->assertEquals($uri, (string) $req->getUri());
            return new FulfilledPromise(new Response(201));
        };

        $middlewareCallable = ($this->middleware)($handler);
        $promise = $middlewareCallable($request, $options);
        $promise->wait();

        $this->assertCount(1, $this->collector->metrics);
        $this->assertEquals(
            DurationMetricLabelsDto::create('POST', $expectedMetricUri, 201),
            $this->collector->metrics[0][1]
        );
    }

    public static function providerForTestPathTemplate(): iterable
    {
        yield 'relative path' => [
            'https://api.example.com/v1/orders/456',
            'v1/orders/<id>',
            'api.example.com/v1/orders/v1/orders/<id>',
        ];
        yield 'absolute path' => [
            'https://api.example.com/v1/orders/456',
            '/v1/orders/<id>',
            'api.example.com/v1/orders/<id>',
        ];
    }

    #[DataProvider('providerForTestWithGuzzleClientBaseUri')]
    public function testWithGuzzleClientBaseUri(string $path, string $templatePath, string $expectedRequestUri, string $expectedMetricUriLabel): void
    {
        $mock = new MockHandler([
            function (Request $request) use ($expectedRequestUri) {
                $this->assertEquals($expectedRequestUri, (string) $request->getUri());
                return new Response(200);
            },
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($this->middleware);

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.example.com/v1/',
        ]);

        $client->request('GET', $path, [
            DurationMetricMiddleware::URI_PATH_TEMPLATE => $templatePath,
        ]);

        $this->assertCount(1, $this->collector->metrics);
        $this->assertEquals(
            DurationMetricLabelsDto::create('GET', $expectedMetricUriLabel, 200),
            $this->collector->metrics[0][1]
        );
    }

    public static function providerForTestWithGuzzleClientBaseUri(): iterable
    {
        yield 'relative path' => [
            'users/123',
            'users/<id>',
            'https://api.example.com/v1/users/123',
            'api.example.com/v1/users/<id>',
        ];
        yield 'relative path for request but absolute for template' => [
            'users/123',
            '/users/<id>',
            'https://api.example.com/v1/users/123',
            'api.example.com/users/<id>',
        ];
        yield 'absolute path' => [
            '/v2/orders/456',
            '/v2/orders/<id>',
            'https://api.example.com/v2/orders/456',
            'api.example.com/v2/orders/<id>',
        ];
    }

    #[DataProvider('providerForTestWithGuzzleClientWithoutBaseUri')]
    public function testWithGuzzleClientWithoutBaseUri(string $uri, string $templatePath, string $expectedRequestUri, string $expectedMetricUriLabel): void
    {
        $mock = new MockHandler([
            function (Request $request) use ($expectedRequestUri) {
                $this->assertEquals($expectedRequestUri, (string) $request->getUri());
                return new Response(200);
            },
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($this->middleware);

        $client = new Client([
            'handler' => $handlerStack,
        ]);

        $client->request('GET', $uri, [
            DurationMetricMiddleware::URI_PATH_TEMPLATE => $templatePath,
        ]);

        $this->assertCount(1, $this->collector->metrics);
        $this->assertEquals(
            DurationMetricLabelsDto::create('GET', $expectedMetricUriLabel, 200),
            $this->collector->metrics[0][1]
        );
    }

    public static function providerForTestWithGuzzleClientWithoutBaseUri(): iterable
    {
        yield 'relative template path' => [
            'https://api.example.com/v1/users/123',
            'v1/users/<id>',
            'https://api.example.com/v1/users/123',
            'api.example.com/v1/users/v1/users/<id>',
        ];
        yield 'relative path for request but absolute for template' => [
            'https://api.example.com/v1/users/123',
            '/users/<id>',
            'https://api.example.com/v1/users/123',
            'api.example.com/users/<id>',
        ];
        yield 'absolute path' => [
            'https://api.example.com/v2/orders/456',
            '/v2/orders/<id>',
            'https://api.example.com/v2/orders/456',
            'api.example.com/v2/orders/<id>',
        ];
    }
}
