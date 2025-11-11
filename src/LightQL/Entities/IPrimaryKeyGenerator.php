<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

/**
 * IPrimaryKeyGenerator
 *
 * Generates unique primary key values for entities.
 *
 * @template TEntity of IEntity
 */
interface IPrimaryKeyGenerator
{
    /**
     * Generates a new, unique primary key value for the given entity.
     *
     * @param TEntity $entity The entity which we have to generate a key.
     */
    function generate(IEntity $entity): null|int|string|IPrimaryKey;
}
