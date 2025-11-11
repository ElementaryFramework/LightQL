<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Query;

use ElementaryFramework\LightQL\Enums\DBMS;
use ElementaryFramework\LightQL\Enums\JoinType;
use ElementaryFramework\LightQL\Enums\SortOrder;
use ElementaryFramework\LightQL\Exceptions\LightQLException;
use ElementaryFramework\LightQL\Exceptions\QueryException;
use ElementaryFramework\LightQL\LightQL;

/**
 * Query builder class for constructing SQL queries.
 */
class Builder
{
    /**
     * Registered SQL operators.
     *
     * @var list<string>
     */
    private const array OPERATORS = ['!=', '<>', '<=', '>=', '=', '<', '>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];

    /**
     * The table name.
     */
    private string $table = '';

    /**
     * Where conditions with parameters.
     *
     * @var array<int, array{sql: string, params: array<string, mixed>}>
     */
    private array $whereConditions = [];

    /**
     * Order by clauses.
     *
     * @var array<int, string>
     */
    private array $orderClauses = [];

    /**
     * Offset value for the LIMIT clause.
     */
    private ?int $limitOffset = null;

    /**
     * Count value for the LIMIT clause.
     */
    private ?int $limitCount = null;

    /**
     * Group by columns.
     *
     * @var array<int, string>
     */
    private array $groupByColumns = [];

    /**
     * Having clause with parameters.
     */
    private ?string $havingClause = null;

    /**
     * @var array<string, mixed>
     */
    private array $havingParams = [];

    /**
     * Distinct flag.
     */
    private bool $distinct = false;

    /**
     * Join clauses.
     *
     * @var array<int, array{type: JoinType, table: string, on: string|null}>
     */
    private array $joinClauses = [];

    /**
     * Parameter counter for unique parameter names.
     */
    private int $paramCounter = 0;

    /**
     * Create a new builder instance.
     *
     * @param LightQL $lightQL The LightQL instance.
     */
    public function __construct(
        private readonly LightQL $lightQL,
    ) {}

    /**
     * Set the table to query.
     *
     * @param string $table The table name.
     */
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Add WHERE conditions to the query.
     *
     * The `$condition` parameter can be a raw SQL condition or an associative array of column names and values.
     * If the value is an array, it will be treated as an IN condition.
     *
     * The array form of the condition allows specifying the operator using the column name as a key.
     * i.e., `$query->where(['age >=' => 18, 'status' => 'active'])`
     *
     * @param array<string, mixed>|string $condition The WHERE condition.
     * @param array $params The parameters to bind. Used for raw SQL conditions.
     */
    public function where(array|string $condition, array $params = []): self
    {
        if (is_string($condition)) {
            $this->whereConditions[] = [
                'sql' => $condition,
                'params' => $params,
            ];
            return $this;
        }

        $whereParts = [];
        $whereParams = [];

        foreach ($condition as $column => $value) {
            if (is_int($column)) {
                // Raw SQL condition
                $whereParts[] = $value;
                continue;
            }

            // Extract operator from the column name if present
            $operator = '=';
            $cleanColumn = $column;

            foreach (self::OPERATORS as $op) {
                if (str_ends_with($column, ' ' . $op)) {
                    $operator = $op;
                    $cleanColumn = substr($column, 0, - (strlen($op) + 1));
                    break;
                }
            }

            $paramName = $this->generateParamName($cleanColumn);

            if (is_array($value)) {
                // Handle IN clause
                $placeholders = [];
                foreach ($value as $i => $val) {
                    $inParamName = "{$paramName}_{$i}";
                    $placeholders[] = ":{$inParamName}";
                    $whereParams[$inParamName] = $val;
                }
                $whereParts[] = "{$cleanColumn} IN (" . implode(', ', $placeholders) . ")";
            } else {
                $whereParts[] = "{$cleanColumn} {$operator} :{$paramName}";
                $whereParams[$paramName] = $value;
            }
        }

        if (!empty($whereParts)) {
            $this->whereConditions[] = [
                'sql' => '(' . implode(' AND ', $whereParts) . ')',
                'params' => $whereParams,
            ];
        }

        return $this;
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column The column to order by.
     * @param SortOrder $order The sort order.
     */
    public function orderBy(string $column, SortOrder $order = SortOrder::ASC): self
    {
        $this->orderClauses[] = "{$column} {$order->value}";
        return $this;
    }

    /**
     * Add a LIMIT clause.
     *
     * @param int $offset The limit offset.
     * @param int $count The limit count.
     */
    public function limit(int $offset, int $count): self
    {
        $this->limitOffset = $offset;
        $this->limitCount = $count;
        return $this;
    }

    /**
     * Add a GROUP BY clause.
     *
     * @param string $column The column to group by.
     */
    public function groupBy(string $column): self
    {
        $this->groupByColumns[] = $column;
        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * @param string $condition The condition to filter by.
     * @param array $params The parameters to bind in the condition.
     */
    public function having(string $condition, array $params = []): self
    {
        $this->havingClause = $condition;
        $this->havingParams = $params;
        return $this;
    }

    /**
     * Add a DISTINCT clause.
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Add an INNER JOIN clause.
     *
     * @param string $table The table to join.
     * @param string $on The ON condition for the join.
     * @return Builder
     */
    public function innerJoin(string $table, string $on): self
    {
        $this->joinClauses[] = [
            'type' => JoinType::INNER,
            'table' => $table,
            'on' => $on
        ];
        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * @param string $table The table to join.
     * @param string $on The ON condition for the join.
     * @return Builder
     */
    public function leftJoin(string $table, string $on): self
    {
        $this->joinClauses[] = [
            'type' => JoinType::LEFT,
            'table' => $table,
            'on' => $on
        ];
        return $this;
    }

    /**
     * Add a RIGHT JOIN clause.
     *
     * @param string $table The table to join.
     * @param string $on The ON condition for the join.
     * @return Builder
     */
    public function rightJoin(string $table, string $on): self
    {
        $this->joinClauses[] = [
            'type' => JoinType::RIGHT,
            'table' => $table,
            'on' => $on
        ];
        return $this;
    }

    /**
     * Add a FULL JOIN clause.
     *
     * @param string $table The table to join.
     * @param string $on The ON condition for the join.
     * @return Builder
     */
    public function fullJoin(string $table, string $on): self
    {
        $this->joinClauses[] = [
            'type' => JoinType::FULL,
            'table' => $table,
            'on' => $on
        ];
        return $this;
    }

    /**
     * Add a CROSS JOIN clause.
     *
     * @param string $table The table to join.
     * @return Builder
     */
    public function crossJoin(string $table): self
    {
        $this->joinClauses[] = [
            'type' => JoinType::CROSS,
            'table' => $table,
            'on' => null
        ];
        return $this;
    }

    /**
     * Add a JOIN clause (defaults to INNER JOIN).
     *
     * @param string $table The table to join.
     * @param string $on The ON condition for the join.
     * @return Builder
     */
    public function join(string $table, string $on): self
    {
        return $this->innerJoin($table, $on);
    }

    /**
     * Build a SELECT query and return a pending query.
     *
     * @param array<int|string, string>|string $columns The columns to select.
     * @throws LightQLException
     */
    public function select(array|string $columns = '*'): PendingQuery
    {
        $result = $this->buildSelectQuery($columns);
        $pendingQuery = new PendingQuery($result['query'], $result['params'], $this->lightQL);
        $this->reset();
        return $pendingQuery;
    }

    /**
     * Count rows.
     *
     * @param array<int, string>|string $columns
     * @return int|array<string, int>
     * @throws LightQLException|QueryException
     */
    public function count(array|string $columns = '*'): int|array
    {
        $this->validateTable();

        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
        $boundParams = [];

        $groupByClause = $this->buildGroupByClause();
        $selectColumns = !empty($this->groupByColumns)
            ? implode(', ', $this->groupByColumns) . ', '
            : '';

        $query = "SELECT {$selectColumns}COUNT({$columnList}) AS lightql_count FROM {$this->table}";
        $query .= $this->buildJoinClause();
        $query .= $this->buildWhereClause($boundParams);
        $query .= $this->buildLimitClause();
        $query .= $groupByClause;

        $pendingQuery = new PendingQuery(trim($query), $boundParams, $this->lightQL);
        $hasGroupBy = !empty($this->groupByColumns);
        $firstGroupColumn = $this->groupByColumns[0] ?? null;
        $this->reset();

        $statement = $pendingQuery->execute();

        if (!$hasGroupBy) {
            $result = $statement->fetchFirst();
            return (int)$result['lightql_count'];
        }

        $results = [];
        while ($row = $statement->fetchFirst()) {
            $key = $row[$firstGroupColumn];
            $results[$key] = (int)$row['lightql_count'];
        }

        return $results;
    }

    /**
     * Insert a single row with parameter binding.
     *
     * @param array<string, mixed> $fieldsAndValues
     * @throws LightQLException
     */
    public function insert(array $fieldsAndValues): PendingQuery
    {
        $result = $this->buildInsertQuery($fieldsAndValues);
        $pendingQuery = new PendingQuery($result['query'], $result['params'], $this->lightQL);
        $this->reset();
        return $pendingQuery;
    }

    /**
     * Insert multiple rows with parameter binding.
     *
     * @param array<int, array<string, mixed>> $entries The array of items to insert into the table.
     * @param list<string> $columns The list of columns to insert.
     *
     * @throws LightQLException
     */
    public function insertMany(array $entries, array $columns = []): PendingQuery
    {
        $this->validateTable();

        if (empty($entries)) {
            throw new LightQLException("Cannot insert empty data");
        }

        if (empty($columns)) {
            $columns = array_keys($entries[0]);
        }

        $columnList = implode(', ', $columns);
        $valueSets = [];
        $params = [];

        foreach ($entries as $rowIndex => $row) {
            $placeholders = [];
            foreach ($row as $column => $value) {
                if (!in_array($column, $columns, true)) {
                    continue;
                }

                $paramName = "{$column}_{$rowIndex}";
                $placeholders[] = ":{$paramName}";
                $params[$paramName] = $value;
            }
            $valueSets[] = '(' . implode(', ', $placeholders) . ')';
        }

        $query = "INSERT INTO {$this->table} ({$columnList}) VALUES " . implode(', ', $valueSets);

        $pendingQuery = new PendingQuery($query, $params, $this->lightQL);
        $this->reset();
        return $pendingQuery;
    }

    /**
     * Update rows with parameter binding.
     *
     * @param array<string, mixed>|string $fieldsAndValues
     * @throws LightQLException
     */
    public function update(array|string $fieldsAndValues): PendingQuery
    {
        $result = $this->buildUpdateQuery($fieldsAndValues);
        $pendingQuery = new PendingQuery($result['query'], $result['params'], $this->lightQL);
        $this->reset();
        return $pendingQuery;
    }

    /**
     * Delete rows.
     *
     * @throws LightQLException
     */
    public function delete(): PendingQuery
    {
        $result = $this->buildDeleteQuery();
        $pendingQuery = new PendingQuery($result['query'], $result['params'], $this->lightQL);
        $this->reset();
        return $pendingQuery;
    }

    /**
     * Truncate table.
     *
     * @throws LightQLException
     */
    public function truncate(): PendingQuery
    {
        $result = $this->buildTruncateQuery();
        $pendingQuery = new PendingQuery($result['query'], $result['params'], $this->lightQL);
        $this->reset();
        return $pendingQuery;
    }

    /**
     * Reset query builder state.
     */
    public function reset(): self
    {
        $this->whereConditions = [];
        $this->orderClauses = [];
        $this->limitOffset = null;
        $this->limitCount = null;
        $this->groupByColumns = [];
        $this->havingClause = null;
        $this->havingParams = [];
        $this->distinct = false;
        $this->joinClauses = [];
        $this->paramCounter = 0;

        return $this;
    }

    /**
     * Build SELECT query string.
     *
     * @param array<int|string, string>|string $columns
     * @return array{query: string, params: array<string, mixed>}
     * @throws LightQLException
     */
    private function buildSelectQuery(array|string $columns): array
    {
        $this->validateTable();

        $columnList = $this->buildColumnList($columns);
        $boundParams = [];

        $query = 'SELECT' . ($this->distinct ? ' DISTINCT' : '') . " {$columnList} FROM {$this->table}";
        $query .= $this->buildJoinClause();
        $query .= $this->buildWhereClause($boundParams);
        $query .= $this->buildGroupByClause();
        $query .= $this->buildHavingClause($boundParams);
        $query .= $this->buildOrderByClause();
        $query .= $this->buildLimitClause();

        return [
            'query' => trim($query),
            'params' => $boundParams,
        ];
    }

    /**
     * Build INSERT query string.
     *
     * @param array<string, mixed> $fieldsAndValues
     * @return array{query: string, params: array<string, mixed>}
     * @throws LightQLException
     */
    private function buildInsertQuery(array $fieldsAndValues): array
    {
        $this->validateTable();

        if (empty($fieldsAndValues)) {
            throw new LightQLException("Cannot insert empty data");
        }

        $columns = array_keys($fieldsAndValues);
        $placeholders = [];
        $params = [];

        foreach ($fieldsAndValues as $column => $value) {
            $paramName = $this->generateParamName($column);
            $placeholders[] = ":{$paramName}";
            $params[$paramName] = $value;
        }

        $columnList = implode(', ', $columns);
        $placeholderList = implode(', ', $placeholders);

        $query = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholderList})";

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Build UPDATE query string.
     *
     * @param array<string, mixed>|string $fieldsAndValues
     * @return array{query: string, params: array<string, mixed>}
     * @throws LightQLException
     */
    private function buildUpdateQuery(array|string $fieldsAndValues): array
    {
        $this->validateTable();

        if (is_string($fieldsAndValues)) {
            $setClause = $fieldsAndValues;
            $params = [];
        } else {
            if (empty($fieldsAndValues)) {
                throw new LightQLException("Cannot update with empty data");
            }

            $setParts = [];
            $params = [];

            foreach ($fieldsAndValues as $column => $value) {
                $paramName = $this->generateParamName($column);
                $setParts[] = "{$column} = :{$paramName}";
                $params[$paramName] = $value;
            }

            $setClause = implode(', ', $setParts);
        }

        $boundParams = [];
        $query = "UPDATE {$this->table} SET {$setClause}";
        $query .= $this->buildWhereClause($boundParams);

        $allParams = array_merge($params, $boundParams);

        return [
            'query' => trim($query),
            'params' => $allParams,
        ];
    }

    /**
     * Build DELETE query string.
     *
     * @return array{query: string, params: array<string, mixed>}
     * @throws LightQLException
     */
    private function buildDeleteQuery(): array
    {
        $this->validateTable();

        $boundParams = [];
        $query = "DELETE FROM {$this->table}";
        $query .= $this->buildWhereClause($boundParams);

        return [
            'query' => trim($query),
            'params' => $boundParams,
        ];
    }

    /**
     * Build TRUNCATE query string.
     *
     * @return array{query: string, params: array<string, mixed>}
     * @throws LightQLException
     */
    private function buildTruncateQuery(): array
    {
        $this->validateTable();

        $query = match ($this->lightQL->dbms) {
            DBMS::SQLITE => "DELETE FROM {$this->table}",
            default => "TRUNCATE TABLE {$this->table}",
        };

        return [
            'query' => $query,
            'params' => [],
        ];
    }

    /**
     * Build a column list from array or string.
     *
     * @param array<int|string, string>|string $columns
     */
    private function buildColumnList(array|string $columns): string
    {
        if (is_string($columns)) {
            return $columns;
        }

        $parts = [];
        foreach ($columns as $column => $alias) {
            if (is_int($column)) {
                $parts[] = $alias;
            } else {
                $parts[] = "{$column} AS {$alias}";
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Build WHERE clause from conditions.
     *
     * @param array<string, mixed> $boundParams Reference to params array to populate
     */
    private function buildWhereClause(array &$boundParams): string
    {
        if (empty($this->whereConditions)) {
            return '';
        }

        $parts = [];
        foreach ($this->whereConditions as $condition) {
            $parts[] = $condition['sql'];
            $boundParams = array_merge($boundParams, $condition['params']);
        }

        return ' WHERE ' . implode(' OR ', $parts);
    }

    /**
     * Build ORDER BY clause.
     */
    private function buildOrderByClause(): string
    {
        if (empty($this->orderClauses)) {
            return '';
        }

        return ' ORDER BY ' . implode(', ', $this->orderClauses);
    }

    /**
     * Build LIMIT clause.
     */
    private function buildLimitClause(): string
    {
        if ($this->limitOffset === null || $this->limitCount === null) {
            return '';
        }

        return match ($this->lightQL->dbms) {
            DBMS::POSTGRESQL => " LIMIT {$this->limitCount} OFFSET {$this->limitOffset}",
            DBMS::MSSQL => " OFFSET {$this->limitOffset} ROWS FETCH NEXT {$this->limitCount} ROWS ONLY",
            default => " LIMIT {$this->limitOffset}, {$this->limitCount}",
        };
    }

    /**
     * Build GROUP BY clause.
     */
    private function buildGroupByClause(): string
    {
        if (empty($this->groupByColumns)) {
            return '';
        }

        return ' GROUP BY ' . implode(', ', $this->groupByColumns);
    }

    /**
     * Build HAVING clause.
     *
     * @param array<string, mixed> $boundParams Reference to a params array to populate
     */
    private function buildHavingClause(array &$boundParams): string
    {
        if ($this->havingClause === null) {
            return '';
        }

        $boundParams = array_merge($boundParams, $this->havingParams);
        return ' HAVING ' . $this->havingClause;
    }

    /**
     * Build JOIN clause from stored join clauses.
     *
     * @return string
     */
    private function buildJoinClause(): string
    {
        if (empty($this->joinClauses)) {
            return '';
        }

        $parts = [];
        foreach ($this->joinClauses as $join) {
            if ($join['type'] === JoinType::CROSS) {
                $parts[] = " {$join['type']->value} JOIN {$join['table']}";
            } else {
                $parts[] = " {$join['type']->value} JOIN {$join['table']} ON {$join['on']}";
            }
        }

        return implode('', $parts);
    }

    /**
     * Generate unique parameter name.
     *
     * @param string $column The name of the column from which the parameter is generated.
     * @return string The generated parameter name.
     */
    private function generateParamName(string $column): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
        return $safeName . '_' . ($this->paramCounter++);
    }

    /**
     * Validate that a table is set.
     *
     * @throws LightQLException
     */
    private function validateTable(): void
    {
        if (empty($this->table)) {
            throw new LightQLException("No table specified. Use from() method first.");
        }
    }
}
