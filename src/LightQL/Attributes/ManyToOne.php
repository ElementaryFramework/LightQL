<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use ElementaryFramework\LightQL\Entities\IEntity;
use InvalidArgumentException;

/**
 * Many-To-One Attribute
 *
 * Used to mark that a property is in a many-to-one relation with another entity.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @property-read class-string<IEntity>|string $entity The referenced entity class name or table name.
 * @property-read string $referencedColumn The name of the referenced column.
 * @property-read string|null $localColumn The name of the local column. If null, the primary key of the entity is used.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne
{
    /**
     * Initialize the many-to-one attribute.
     *
     * @param class-string<IEntity>|string $entity The referenced entity class name
     * @param string|null $localColumn The name of the local column. If null, the primary key of the entity is used.
     * @param string $referencedColumn The name of the referenced column
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $entity,
        public readonly string $referencedColumn,
        public readonly ?string $localColumn = null,
    ) {
        if (empty($entity)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"entity\" property");
        }

        if (empty($referencedColumn)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"referencedColumn\" property");
        }
    }
}
