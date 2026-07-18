<?php

namespace Mmo\GuzzleMiddleware\TraceRequest;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class TraceRequestMiddleware
{
    public function __construct(private readonly StorageInterface $storage) {}

    /**
     * @param callable $handler
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public function __invoke(callable $handler): callable
    {
        return function (Request $request, array $options) use ($handler) {
            return $handler($request, $options)->then(
                function (Response $response) use ($request, $options) {
                    $this->storage->store($request, $response);
                    return $response;
                },
            );
        };
    }
}
