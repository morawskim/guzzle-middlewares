<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\Storage;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mmo\GuzzleMiddleware\TraceRequest\StorageInterface;

class StreamStorage implements StorageInterface
{
    /**
     * @var resource
     */
    private $streamRequest;
    /**
     * @var resource
     */
    private $streamResponse;

    public function __construct($streamRequest, $streamResponse)
    {
        if (!is_resource($streamRequest) || !is_resource($streamResponse)) {
            throw new \InvalidArgumentException('$streamRequest and $streamResponse must be a resource');
        }

        $requestType = get_resource_type($streamRequest);
        $responseType = get_resource_type($streamResponse);

        if ($requestType !== 'stream' || $responseType !== 'stream') {
            throw new \InvalidArgumentException('$streamRequest and $streamResponse must be a stream resource');
        }

        $this->streamRequest = $streamRequest;
        $this->streamResponse = $streamResponse;
    }

    public function store(Request $request, Response $response): void
    {
        $marker = "\n" . str_repeat('=', 20) . "\n";
        fwrite($this->streamRequest, Message::toString($request) . $marker);
        fwrite($this->streamResponse, Message::toString($response) . $marker);
    }
}
