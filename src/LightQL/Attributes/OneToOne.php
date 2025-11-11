<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * One-To-One Attribute
 *
 * Used to mark that a property is in a one-to-one relation with another.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @property-read class-string<IEntity>|string $entity The referenced entity class name or table name.
 * @property-read string|null $referencedColumn The name of the referenced column.
 * @property-read string|null $mappedBy The name of the property in the referenced entity that maps this relation.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToOne
{
    /**
     * Initialize the one-to-one attribute.
     *
     * @param class-string<IEntity>|string $entity The referenced entity class name or table name.
     * @param string|null $referencedColumn The name of the referenced column.
     * @param string|null $mappedBy The name of the property in the referenced entity that maps this relation.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $entity,
        public readonly ?string $referencedColumn = null,
        public readonly ?string $mappedBy = null
    ) {
        if (empty($entity)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"entity\" property");
        }

        if ($referencedColumn === null && $mappedBy === null) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"referencedColumn\" or \"mappedBy\" property");
        }

        if ($referencedColumn !== null && $mappedBy !== null) {
            throw new InvalidArgumentException(self::class . " requires either a \"referencedColumn\" or \"mappedBy\" property, not both");
        }

        if ($referencedColumn !== null && empty($referencedColumn)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"referencedColumn\" property");
        }

        if ($mappedBy !== null && empty($mappedBy)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"mappedBy\" property");
        }
    }
}
