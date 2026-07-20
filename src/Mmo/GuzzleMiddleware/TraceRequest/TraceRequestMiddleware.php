<?php

namespace Mmo\GuzzleMiddleware\TraceRequest;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class TraceRequestMiddleware
{
    public const string REQUEST_ID = 'trace_request_id_middleware';

    public function __construct(private readonly StorageInterface $storage) {}

    /**
     * @param callable $handler
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public function __invoke(callable $handler): callable
    {
        return function (Request $request, array $options) use ($handler) {
            $this->validateRequestId($options);

            return $handler($request, $options)->then(
                function (Response $response) use ($request, $options) {
                    $requestId = $options[self::REQUEST_ID];
                    $requestId = is_callable($requestId) ? $requestId($request, $response) : $requestId;
                    $this->storage->store($requestId, $request, $response);

                    return $response;
                },
            );
        };
    }

    private function validateRequestId(array $options): void
    {
        if (empty($options[self::REQUEST_ID])) {
            throw new \InvalidArgumentException('Request id must be provided');
        }

        $requestId = $options[self::REQUEST_ID];
        if (!(is_string($requestId) || is_callable($requestId))) {
            throw new \InvalidArgumentException('Request id must be string or callable');
        }
    }
}
