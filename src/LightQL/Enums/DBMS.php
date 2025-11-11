<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Enums;

/**
 * Lists the supported Database Management Systems
 */
enum DBMS: string
{
    /**
     * MySQL database.
     */
    case MYSQL = 'mysql';

    /**
     * MariaDB database.
     */
    case MARIADB = 'mariadb';

    /**
     * PostgreSQL database.
     */
    case POSTGRESQL = 'pgsql';

    /**
     * Sqlite database.
     */
    case SQLITE = 'sqlite';

    /**
     * Microsoft SQL Server database.
     */
    case MSSQL = 'mssql';

    /**
     * Oracle database.
     */
    case ORACLE = 'oracle';

    /**
     * Sybase database.
     */
    case SYBASE = 'sybase';

    /**
     * Custom database.
     *
     * Database connection options should be manually provided.
     */
    case CUSTOM = 'custom';

    public function getDriver(): string
    {
        return match ($this) {
            self::MYSQL, self::MARIADB => 'mysql',
            self::POSTGRESQL => 'pgsql',
            self::SQLITE => 'sqlite',
            self::MSSQL => 'sqlsrv',
            self::ORACLE => 'oci',
            self::SYBASE => 'dblib',
            self::CUSTOM => '',
        };
    }
}
