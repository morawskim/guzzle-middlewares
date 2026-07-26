<?php

namespace Mmo\GuzzleMiddleware\TraceRequest\PDO;

/**
 * @internal
 */
class PDOMysqlAdapter
{
    public static function getCreateTableSql(string $tableName): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS `{$tableName}` (
    `id` bigint unsigned AUTO_INCREMENT PRIMARY KEY NOT NULL,
    `key` varchar(255) NOT NULL,
    `datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `request` longtext NOT NULL,
    `response` longtext NOT NULL,
    UNIQUE KEY `uniq_key` (`key`)
);
SQL;
    }

    public static function getInsertSql(string $tableName): string
    {
        return <<<SQL
INSERT INTO  `{$tableName}`(`key`, `request`, `response`)
  VALUES(:key, :request, :response);
SQL;
    }
}
