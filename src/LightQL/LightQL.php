<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL;

use ElementaryFramework\LightQL\Enums\DBMS;
use ElementaryFramework\LightQL\Exceptions\LightQLException;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;
use ElementaryFramework\LightQL\Query\Builder;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Query builder class with parameter binding and type safety.
 */
final class LightQL
{
    /**
     * The current PDO instance.
     */
    private(set) ?PDO $pdo = null;

    /**
     * Class constructor.
     *
     * @param string $database
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param DBMS $dbms
     * @param array $pdoOptions
     * @param int|null $port
     * @param string|null $socket
     * @param string|null $charset
     * @param string|null $customDsn
     * @throws LightQLException
     */
    public function __construct(
        private readonly string  $database,
        private readonly string  $hostname = '',
        private readonly string  $username = '',
        private readonly string  $password = '',
        private(set) readonly DBMS $dbms = DBMS::SQLITE,
        private readonly array   $pdoOptions = [],
        private readonly ?int    $port = null,
        private readonly ?string $socket = null,
        private readonly ?string $charset = null,
        ?string                  $customDsn = null,
    )
    {
        $dsn = $customDsn ?? $this->buildDsn();
        $commands = $this->getInitCommands();

        $this->connect($dsn, $commands);
    }

    /**
     * Factory method for backward compatibility with array options.
     *
     * @param array<string, mixed> $options
     *
     * @throws LightQLException
     */
    public static function fromArray(array $options): self
    {
        $dbms = DBMS::tryFrom(strtolower($options['dbms'] ?? 'sqlite')) ?? DBMS::SQLITE;

        if (isset($options['dsn']['driver'])) {
            $driver = $options['dsn']['driver'];
            unset($options['dsn']['driver']);

            $dsnParts = [];
            foreach ($options['dsn'] as $key => $value) {
                $dsnParts[] = is_int($key) ? $value : "{$key}={$value}";
            }
            $customDsn = $driver . ':' . implode(';', $dsnParts);

            return new self(
                database: $options['database'] ?? '',
                hostname: $options['hostname'] ?? '',
                username: $options['username'] ?? '',
                password: $options['password'] ?? '',
                dbms: $dbms,
                pdoOptions: $options['options'] ?? [],
                customDsn: $customDsn,
            );
        }

        return new self(
            database: $options['database'] ?? '',
            hostname: $options['hostname'] ?? '',
            username: $options['username'] ?? '',
            password: $options['password'] ?? '',
            dbms: $dbms,
            pdoOptions: $options['options'] ?? [],
            port: isset($options['port']) && is_numeric($options['port']) ? (int)$options['port'] : null,
            socket: $options['socket'] ?? null,
            charset: $options['charset'] ?? null,
        );
    }

    /**
     * Factory method used to create a new LightQL instance using a persistence unit.
     *
     * @param PersistenceUnit $persistenceUnit The persistence unit.
     *
     * @throws LightQLException
     */
    public static function fromPersistenceUnit(PersistenceUnit $persistenceUnit): self
    {
        return new self(
            database: $persistenceUnit->database,
            hostname: $persistenceUnit->hostname,
            username: $persistenceUnit->username,
            password: $persistenceUnit->password,
            dbms: $persistenceUnit->dbms,
        );
    }

    /**
     * Close the database connection.
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    /**
     * Create a new query builder instance.
     */
    public function builder(): Builder
    {
        return new Builder($this);
    }

