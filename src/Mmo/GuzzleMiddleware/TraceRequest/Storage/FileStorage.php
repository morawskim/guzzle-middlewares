<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\Storage;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Message;
use Mmo\GuzzleMiddleware\TraceRequest\StorageInterface;

class FileStorage implements StorageInterface
{
    public function __construct(private readonly string $directory)
    {
    }

    public function store(string $requestId, Request $request, Response $response): void
    {
        $domain = $request->getUri()->getHost();
        $filePathPrefix = rtrim($this->directory, '/')
            . DIRECTORY_SEPARATOR
            . $domain
            . $requestId;

        file_put_contents(
            $filePathPrefix . '-request',
            Message::toString($request),
        );

        file_put_contents(
            $filePathPrefix . '-response',
            Message::toString($response),
        );
    }
}
