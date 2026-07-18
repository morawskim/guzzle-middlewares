<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\tests;

use Mmo\GuzzleMiddleware\TraceRequest\Storage\StreamStorage;
use Mmo\GuzzleMiddleware\TraceRequest\TraceRequestMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
class TraceRequestMiddlewareTest extends TestCase
{
    public function testA()
    {
        $middleware = new TraceRequestMiddleware(new StreamStorage(
            $requestStream = fopen('php://memory', 'rb+'),
            $responseStream = fopen('php://memory', 'rb+'),
        ));
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($middleware);

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.example.com/v1/',
        ]);
        $client->request('POST', 'resource', [
            'body' => json_encode(['xyz' => 'abc'], JSON_THROW_ON_ERROR),
        ]);

        rewind($requestStream);
        rewind($responseStream);
        $requestStreamContents = stream_get_contents($requestStream);
        $responseStreamContents = stream_get_contents($responseStream);

        $this->assertNotEmpty($requestStreamContents);
        $this->assertStringContainsString('POST /v1/resource HTTP', $requestStreamContents);
        $this->assertStringContainsString('User-Agent: GuzzleHttp', $requestStreamContents);
        $this->assertStringContainsString('xyz":"abc"', $requestStreamContents);

        $this->assertNotEmpty($responseStreamContents);
        $this->assertStringContainsString('HTTP/1.1 200', $responseStreamContents);
        $this->assertStringContainsString('X-Foo: Bar', $responseStreamContents);
        $this->assertStringContainsString('foo":"bar"', $responseStreamContents);
    }
}
