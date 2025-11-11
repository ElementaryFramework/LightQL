<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

/**
 * Represents a relationship or association between entities.
 *
 * @template TEntity of IEntity The type of the related entity.
 * @template TRelated of TEntity|list<TEntity> = TEntity
 *
 * @property-read TRelated $related The related data. Can be a reference to a single entity or a collection of entities of the same type.
 */
class Relation
{
    public function __construct(
        private(set) IEntity|array $related
    )
    {
    }
}