<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Query;

use ElementaryFramework\LightQL\Exceptions\QueryException;
use ElementaryFramework\LightQL\LightQL;
use Generator;
use PDOStatement;
use PDO;

/**
 * Wraps a PDOStatement to provide convenient access to query results.
 */
final readonly class QueryResult
{
    /**
     * Create a new QueryResult instance.
     *
     * @param PDOStatement $statement The executed PDO statement
     * @param string $queryString The original query string for context
     * @param LightQL $lightQL The LightQL instance for accessing connection methods
     */
    public function __construct(
        public PDOStatement $statement,
        public string $queryString,
        private LightQL $lightQL,
    ) {}

    /**
     * Check if this query result contains data that can be fetched.
     * Returns true for SELECT queries, false for INSERT/UPDATE/DELETE etc.
     */
    public function hasResults(): bool
    {
        return $this->statement->columnCount() > 0;
    }

    /**
     * Fetch all rows as an associative array.
     *
     * @return array<int, array<string, mixed>>
     * @throws QueryException
     */
    public function fetchAll(): array
    {
        $this->ensureHasResults();

        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generator that yields rows one by one as associative arrays.
     * Useful for processing large result sets without loading everything into memory.
     *
     * @return Generator<int, array<string, mixed>>
     * @throws QueryException
     */
    public function rows(): Generator
    {
        $this->ensureHasResults();

        while ($row = $this->statement->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     * Fetch the first row as an associative array.
     *
     * @return array<string, mixed>|null
     * @throws QueryException
     */
    public function fetchFirst(): ?array
    {
        $this->ensureHasResults();

        $result = $this->statement->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * Fetch a single column value from the first row.
     *
     * @param int|string $column Column index (0-based) or column name
     * @return mixed
     * @throws QueryException
     */
    public function fetchColumn(int|string $column = 0): mixed
    {
        $this->ensureHasResults();

        if (is_string($column)) {
            $row = $this->statement->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? ($row[$column] ?? null) : null;
        }

        return $this->statement->fetchColumn($column);
    }

    /**
     * Get the number of rows affected by the last DELETE, INSERT, or UPDATE statement.
     */
    public function getRowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * Get the number of columns in the result set.
     */
    public function getColumnCount(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * Get the last insert ID (useful for INSERT queries with auto-increment columns).
     */
    public function getLastInsertId(): int
    {
        return $this->lightQL->lastInsertId();
    }

    /**
     * Get column metadata.
     *
     * @param int $column 0-based column index
     * @return array<string, mixed>|false
     */
    public function getColumnMeta(int $column): array|false
    {
        return $this->statement->getColumnMeta($column);
    }

    /**
     * Ensure that the result set has results.
     *
     * @throws QueryException
     */
    private function ensureHasResults(): void
    {
        if (!$this->hasResults()) {
            throw new QueryException("Cannot perform operation on a query that returns no data.");
        }
    }
}
