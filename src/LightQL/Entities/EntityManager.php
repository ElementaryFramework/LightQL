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
 * @version   GIT: 0.0.1
 * @link      http://lightql.na2axl.tk
 */

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\Annotations\Annotations;
use ElementaryFramework\LightQL\LightQL;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;

/**
 * Entity Manager
 *
 * Manage all entities, using one same persistence unit.
 *
 * @final
 * @category Entities
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Entities/PersistenceUnit
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
     * @param mixed  $id          The value of the primary key.
     *
     * @return Entity
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function find(string $entityClass, $id): Entity
    {
        $entityAnnotation = Annotations::ofClass($entityClass, "@entity");

        $entity = new $entityClass;
        $columns = $entity->getColumns();

        $where = array();

        foreach ($columns as $property => $column) {
            if (count($where) === 0) {
                if ($column->isPrimaryKey) {
                    $where[$column->getName()] = $this->_lightql->quote($id);
                }
            } else break;
        }

        $raw = $this->_lightql
            ->from($entityAnnotation[0]->table)
            ->where($where)
            ->selectFirst();

        $entity->hydrate($raw);

        return$entity;
    }

    /**
     * Persists an entity into the database.
     *
     * @param Entity $entity The entity to create.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function persist(Entity &$entity)
    {
        $entityAnnotation = Annotations::ofClass($entity, "@entity");

        $columns = $entity->getColumns();
        $fieldAndValues = array();

        $autoIncrementProperty = null;

        foreach ($columns as $property => $column) {
            $fieldAndValues[$column->getName()] = $this->_lightql->quote($entity->$property);

            if ($autoIncrementProperty === null && $column->isAutoIncrement) {
                $autoIncrementProperty = $property;
            }
        }

        $this->_lightql
            ->from($entityAnnotation[0]->table)
            ->insert($fieldAndValues);

        if ($autoIncrementProperty !== null) {
            $entity->$autoIncrementProperty = $this->_lightql->lastInsertID();
        }
    }

    /**
     * Merges the entity in the database with the given one.
     *
     * @param Entity $entity The entity to edit.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function merge(Entity &$entity)
    {
        $entityAnnotation = Annotations::ofClass($entity, "@entity");

        $columns = $entity->getColumns();
        $fieldAndValues = array();

        $where = array();

        foreach ($columns as $property => $column) {
            $fieldAndValues[$column->getName()] = $this->_lightql->quote($entity->$property);

            if ($column->isPrimaryKey) {
                $where[$column->getName()] =  $this->_lightql->quote($entity->get($column->getName()));
            }
        }

        $this->_lightql
            ->from($entityAnnotation[0]->table)
            ->where($where)
            ->update($fieldAndValues);
    }

    /**
     * Removes an entity from the database.
     *
     * @param Entity $entity The entity to delete.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function delete(Entity &$entity)
    {
        $entityAnnotation = Annotations::ofClass($entity, "@entity");

        $columns = $entity->getColumns();
        $fieldAndValues = array();

        $where = array();
        $pk = array();

        foreach ($columns as $property => $column) {
            $fieldAndValues[$column->getName()] = $this->_lightql->quote($entity->$property);

            if ($column->isPrimaryKey) {
                $where[$column->getName()] =  $this->_lightql->quote($entity->$property);
                $pk[] = $property;
            }
        }

        $this->_lightql
            ->from($entityAnnotation[0]->table)
            ->where($where)
            ->delete();

        if (count($pk) > 0) {
            foreach ($pk as $item) {
                $entity->$item = null;
            }
        }
    }

    /**
     * @return LightQL
     */
    public function getLightQL(): LightQL
    {
        return $this->_lightql;
    }
}
