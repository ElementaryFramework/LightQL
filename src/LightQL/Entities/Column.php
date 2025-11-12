<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\LightQL\Attributes\AttributeUtils;
use ElementaryFramework\LightQL\Attributes\AutoIncrement;
use ElementaryFramework\LightQL\Attributes\Column as ColumnAttribute;
use ElementaryFramework\LightQL\Attributes\Id;
use ElementaryFramework\LightQL\Attributes\ManyToMany;
use ElementaryFramework\LightQL\Attributes\ManyToOne;
use ElementaryFramework\LightQL\Attributes\OneToMany;
use ElementaryFramework\LightQL\Attributes\OneToOne;
use ElementaryFramework\LightQL\Attributes\Size;
use ElementaryFramework\LightQL\Attributes\Unique;
use ReflectionException;

/**
 * Column
 *
 * Describe a single table's column from the database.
 *
 * @template T
 */
class Column
{
    /**
     * The column name.
     */
    private(set) string $name;

    /**
     * The column type.
     */
    private(set) string $type;

    /**
     * The column size (if any).
     *
     * @var array{int, int}
     */
    private(set) array $size;

    /**
     * The column's default value.
     *
     * @var T
     */
    private(set) mixed $default = null;

    /**
     * Defines if the column has the AUTO_INCREMENT constraint.
     */
    private(set) bool $isAutoIncrement;

    /**
     * Defines if the column is a primary key.
     */
    private(set) bool $isPrimaryKey;

    /**
     * Defines if the column has the UNIQUE constraint.
     */
    private(set) bool $isUniqueKey;

    /**
     * Defines if the column is in a one-to-many relation with another.
     */
    private(set) bool $isOneToMany;

    /**
     * Defines if the column is in a many-to-one relation with another.
     */
    private(set) bool $isManyToOne;

    /**
     * Defines if the column is in a many-to-many relation with another.
     */
    private(set) bool $isManyToMany;

    /**
     * Defines if the column is in a one-to-one relation with another.
     */
    private(set) bool $isOneToOne;

    /**
     * Checks whether the column is a relation.
     */
    public bool $isRelation {
        get => $this->isManyToMany || $this->isOneToMany || $this->isManyToOne || $this->isOneToOne;
    }

    /**
     * Create a new instance of the table column descriptor.
     *
     * @param string $name The column's name.
     * @param string $type The column's type.
     * @param array{int, int} $size The array of sizes containing (min, max) values only.
     * @param null|T $default The default value of the column.
     */
    public function __construct(string $name, string $type, array $size, mixed $default = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->default = $default;
    }

    /**
     * Create a new column from the metadata of the given entity property.
     *
     * @template TClass of object The entity class.
     *
     * @param class-string<TClass>|TClass $entity The entity class name or instance.
     * @param string $property The name of the property.
     *
     * @throws ReflectionException
     *
     * @internal
     */
    public static function ofEntityProperty(string|object $entity, string $property): self
    {
        $columnAttribute = AttributeUtils::ofProperty($entity, $property, COlumnAttribute::class);

        try {
            $sizeAttribute = AttributeUtils::ofProperty($entity, $property, Size::class);
        } catch (ReflectionException) {
            $sizeAttribute = null;
        }

        $name = $columnAttribute->name;
        $type = $columnAttribute->type ?? '';
        $size = [
            $sizeAttribute?->min,
            $sizeAttribute?->max,
        ];

        $column = new self($name, $type, $size, $columnAttribute->default);

        $column->isPrimaryKey = AttributeUtils::propertyHasAttribute($entity, $property, Id::class);
        $column->isUniqueKey = $column->isPrimaryKey || AttributeUtils::propertyHasAttribute($entity, $property, Unique::class);
        $column->isAutoIncrement = AttributeUtils::propertyHasAttribute($entity, $property, AutoIncrement::class);
        $column->isManyToMany = AttributeUtils::propertyHasAttribute($entity, $property, ManyToMany::class);
        $column->isManyToOne = AttributeUtils::propertyHasAttribute($entity, $property, ManyToOne::class);
        $column->isOneToMany = AttributeUtils::propertyHasAttribute($entity, $property, OneToMany::class);
        $column->isOneToOne = AttributeUtils::propertyHasAttribute($entity, $property, OneToOne::class);

        return $column;
    }
}
