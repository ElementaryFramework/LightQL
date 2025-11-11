<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

/**
 * Represents a relationship or association between entities.
 *
 * @template TEntity of IEntity
 * @template TRelated of TEntity|list<TEntity> = TEntity
 */
class Relation
{
    public function __construct(
        public private(set) IEntity|array $related
    )
    {
    }
}