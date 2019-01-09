<?php

/**
 * LightQL - The lightweight PHP ORM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @category  Library
 * @package   LightQL
 * @author    Axel Nana <ax.lnana@outlook.com>
 * @copyright 2018 Aliens Group, Inc.
 * @license   MIT <https://github.com/ElementaryFramework/LightQL/blob/master/LICENSE>
 * @version   1.0.0
 * @link      http://lightql.na2axl.tk
 */

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\Annotations\Annotations;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ElementaryFramework\LightQL\Exceptions\ValueValidatorException;
use ElementaryFramework\LightQL\LightQL;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;

/**
 * Entity Manager
 *
 * Manage all entities, using one same persistence unit.
 *
 * @category Entities
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Entities/EntityManager
 */
final class EntityManager
{
    /**
     * The persistence unit of this entity
     * manager.
     *
     * @var PersistenceUnit
     */
    private $_persistenceUnit;

    /**
     * The LightQL instance used by this
     * entity manager.
     *
     * @var LightQL
     */
    private $_lightql;

    /**
     * EntityManager constructor.
     *
     * @param PersistenceUnit $persistenceUnit The persistence unit to use in this manager.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function __construct(PersistenceUnit $persistenceUnit)
    {
        // Save the persistence unit
        $this->_persistenceUnit = $persistenceUnit;

        // Create a LightQL instance
        $this->_lightql = new LightQL(
            array(
                "dbms" => $this->_persistenceUnit->getDbms(),
                "database" => $this->_persistenceUnit->getDatabase(),
                "hostname" => $this->_persistenceUnit->getHostname(),
                "username" => $this->_persistenceUnit->getUsername(),
                "password" => $this->_persistenceUnit->getPassword()
            )
        );
    }

    /**
     * Finds an entity from the database.
     *
     * @param string $entityClass The class name of the entity to find.
     * @param mixed $id           The value of the primary key.
     *
     * @return array Raw data from database.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     * @throws \ReflectionException
     */
    public function find(string $entityClass, $id): array
    {
        $entityAnnotation = Annotations::ofClass($entityClass, "@entity");

        /** @var Entity $entity */
        $entity = new $entityClass;
        $columns = $entity->getColumns();

        $where = array();

        if ($id instanceof IPrimaryKey) {
            $pkClass = new \ReflectionClass($id);
            $properties = $pkClass->getProperties();

            /** @var \ReflectionProperty $property */
            foreach ($properties as $property) {
                if (Annotations::propertyHasAnnotation($id, $property->getName(), "@id") && Annotations::propertyHasAnnotation($id, $property->getName(), "@column")) {
                    $name = Annotations::ofProperty($id, $property->getName(), "@column")[0]->name;
                    $where[$name] = $this->_lightql->quote($id->{$property->getName()});
                }
            }
        } else {
            foreach ($columns as $property => $column) {
                if (count($where) === 0) {
                    if ($column->isPrimaryKey) {
                        $where[$column->getName()] = $this->_lightql->quote($id);
                    }
                } else break;
            }
        }

        $raw = $this->_lightql
            ->from($entityAnnotation[0]->table)
            ->where($where)
            ->selectFirst();

        return $raw;
    }

    /**
     * Persists an entity into the database.
     *
     * @param Entity &$entity The entity to create.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\EntityException
     */
    public function persist(Entity &$entity)
    {
        $entityAnnotation = Annotations::ofClass($entity, "@entity");

        $columns = $entity->getColumns();
        $fieldAndValues = array();

        $autoIncrementProperty = null;
        $idProperty = null;
        $valueValidator = null;

        if (Annotations::classHasAnnotation($entity, "@validator")) {
            $validatorAnnotation = Annotations::ofClass($entity, "@validator");

            if (\is_subclass_of($validatorAnnotation[0]->validator, IValueValidator::class)) {
                $validatorClass = new \ReflectionClass($validatorAnnotation[0]->validator);

                $valueValidator = $validatorClass->newInstance();
            } else {
                throw new EntityException("The value validator of this entity doesn't implement the IValueValidator interface.");
            }
        }

        /** @var Column $column */
        foreach ($columns as $property => $column) {
            if ($autoIncrementProperty === null && $column->isAutoIncrement) {
                $autoIncrementProperty = $property;
            }

            if ($idProperty === null && $column->isPrimaryKey) {
                $idProperty = $property;
            }
        }

        if ($idProperty !== null && ($autoIncrementProperty === null || $autoIncrementProperty !== $idProperty)) {
            // We have a non auto incremented primary key...
            // Check if the value is null or not set
            if ($entity->{$idProperty} === null || !isset($entity->{$idProperty})) {
                // We have a not defined non auto incremented primary key...
                // Check if the entity class has an @idGenerator annotation
                if (Annotations::classHasAnnotation($entity, "@idGenerator")) {
                    $idGeneratorAnnotation = Annotations::ofClass($entity, "@idGenerator");

                    if (\is_subclass_of($idGeneratorAnnotation[0]->generator, IEntityIdGenerator::class)) {
                        // We are safe !
                        // Generate an entity primary key using the generator
                        $idGeneratorClass = new \ReflectionClass($idGeneratorAnnotation[0]->generator);
                        /** @var IEntityIdGenerator $idGenerator */
                        $idGenerator = $idGeneratorClass->newInstance();

                        $entity->{$idProperty} = $idGenerator->generate($entity);
                    } else {
                        // Bad id generator implementation, throw an error
                        throw new EntityException("The id generator of this entity doesn't implement the IEntityIdGenerator interface.");
                    }

                } else {
                    // This will result to a SQL error, throw instead
                    throw new EntityException(
                        "Cannot persist an entity into the database. The entity primary key has no value, and has not the @autoIncrement annotation." .
                        " If the table primary key column is auto incremented, consider add the @autoIncrement annotation to the primary key class property." .
                        " If the table primary key column is not auto incremented, please give a value to the primary key class property before persist the entity, or use a @idGenerator annotation instead."
                    );
                }
            }
        }

        /** @var Column $column */
        foreach ($columns as $property => $column) {
            $value = $this->_lightql->quote($entity->get($column->getName()));

            if ($valueValidator !== null) {
                if ($valueValidator->validate($entityAnnotation[0]->table, $column->getName(), $value)) {
                    $fieldAndValues[$column->getName()] = $value;
                } else {
                    throw new ValueValidatorException($property);
                }
            } else {
                $fieldAndValues[$column->getName()] = $value;
            }
        }

        $this->_lightql->beginTransaction();
        try {
            $this->_lightql
                ->from($entityAnnotation[0]->table)
                ->insert($fieldAndValues);

            if ($autoIncrementProperty !== null) {
                $entity->$autoIncrementProperty = $this->_lightql->lastInsertID();
            }

            $this->_lightql->commit();
        } catch (\Exception $e) {
            $this->_lightql->rollback();

            throw new EntityException($e->getMessage());
        }
    }