    /**
     * Execute raw query.
     *
     * @throws LightQLException
     */
    public function query(string $query, int $mode = PDO::FETCH_ASSOC): PDOStatement
    {
        try {
            $statement = $this->pdo->query($query, $mode);
            if ($statement === false) {
                throw new LightQLException("Query execution failed");
            }
            return $statement;
        } catch (PDOException $e) {
            throw new LightQLException("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Prepare a statement.
     *
     * @param array<int, mixed> $options
     * @throws LightQLException
     */
    public function prepare(string $query, array $options = []): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($query, $options);
            if ($statement === false) {
                throw new LightQLException("Failed to prepare statement");
            }
            return $statement;
        } catch (PDOException $e) {
            throw new LightQLException("Prepare failed: " . $e->getMessage());
        }
    }

    /**
     * Get last insert ID.
     */
    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Quote a value.
     */
    public function quote(string $value): string
    {
        return $this->pdo->quote($value);
    }

    /**
     * Execute the given callback in a transaction, and automatically commit on success.
     *
     * @param callable(): void $callback The callback to execute in a transaction.
     * @param null|callable(Throwable): bool $onError An optional error callback with the exception occurred while executing the callback. This function can
     * return a boolean saying whether the transaction should be commited or not.
     *
     * @throws Throwable
     */
    public function transaction(callable $callback, ?callable $onError = null): void
    {
        $this->beginTransaction();

        try {
            $callback();
            $this->commit();
        } catch (Throwable $e) {
            $shouldCommit = false;
            $lastException = $e;

            try {
                if ($onError !== null) {
                    $shouldCommit = $onError($e);
                }
            } catch (Throwable $e) {
                $lastException = $e;
            }

            if ($shouldCommit) {
                $this->commit();
            } else {
                $this->rollBack();
            }

            throw $lastException;
        }
    }

    /**
     * Begin transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction.
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Gets the error message from the last query, if any.
     *
     * @return string|null The error message from the last query, or null if no error occurred.
     */
    public function lastError(): ?string
    {
        return $this->pdo->errorInfo()[2] ?? null;
    }

    /**
     * Build DSN string based on a DBMS type.
     * @throws LightQLException
     */
    private function buildDsn(): string
    {
        $driver = $this->dbms->getDriver();

        $attr = match ($this->dbms) {
            DBMS::MYSQL, DBMS::MARIADB => $this->buildMySqlDsnAttributes(),
            DBMS::POSTGRESQL => $this->buildPgSqlDsnAttributes(),
            DBMS::SYBASE => $this->buildSybaseDsnAttributes(),
            DBMS::ORACLE => $this->buildOracleDsnAttributes(),
            DBMS::MSSQL => $this->buildMsSqlDsnAttributes($driver),
            DBMS::SQLITE => [$this->database],
            DBMS::CUSTOM => throw new LightQLException("Custom DSN is required when using DBMS::CUSTOM"),
        };

        $parts = [];
        foreach ($attr as $key => $value) {
            $parts[] = is_int($key) ? $value : "{$key}={$value}";
        }

        return $driver . ':' . implode(';', $parts);
    }

    /**
     * @return array<string|int, string|int>
     */
    private function buildMySqlDsnAttributes(): array
    {
        $attr = ['dbname' => $this->database];

        if ($this->socket !== null) {
            $attr['unix_socket'] = $this->socket;
        } else {
            $attr['host'] = $this->hostname;
            if ($this->port !== null) {
                $attr['port'] = $this->port;
            }
        }

        return $attr;
    }

    /**
     * @return array<string, string|int>
     */
    private function buildPgSqlDsnAttributes(): array
    {
        $attr = [
            'host' => $this->hostname,
            'dbname' => $this->database,
        ];

        if ($this->port !== null) {
            $attr['port'] = $this->port;
        }

        return $attr;
    }

    /**
     * @return array<string, string|int>
     */
    private function buildSybaseDsnAttributes(): array
    {
        $attr = [
            'host' => $this->hostname,
            'dbname' => $this->database,
        ];

        if ($this->port !== null) {
            $attr['port'] = $this->port;
        }

        return $attr;
    }

    /**
     * @return array<string, string>
     */
    private function buildOracleDsnAttributes(): array
    {
        $dbname = $this->hostname
            ? "//{$this->hostname}" . ($this->port ? ":{$this->port}" : ':1521') . "/{$this->database}"
            : $this->database;

        $attr = ['dbname' => $dbname];

        if ($this->charset !== null) {
            $attr['charset'] = $this->charset;
        }

        return $attr;
    }

    /**
     * @return array<string, string|int>
     */
    private function buildMsSqlDsnAttributes(string &$driver): array
    {
        // MSSQL can use dblib or sqlsrv driver
        $useDblib = false; // Can be configured if needed

        if ($useDblib) {
            $driver = 'dblib';
            return [
                'host' => $this->hostname . ($this->port ? ":{$this->port}" : ''),
                'dbname' => $this->database,
            ];
        }

        $driver = 'sqlsrv';
        return [
            'Server' => $this->hostname . ($this->port ? ",{$this->port}" : ''),
            'Database' => $this->database,
        ];
    }

    /**
     * Get initialization commands for the connection.
     *
     * @return array<int, string>
     */
    private function getInitCommands(): array
    {
        $commands = match ($this->dbms) {
            DBMS::MYSQL, DBMS::MARIADB => ['SET SQL_MODE=ANSI_QUOTES'],
            DBMS::MSSQL => ['SET QUOTED_IDENTIFIER ON', 'SET ANSI_NULLS ON'],
            default => [],
        };

        if (
            in_array($this->dbms, [DBMS::MYSQL, DBMS::MARIADB, DBMS::POSTGRESQL, DBMS::SYBASE, DBMS::MSSQL])
            && $this->charset !== null
        ) {
            $commands[] = "SET NAMES " . $this->quote($this->charset);
        }

        return $commands;
    }

    /**
     * Establish database connection.
     *
     * @param array<int, string> $initCommands
     * @throws LightQLException
     */
    private function connect(string $dsn, array $initCommands): void
    {
        try {
            $this->pdo = new PDO(
                $dsn,
                $this->username,
                $this->password,
                $this->pdoOptions
            );

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach ($initCommands as $command) {
                $this->pdo->exec($command);
            }
        } catch (PDOException $e) {
            throw new LightQLException("Database connection failed: " . $e->getMessage());
        }
    }
}
