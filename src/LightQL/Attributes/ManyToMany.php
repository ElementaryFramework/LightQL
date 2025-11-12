<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

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
 * @template TEntity of IEntity The related entity class.
 * @template TPivotEntity of IEntity The pivot entity class.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class ManyToMany
{
    /**
     * Initialize the many-to-many attribute.
     *
     * @param class-string<TEntity> $entity The referenced entity class name.
     * @param class-string<TPivotEntity> $pivotTable The class name of the pivot entity.
     * @param string $foreignColumn The name of the column in the pivot table.
     * @param string|null $localColumn The name of the column in the entity table. Set to null to use the primary key of the entity table.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string  $entity,
        public string  $pivotTable,
        public string  $foreignColumn,
        public ?string $localColumn = null,
    )
    {
        if (empty($entity)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "entity" property');
        }

        if (empty($pivotTable)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "pivotTable" property');
        }

        if (empty($foreignColumn)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "foreignColumn" property');
        }
    }
}
