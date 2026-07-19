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
    `id` bigint AUTO_INCREMENT PRIMARY KEY NOT NULL,
    `datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `request` longtext NOT NULL,
    `response` longtext NOT NULL
);
SQL;
    }

    public static function getInsertSql(string $tableName): string
    {
        return <<<SQL
INSERT INTO  `{$tableName}`(`request`, `response`)
  VALUES(:request, :response);
SQL;
    }
}
