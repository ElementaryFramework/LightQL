<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Query;

use ElementaryFramework\LightQL\Exceptions\LightQLException;
use ElementaryFramework\LightQL\Exceptions\QueryException;
use ElementaryFramework\LightQL\LightQL;
use PDO;
use PDOException;
use Stringable;

/**
 * Represents a pending query that can be executed or inspected.
 */
final readonly class PendingQuery implements Stringable
{
    /**
     * Create a new pending query instance.
     *
     * @param string $queryString The SQL query string
     * @param array<string, mixed> $parameters The bound parameters
     * @param LightQL $lightQL The LightQL instance for execution
     */
    public function __construct(
        private string  $queryString,
        private array   $parameters,
        private LightQL $lightQL,
    ) {}

    /**
     * Execute the query and return the QueryResult.
     *
     * @throws QueryException|LightQLException
     */
    public function execute(): QueryResult
    {
        try {
            $statement = $this->lightQL->prepare($this->queryString);

            foreach ($this->parameters as $param => $value) {
                $type = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };

                $statement->bindValue(":{$param}", $value, $type);
            }

            $statement->execute();

            return new QueryResult($statement, $this->queryString, $this->lightQL);
        } catch (PDOException $e) {
            throw new QueryException(
                "Query execution failed: " . $e->getMessage() . " | Query: {$this->queryString}"
            );
        }
    }

    /**
     * Get the query string.
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Get the query string (magic method).
     */
    public function __toString(): string
    {
        return $this->queryString;
    }

    /**
     * Get the value of a specific parameter.
     *
     * @param string $name The parameter name.
     *
     * @return mixed
     */
    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Get all parameters.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Check if a parameter exists.
     *
     * @param string $name The parameter name.
     */
    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }
}
