<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Annotations;

use Attribute;
use ElementaryFramework\LightQL\Entities\IEntity;
use InvalidArgumentException;

/**
 * Many-To-Many Attribute
 *
 * Used to mark that a property is in a many-to-many relation with another entity.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @property-read class-string<IEntity> $entity The referenced entity class name.
 * @property-read class-string<IEntity> $pivotTable The name of the pivot table, or the class name of the pivot entity.
 * @property-read string $foreignColumn The name of the column in the pivot table.
 * @property-read string|null $localColumn The name of the column in the entity table. Set to null to use the primary key of the entity table.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany
{
    /**
     * Initialize the many-to-many attribute.
     *
     * @param class-string<IEntity> $entity The referenced entity class name.
     * @param class-string<IEntity> $pivotTable The name of the pivot table, or the class name of the pivot entity.
     * @param string $foreignColumn The name of the column in the pivot table.
     * @param string|null $localColumn The name of the column in the entity table. Set to null to use the primary key of the entity table.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string  $entity,
        public readonly string  $pivotTable,
        public readonly string  $foreignColumn,
        public readonly ?string $localColumn = null,
    )
    {
        if (empty($entity)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"entity\" property");
        }

        if (empty($pivotTable)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"pivotTable\" property");
        }

        if (empty($foreignColumn)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"foreignColumn\" property");
        }
    }
}
