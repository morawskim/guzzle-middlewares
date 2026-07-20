<?php

namespace Mmo\GuzzleMiddleware\TraceRequest;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

interface StorageInterface
{
    public function store(string $requestId, Request $request, Response $response): void;
}
