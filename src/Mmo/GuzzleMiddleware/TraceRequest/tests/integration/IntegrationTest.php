<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\tests\integration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;
use Mmo\GuzzleMiddleware\TraceRequest\Storage\StreamStorage;
use Mmo\GuzzleMiddleware\TraceRequest\TraceRequestMiddleware;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function test5xxServerException(): void
    {
        $exceptionHasBeenThrow = false;
        $middleware = new TraceRequestMiddleware(new StreamStorage(
            $requestStream = fopen('php://memory', 'rb+'),
            $responseStream = fopen('php://memory', 'rb+'),
        ));

        $handlerStack = HandlerStack::create();
        $handlerStack->push($middleware);

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://httpbin.io',
        ]);
        try {
            $client->request('POST', '/status/507', [
                'body' => json_encode(['xyz' => 'abc'], JSON_THROW_ON_ERROR),
                TraceRequestMiddleware::REQUEST_ID => '1234567890',
            ]);
        } catch (ServerException $e) {
            $exceptionHasBeenThrow = true;
        }

        rewind($requestStream);
        rewind($responseStream);
        $requestStreamContents = stream_get_contents($requestStream);
        $responseStreamContents = stream_get_contents($responseStream);

        $this->assertTrue($exceptionHasBeenThrow);
        $this->assertNotEmpty($requestStreamContents);
        $this->assertStringContainsString('POST /status/507 HTTP', $requestStreamContents);

        $this->assertNotEmpty($requestStreamContents);
        $this->assertStringContainsString('HTTP/1.1 507', $responseStreamContents);
    }
}
