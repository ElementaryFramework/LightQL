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

namespace ElementaryFramework\LightQL\Sessions;

use ElementaryFramework\LightQL\Entities\GenericEntity;
use ElementaryFramework\LightQL\Entities\IEntity;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ElementaryFramework\LightQL\Exceptions\FacadeException;
use ElementaryFramework\LightQL\LightQL;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;

final class GenericFacade implements IFacade
{
    /**
     * The persistence unit used by this instance.
     *
     * @var PersistenceUnit
     */
    private $_persistenceUnit;

    /**
     * The managed LightQL instance.
     *
     * @var LightQL
     */
    private $_lightql;

    /**
     * GenericFacade constructor.
     *
     * @param PersistenceUnit $persistenceUnit The persistence unit to use with this GenericFacade.
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
                "password" => $this->_persistenceUnit->getPassword(),
                "port" => $this->_persistenceUnit->getPort()
            )
        );
    }

    /**
     * Creates an entity.
     *
     * @param IEntity $entity The entity to create.
     *
     * @throws FacadeException
     * @throws EntityException
     */
    public function create(IEntity &$entity)
    {
        if ($entity instanceof GenericEntity) {
            $this->_lightql->beginTransaction();
            try {
                $this->_lightql
                    ->from($entity->getName())
                    ->insert($entity->getData());

                $this->_lightql->commit();
            } catch (\Exception $e) {
                $this->_lightql->rollback();

                throw new EntityException($e->getMessage());
            }
        } else {
            throw new FacadeException("Only GenericEntity instances can be used with GenericFacade.");
        }
    }

    /**
     * Edit an entity.
     *
     * @param IEntity $entity The entity to edit.
     * @throws EntityException
     * @throws FacadeException
     */
    public function edit(IEntity &$entity)
    {
        if ($entity instanceof GenericEntity) {
            $this->_lightql->beginTransaction();
            try {
                $this->_lightql
                    ->from($entity->getName())
                    ->where(array(
                        $entity->getPk() => $this->_lightql->quote($entity->get($entity->getPk()))
                    ))
                    ->update($entity->getData());

                $this->_lightql->commit();
            } catch (\Exception $e) {
                $this->_lightql->rollback();

                throw new EntityException($e->getMessage());
            }
        } else {
            throw new FacadeException("Only GenericEntity instances can be used with GenericFacade.");
        }
    }

    /**
     * Delete an entity.
     *
     * @param IEntity $entity The entity to delete.
     * @throws EntityException
     * @throws FacadeException
     */
    function delete(IEntity &$entity)
    {
        if ($entity instanceof GenericEntity) {
            $this->_lightql->beginTransaction();
            try {
                $this->_lightql
                    ->from($entity->getName())
                    ->where(array(
                        $entity->getPk() => $this->_lightql->quote($entity->get($entity->getPk()))
                    ))
                    ->delete();

                $this->_lightql->commit();
            } catch (\Exception $e) {
                $this->_lightql->rollback();

                throw new EntityException($e->getMessage());
            }
        } else {
            throw new FacadeException("Only GenericEntity instances can be used with GenericFacade.");
        }
    }

    /**
     * Find an entity.
     *
     * This method is unavailable. Use findGeneric instead.
     *
     * @param mixed $id The id of the entity to find
     *
     * @return IEntity
     *
     * @throws FacadeException
     */
    public function find($id): IEntity
    {
        throw new FacadeException("The \"find\" method is unavailable in GenericFacade, use \"findGeneric\" instead.");
    }

    /**
     * Find an entity.
     *
     * @param string $table The name of the table
     * @param string $pk The name of the column with primary key property
     * @param mixed $id The pk value of the entity to find
     *
     * @return IEntity
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function findGeneric(string $table, string $pk, $id): IEntity
    {
        $raw = $this->_lightql
            ->from($table)
            ->where(array($pk => $this->_lightql->quote($id)))
            ->selectFirst();

        return new GenericEntity($table, $pk, $raw);
    }

    /**
     * Find all entities.
     *
     * This method is unavailable. Use findGeneric instead.
     *
     * @return IEntity[]
     *
     * @throws FacadeException
     */
    public function findAll(): array
    {
        throw new FacadeException("The \"findAll\" method is unavailable in GenericFacade, use \"findAllGeneric\" instead.");
    }

    /**
     * Find all entities.
     *
     * @param string $table The name of the table
     * @param string $pk The name of the column with primary key property
     *
     * @return IEntity[]
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function findAllGeneric(string $table, string $pk): array
    {
        $rawEntities = $this->_lightql
            ->from($table)
            ->selectArray();

        return array_map(function ($raw) use ($table, $pk) {
            return new GenericEntity($table, $pk, $raw);
        }, $rawEntities);
    }

    /**
     * Find all entities in the given range.
     *
     * This method is unavailable. Use findRangeGeneric instead.
     *
     * @param int $start The starting offset.
     * @param int $length The number of entities to find.
     *
     * @return IEntity[]
     *
     * @throws FacadeException
     */
    public function findRange(int $start, int $length): array
    {
        throw new FacadeException("The \"findRange\" method is unavailable in GenericFacade, use \"findRangeGeneric\" instead.");
    }

    /**
     * Find all entities in the given range.
     *
     * @param string $table The name of the table
     * @param string $pk The name of the column with primary key property
     * @param int $start The starting offset.
     * @param int $length The number of entities to find.
     *
     * @return IEntity[]
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function findRangeGeneric(string $table, string $pk, int $start, int $length): array
    {
        $rawEntities = $this->_lightql
            ->from($table)
            ->limit($start, $length)
            ->selectArray();

        return array_map(function ($raw) use ($table, $pk) {
            return new GenericEntity($table, $pk, $raw);
        }, $rawEntities);
    }

    /**
     * Count the number of entities.
     *
     * This method is unavailable. Use countGeneric instead.
     *
     * @return int
     *
     * @throws FacadeException
     */
    public function count(): int
    {
        throw new FacadeException("The \"count\" method is unavailable in GenericFacade, use \"countGeneric\" instead.");
    }

    /**
     * Count the number of entities.
     *
     * @param string $table The name of the table
     *
     * @return int
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function countGeneric(string $table): int
    {
        return $this->_lightql
            ->from($table)
            ->count();
    }
}