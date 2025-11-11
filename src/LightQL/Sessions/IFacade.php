<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Sessions;

use ElementaryFramework\LightQL\Entities\IEntity;
use ElementaryFramework\LightQL\Entities\IPrimaryKey;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ElementaryFramework\LightQL\Exceptions\FacadeException;
use ElementaryFramework\LightQL\Exceptions\LightQLException;
use ElementaryFramework\LightQL\Exceptions\QueryException;

/**
 * IFacade interface.
 *
 * Provide methods for all entity facades.
 *
 * @template TEntity of IEntity
 */
interface IFacade
{
    /**
     * Creates an entity.
     *
     * @param TEntity $entity The entity to create.
     *
     * @throws FacadeException
     * @throws EntityException
     * @throws LightQLException
     */
    function create(IEntity &$entity): void;

    /**
     * Edit an entity.
     *
     * @param TEntity $entity The entity to edit.
     *
     * @throws FacadeException
     * @throws EntityException
     * @throws LightQLException
     */
    function update(IEntity &$entity): void;

    /**
     * Delete an entity.
     *
     * @param TEntity $entity The entity to delete.
     *
     * @throws FacadeException
     * @throws EntityException
     * @throws LightQLException
     */
    function delete(IEntity &$entity): void;

    /**
     * Find an entity.
     *
     * @param int|string|IPrimaryKey $id The id of the entity to find
     * @return TEntity
     *
     * @throws FacadeException
     * @throws EntityException
     * @throws LightQLException
     */
    function retrieve(int|string|IPrimaryKey $id): IEntity;

    /**
     * Find all entities.
     *
     * @return list<TEntity>
     *
     * @throws FacadeException
     * @throws EntityException
     * @throws LightQLException
     * @throws QueryException
     */
    function list(): array;

    /**
     * Find all entities in the given range.
     *
     * @param int $start The starting offset.
     * @param int $length The number of entities to find.
     *
     * @return list<TEntity>
     *
     * @throws FacadeException
     * @throws EntityException
     * @throws LightQLException
     * @throws QueryException
     */
    function range(int $start, int $length): array;

    /**
     * Count the number of entities.
     *
     * @return int
     *
     * @throws FacadeException
     * @throws EntityException
     * @throws LightQLException
     * @throws QueryException
     */
    function count(): int;
}