<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use ElementaryFramework\LightQL\Entities\IEntity;
use InvalidArgumentException;

/**
 * One-To-Many Attribute
 *
 * Used to mark that a property is in a one-to-many relation with another.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @template TEntity of IEntity The related entity class.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class OneToMany
{
    /**
     * Initialize the one-to-many attribute.
     *
     * @param class-string<TEntity> $entity The referenced entity class name.
     * @param string $mappedBy The name of the property in the referenced entity that maps back to this entity.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $entity,
        public string $mappedBy
    )
    {
        if (empty($entity)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "entity" property');
        }

        if (empty($mappedBy)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "mappedBy" property');
        }
    }
}