    /**
     * Merges the entity in the database with the given one.
     *
     * @param Entity &$entity The entity to edit.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\EntityException
     */
    public function merge(Entity &$entity)
    {
        $entityAnnotation = Annotations::ofClass($entity, "@entity");

        $columns = $entity->getColumns();
        $fieldAndValues = array();
        $valueValidator = null;

        $where = array();

        $entityReflection = new \ReflectionClass($entity);
        $entityProperties = $entityReflection->getProperties();

        if (Annotations::classHasAnnotation($entity, "@validator")) {
            $validatorAnnotation = Annotations::ofClass($entity, "@validator");

            if (\is_subclass_of($validatorAnnotation[0]->validator, IValueValidator::class)) {
                $validatorClass = new \ReflectionClass($validatorAnnotation[0]->validator);

                $valueValidator = $validatorClass->newInstance();
            } else {
                throw new EntityException("The value validator of this entity doesn't implement the IValueValidator interface.");
            }
        }

        /** @var \ReflectionProperty $property */
        foreach ($entityProperties as $property) {
            $id = $entity->{$property->getName()};
            if ($id instanceof IPrimaryKey) {
                $propertyReflection = new \ReflectionClass($id);
                $propertyProperties = $propertyReflection->getProperties();

                foreach ($propertyProperties as $key) {
                    $name = Annotations::ofProperty($id, $key->getName(), "@column")[0]->name;
                    $where[$name] = $this->_lightql->quote($id->{$key->getName()});
                }

                break;
            }
        }

        /** @var Column $column */
        foreach ($columns as $property => $column) {
            $value = $this->_lightql->quote($entity->get($column->getName()));

            if ($valueValidator !== null) {
                if ($valueValidator->validate($entityAnnotation[0]->table, $column->getName(), $value)) {
                    $fieldAndValues[$column->getName()] = $value;
                } else {
                    throw new ValueValidatorException($property);
                }
            } else {
                $fieldAndValues[$column->getName()] = $value;
            }

            if ($column->isPrimaryKey) {
                $where[$column->getName()] = $this->_lightql->quote($entity->get($column->getName()));
            }
        }

        $this->_lightql->beginTransaction();
        try {
            $this->_lightql
                ->from($entityAnnotation[0]->table)
                ->where($where)
                ->update($fieldAndValues);

            $this->_lightql->commit();
        } catch (\Exception $e) {
            $this->_lightql->rollback();

            throw new EntityException($e->getMessage());
        }
    }

    /**
     * Removes an entity from the database.
     *
     * @param Entity &$entity The entity to delete.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\EntityException
     */
    public function delete(Entity &$entity)
    {
        $entityAnnotation = Annotations::ofClass($entity, "@entity");

        $columns = $entity->getColumns();
        $fieldAndValues = array();

        $where = array();
        $pk = array();

        $entityReflection = new \ReflectionClass($entity);
        $entityProperties = $entityReflection->getProperties();

        /** @var \ReflectionProperty $property */
        foreach ($entityProperties as $property) {
            $id = $entity->{$property->getName()};
            if ($id instanceof IPrimaryKey) {
                $propertyReflection = new \ReflectionClass($id);
                $propertyProperties = $propertyReflection->getProperties();

                foreach ($propertyProperties as $key) {
                    $name = Annotations::ofProperty($id, $key->getName(), "@column")[0]->name;
                    $where[$name] = $this->_lightql->quote($id->{$key->getName()});
                    $pk[] = $property->getName();
                }

                break;
            }
        }

        foreach ($columns as $property => $column) {
            if ($column->isPrimaryKey) {
                $where[$column->getName()] =  $this->_lightql->quote($entity->get($column->getName()));
                $pk[] = $property;
            }
        }

        $this->_lightql->beginTransaction();
        try {
            $this->_lightql
                ->from($entityAnnotation[0]->table)
                ->where($where)
                ->delete();

            if (count($pk) > 0) {
                foreach ($pk as $item) {
                    $entity->$item = null;
                }
            }

            $this->_lightql->commit();
        } catch (\Exception $e) {
            $this->_lightql->rollback();

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
        return $this->_lightql;
    }
}
