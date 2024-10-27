<?php

namespace Ucscode\DoctrineExpression;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;

/**
 * Enum representing the various database driver platforms supported by Doctrine.
 * This enum maps each driver to its corresponding Doctrine platform class.
 *
 * @author Uchenna Ajah (Ucscode) <uche23mail@gmail.com>
 */
enum DriverEnum: string
{
    case PDO_MYSQL = MySQLPlatform::class; // MYSQL
    case PDO_PGSQL = PostgreSQLPlatform::class; // PostgreSQL
    case PDO_SQLITE = SQLitePlatform::class; // SQLite
    case PDO_SQLSRV = SQLServerPlatform::class; // SQL Server
    case PDO_OCI = OraclePlatform::class; // Oracle
}
