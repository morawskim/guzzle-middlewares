<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\tests;

use PDO;

class PDOFactory
{
    public static function fromDatabaseUrl(string $url): PDO
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $parts['host'],
            $parts['port'] ?? 3306,
            ltrim($parts['path'], '/'),
            $query['charset'] ?? 'utf8mb4'
        );

        return new PDO(
            $dsn,
            urldecode($parts['user'] ?? ''),
            urldecode($parts['pass'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
    }
}
