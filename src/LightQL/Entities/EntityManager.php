<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\LightQL\Attributes\AttributeUtils;
use ElementaryFramework\LightQL\Attributes\Id;
use ElementaryFramework\LightQL\Attributes\Column as ColumnAttribute;
use ElementaryFramework\LightQL\Attributes\IdGenerator;
use ElementaryFramework\LightQL\Attributes\Table;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ElementaryFramework\LightQL\Exceptions\LightQLException;
use ElementaryFramework\LightQL\Exceptions\QueryException;
use ElementaryFramework\LightQL\LightQL;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;
use Exception;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Entity Manager
 *
 * Manage all entities through a single persistence unit.
 */
final readonly class EntityManager
{
    /**
     * The LightQL instance used by this entity manager.
     */
    private LightQL $lightql;

    /**
     * EntityManager constructor.
     *
     * @param PersistenceUnit $persistenceUnit The persistence unit to use in this manager.
     *
     * @throws LightQLException
     */
    public function __construct(private PersistenceUnit $persistenceUnit)
    {
        // Create a LightQL instance
        $this->lightql = new LightQL(
            database: $this->persistenceUnit->database,
            hostname: $this->persistenceUnit->hostname,
            username: $this->persistenceUnit->username,
            password: $this->persistenceUnit->password,
            dbms: $this->persistenceUnit->dbms,
        );
    }

    /**
     * Finds an entity from the database.
     *
     * @param class-string<IEntity> $entityClass The class name of the entity to find.
     * @param string|int|IPrimaryKey $id The value of the primary key.
     *
     * @return array Raw data from database.
     *
     * @throws LightQLException
     * @throws ReflectionException
     * @throws QueryException
     */
    public function find(string $entityClass, string|int|IPrimaryKey $id): array
    {
        $table = AttributeUtils::ofClass($entityClass, Table::class);

        /** @var IEntity $entity */
        $entity = new $entityClass;
        $columns = $entity->getColumns();

        $where = [];

        if ($id instanceof IPrimaryKey) {
            $pkClass = new ReflectionClass($id);
            $properties = $pkClass->getProperties();

            foreach ($properties as $property) {
                if (AttributeUtils::propertyHasAttribute($id, $property->getName(), Id::class) && AttributeUtils::propertyHasAttribute($id, $property->getName(), ColumnAttribute::class)) {
                    $name = AttributeUtils::ofProperty($id, $property->getName(), ColumnAttribute::class)->name;
                    $where[$name] = $id->{$property->getName()};
                }
            }
        } else {
            foreach ($columns as $column) {
                if ($column->isPrimaryKey) {
                    $where[$column->name] = $id;
                    break;
                }
            }
        }

        return $this->lightql
            ->builder()
            ->from($table->table)
            ->where($where)
            ->select()
            ->execute()
            ->fetchFirst();
    }

    /**
     * Persists an entity into the database.
     *
     * @param IEntity $entity The entity to create.
     *
     * @throws EntityException|ReflectionException
     * @throws Throwable
     */
    public function persist(IEntity $entity): void
    {
        $table = AttributeUtils::ofClass($entity, Table::class);

        $columns = $entity->getColumns();
        $fieldAndValues = [];

        $autoIncrementProperty = null;
        $idProperty = null;

        foreach ($columns as $property => $column) {
            if ($autoIncrementProperty === null && $column->isAutoIncrement) {
                $autoIncrementProperty = $property;
            }

            if ($idProperty === null && $column->isPrimaryKey) {
                $idProperty = $property;
            }

            if ($autoIncrementProperty !== null && $idProperty !== null) {
                break;
            }
        }

        if ($idProperty !== null && ($autoIncrementProperty === null || $autoIncrementProperty !== $idProperty)) {
            // We have a non auto incremented primary key...
            // Check if the value is null or not set
            if (!isset($entity->{$idProperty})) {
                // We have a not defined non auto incremented primary key...
                // Check if the entity class has an @idGenerator annotation
                if (AttributeUtils::classHasAttribute($entity, IdGenerator::class)) {
                    $idGeneratorAttribute = AttributeUtils::ofClass($entity, IdGenerator::class);

                    if (is_subclass_of($idGeneratorAttribute->generator, IPrimaryKeyGenerator::class)) {
                        // We are safe !
                        // Generate an entity primary key using the generator
                        /** @var IPrimaryKeyGenerator $idGenerator */
                        $idGenerator = new ReflectionClass($idGeneratorAttribute->generator)->newInstance();

                        $entity->{$idProperty} = $idGenerator->generate($entity);
                    } else {
                        // Bad id generator implementation, throw an error
                        throw new EntityException("The id generator of this entity doesn't implement the IPrimaryKeyGenerator interface.");
                    }

                } else {
                    // This will result to a SQL error, throw instead
                    throw new EntityException(
                        "Cannot persist an entity into the database. The entity primary key has no value, and has not the AutoIncrement attribute." .
                        " If the table primary key column is auto incremented, consider adding the AutoIncrement attribute to the primary key class property." .
                        " If the table primary key column is not auto incremented, please give a value to the primary key class property before to persist the entity, or use a IdGenerator attribute instead."
                    );
                }
            }
        }

        foreach ($columns as $column) {
            $fieldAndValues[$column->name] = $entity->get($column->name);
        }

        $this->lightql->transaction(function () use ($entity, $fieldAndValues, $table, $autoIncrementProperty) {
            $statement = $this->lightql
                ->builder()
                ->from($table->table)
                ->insert($fieldAndValues)
                ->execute();

            if ($autoIncrementProperty !== null) {
                $entity->$autoIncrementProperty = $statement->getLastInsertId();
            }

        }, function (Throwable $e) {
            throw new EntityException($e->getMessage());
        });
    }

    /**
     * Merges the entity in the database with the given one.
     *
     * @param IEntity $entity The entity to edit.
     *
     * @throws EntityException
     * @throws ReflectionException
     */
    public function merge(IEntity $entity): void
    {
        $table = AttributeUtils::ofClass($entity, Table::class);

        $columns = $entity->getColumns();
        $fieldAndValues = [];

        $where = [];

        $entityReflection = new ReflectionClass($entity);
        $entityProperties = $entityReflection->getProperties();

        foreach ($entityProperties as $property) {
            $id = $entity->{$property->getName()};

            if ($id instanceof IPrimaryKey) {
                $propertyReflection = new ReflectionClass($id);
                $propertyProperties = $propertyReflection->getProperties();

                foreach ($propertyProperties as $key) {
                    $name = AttributeUtils::ofProperty($id, $key->getName(), ColumnAttribute::class)->name;
                    $where[$name] = $id->{$key->getName()};
                }

                break;
            }
        }

        foreach ($columns as $column) {
            $fieldAndValues[$column->name] = $entity->get($column->name);

            if ($column->isPrimaryKey) {
                $where[$column->name] = $entity->get($column->name);
            }
        }

        try {
            $this->lightql
                ->builder()
                ->from($table->table)
                ->where($where)
                ->update($fieldAndValues)
                ->execute();
        } catch (Exception $e) {
            throw new EntityException($e->getMessage());
        }
    }

    /**
     * Removes an entity from the database.
     *
     * @param IEntity $entity The entity to delete.
     *
     * @throws EntityException
     * @throws ReflectionException
     */
    public function delete(IEntity $entity): void
    {
        $table = AttributeUtils::ofClass($entity, Table::class);

        $columns = $entity->getColumns();

        $where = [];
        $pk = [];

        $entityReflection = new ReflectionClass($entity);
        $entityProperties = $entityReflection->getProperties();

        foreach ($entityProperties as $property) {
            $id = $entity->{$property->getName()};
            if ($id instanceof IPrimaryKey) {
                $propertyReflection = new ReflectionClass($id);
                $propertyProperties = $propertyReflection->getProperties();

                foreach ($propertyProperties as $key) {
                    $name = AttributeUtils::ofProperty($id, $key->getName(), ColumnAttribute::class)->name;
                    $where[$name] = $id->{$key->getName()};
                    $pk[] = $property->getName();
                }

                break;
            }
        }

        foreach ($columns as $property => $column) {
            if ($column->isPrimaryKey) {
                $where[$column->name] = $entity->get($column->name);
                $pk[] = $property;
            }
        }

        try {
            $this->lightql
                ->builder()
                ->from($table->table)
                ->where($where)
                ->delete()
                ->execute();

            if (!empty($pk)) {
                foreach ($pk as $item) {
                    $entity->$item = null;
                }
            }
        } catch (Exception $e) {
            throw new EntityException($e->getMessage());
        }
    }

    /**
     * Gets the LightQL instance associated
     * to this entity manager.
     *
     * @return LightQL
     */
    public function getLightQL(): LightQL
    {
        return $this->lightql;
    }
}
