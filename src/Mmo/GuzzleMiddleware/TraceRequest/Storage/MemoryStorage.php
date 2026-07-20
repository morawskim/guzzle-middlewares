<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\Storage;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mmo\GuzzleMiddleware\TraceRequest\StorageInterface;

class MemoryStorage implements StorageInterface
{
    public array $data = [];

    public function store(string $requestId, Request $request, Response $response): void
    {
        $this->data[] = [$requestId, $request, $response];
    }
}
