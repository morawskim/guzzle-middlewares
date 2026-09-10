<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\tests\integration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mmo\GuzzleMiddleware\TraceRequest\Storage\PDOStorage;
use Mmo\GuzzleMiddleware\TraceRequest\tests\PDOFactory;
use Mmo\GuzzleMiddleware\TraceRequest\TraceRequestMiddleware;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
class MysqlTest extends TestCase
{
    private const TABLE_NAME = 'guzzle_trace_request';
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = PDOFactory::fromDatabaseUrl($_ENV['DATABASE_URL']);
        $this->pdo->exec('DROP TABLE IF EXISTS ' . self::TABLE_NAME);
        parent::setUp();
    }

    public function testMysqlStorage(): void
    {
        $middleware = new TraceRequestMiddleware(new PDOStorage(
            $this->pdo,
            self::TABLE_NAME,
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
            TraceRequestMiddleware::REQUEST_ID => '1234567890',
        ]);

        $rows = $this->pdo->query('SELECT * FROM ' . self::TABLE_NAME)->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame('1234567890', $rows[0]['key']);
        $requestStreamContents = $rows[0]['request'];
        $responseStreamContents = $rows[0]['response'];

        $this->assertStringContainsString('POST /v1/resource HTTP', $requestStreamContents);
        $this->assertStringContainsString('User-Agent: GuzzleHttp', $requestStreamContents);
        $this->assertStringContainsString('xyz":"abc"', $requestStreamContents);

        $this->assertStringContainsString('HTTP/1.1 200', $responseStreamContents);
        $this->assertStringContainsString('X-Foo: Bar', $responseStreamContents);
        $this->assertStringContainsString('foo":"bar"', $responseStreamContents);
    }
}
