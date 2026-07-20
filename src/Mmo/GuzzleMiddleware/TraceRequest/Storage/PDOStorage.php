<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\Storage;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mmo\GuzzleMiddleware\TraceRequest\PDO\PDOMysqlAdapter;
use Mmo\GuzzleMiddleware\TraceRequest\StorageInterface;
use PDO;

class PDOStorage implements StorageInterface
{
    public function __construct(private readonly PDO $database, private readonly string $tableName = 'guzzle_trace_request')
    {
        if (!in_array($database->getAttribute(\PDO::ATTR_DRIVER_NAME), ['mysql'], true)) {
            throw new \RuntimeException('Only MySQL is supported.');
        }

        $this->createTable();
    }

    public function store(string $requestId, Request $request, Response $response): void
    {
        $driver = $this->database->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $sql = match ($driver) {
            'mysql' => PDOMysqlAdapter::getInsertSql($this->tableName),
            default => throw new \RuntimeException('Only MySQL is supported.'),
        };

        $statement = $this->database->prepare($sql);
        $statement->execute([
            ':key' => $requestId,
            ':request' => Message::toString($request),
            ':response' => Message::toString($response),
        ]);
    }

    protected function createTable(): void
    {
        $driver = $this->database->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = match ($driver) {
            'mysql' => PDOMysqlAdapter::getCreateTableSql($this->tableName),
            default => throw new \RuntimeException('Only MySQL is supported.'),
        };
        $this->database->query($sql);
    }
}
