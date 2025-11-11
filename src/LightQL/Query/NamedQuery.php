<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Query;

use ElementaryFramework\LightQL\Entities\EntityManager;
use ElementaryFramework\LightQL\Entities\IEntity;
use ElementaryFramework\LightQL\Exceptions\QueryException;
use Exception;
use PDOStatement;
use ReflectionClass;
use ReflectionException;

/**
 * Query
 *
 * Manage, run and get results from named queries.
 */
final class NamedQuery
{
    /**
     * The entity manager running this query.
     */
    private EntityManager $entityManager;

    /**
     * The reflection class of the managed entity.
     */
    private ReflectionClass $entityReflection;

    /**
     * The named query string.
     */
    private string $namedQuery;

    /**
     * Query parameters.
     *
     * @var array<string, mixed>
     */
    private array $_parameters = [];

    /**
     * The query executed by this instance.
     *
     * @var PDOStatement|null
     */
    private ?PDOStatement $query = null;

    /**
     * Query constructor.
     *
     * @param EntityManager $manager The entity manager instance.
     */
    public function __construct(EntityManager $manager)
    {
        $this->entityManager = $manager;
    }

    /**
     * Sets the reflection class of the managed entity.
     *
     * @param class-string<IEntity>|IEntity $entity The managed entity reflection class instance.
     * @throws ReflectionException
     */
    public function setEntity(string|IEntity $entity): self
    {
        $this->entityReflection = new ReflectionClass($entity);

        return $this;
    }

    /**
     * Sets the named query to execute.
     *
     * @param string $query The named query.
     */
    public function setQuery(string $query): self
    {
        $this->namedQuery = $query;

        return $this;
    }

    /**
     * Defines the value of one of query parameters.
     *
     * @param string|array $name  The name of the parameter in the query, or the array of parameters.
     * @param mixed  $value The value of this parameter.
     */
    public function setParam(string|array $name, mixed $value = null): self
    {
        if (is_string($name)) {
            $this->_parameters[$name] = $value;
        } else {
            $this->_parameters = $name;
        }

        return $this;
    }

    /**
     * Executes the query.
     *
     * @return bool
     * @throws QueryException
     */
    public function run(): bool
    {
        try {
            $this->query = $this->entityManager->getLightQL()->prepare($this->namedQuery);

            foreach ($this->_parameters as $name => $value) {
                $this->query->bindValue($name, $value);
            }

            return $this->query->execute();
        } catch (Exception $e) {
            throw new QueryException($e->getMessage());
        }
    }

    /**
     * Returns the set of results after the execution of the query.
     *
     * @return IEntity[]
     *
     * @throws QueryException
     * @throws ReflectionException
     */
    public function getResults(): array
    {
        if ($this->query === null) {
            throw new QueryException("Cannot get results, have you ran the query?");
        }

        return array_map(function ($item) {
            return $this->entityReflection->newInstance($item);
        }, $this->query->fetchAll());
    }

    /**
     * Returns the first result of the set after the execution
     * of the query.
     *
     * @return IEntity|null
     *
     * @throws QueryException
     * @throws ReflectionException
     */
    public function getFirstResult(): ?IEntity
    {
        $results = $this->getResults();
        return count($results) > 0 ? $results[0] : null;
    }
}
