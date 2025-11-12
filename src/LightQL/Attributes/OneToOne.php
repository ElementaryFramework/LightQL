<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use ElementaryFramework\LightQL\Entities\IEntity;
use InvalidArgumentException;

/**
 * One-To-One Attribute
 *
 * Used to mark that a property is in a one-to-one relation with another.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @template TEntity of IEntity The related entity class.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class OneToOne
{
    /**
     * Initialize the one-to-one attribute.
     *
     * @param class-string<TEntity> $entity The referenced entity class name.
     * @param string|null $referencedColumn The name of the referenced column.
     * @param string|null $mappedBy The name of the property in the referenced entity that maps this relation.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string  $entity,
        public ?string $referencedColumn = null,
        public ?string $mappedBy = null
    )
    {
        if (empty($entity)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "entity" property');
        }

        if ($referencedColumn === null && $mappedBy === null) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "referencedColumn" or "mappedBy" property');
        }

        if ($referencedColumn !== null && $mappedBy !== null) {
            throw new InvalidArgumentException(self::class . ' requires either a "referencedColumn" or "mappedBy" property, not both');
        }

        if ($referencedColumn !== null && empty($referencedColumn)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "referencedColumn" property');
        }

        if ($mappedBy !== null && empty($mappedBy)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "mappedBy" property');
        }
    }
}
