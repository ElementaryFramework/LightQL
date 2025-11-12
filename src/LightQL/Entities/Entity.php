<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\LightQL\Attributes\AttributeUtils;
use ElementaryFramework\LightQL\Attributes\Table;
use ElementaryFramework\LightQL\Attributes\Column as ColumnAttribute;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ReflectionClass;
use ReflectionException;

/**
 * Entity
 *
 * Object-Oriented Mapping of a database row.
 */
abstract class Entity implements IEntity
{
    /**
     * The reflection class of this entity.
     *
     * @var ReflectionClass|null
     */
    private ?ReflectionClass $reflection = null;

    /**
     * @var array<string, mixed> Stores unserialized data for each column of the entity.
     */
    private array $unserialized = [];

    /**
     * The raw data provided from the database.
     *
     * @var array<string, mixed>
     */
    protected array $raw = [];

    /**
     * The array of database columns of this entity.
     *
     * @var Column[]
     */
    private(set) array $columns = [];

    /**
     * {@inheritDoc}
     *
     * @throws EntityException
     * @throws ReflectionException
     */
    public function __construct(array $data = [])
    {
        if (!AttributeUtils::classHasAttribute($this, Table::class)) {
            throw new EntityException("Cannot create an entity without the Table attribute.");
        }

        $this->reflection = new ReflectionClass($this);
        $properties = $this->reflection->getProperties();

        $pkFound = false;

        foreach ($properties as $property) {
            if (AttributeUtils::propertyHasAttribute($this, $property->name, ColumnAttribute::class)) {
                $column = Column::ofEntityProperty($this, $property->name);

                $this->columns[$property->name] = $column;

                if ($column->isPrimaryKey && $pkFound) {
                    throw new EntityException("The entity has declared more than one primary keys. Consider using a class implementing the IPrimaryKey interface instead.");
                }

                $pkFound = $column->isPrimaryKey;
            }
        }

        $this->hydrate($data);
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $row): void
    {
        // Merge values
        $this->raw = [...$this->raw, ...$row];

        // Unserialize and populate columns
        foreach ($this->columns as $property => $column) {
            if (array_key_exists($column->name, $row)) {
                $this->unserialized[$column->name] = $column->unserialize($row[$column->name], $row);
            }

            if (!$column->isRelation) {
                if (array_key_exists($column->name, $this->raw)) {
                    $this->{$property} = $this->raw[$column->name];
                } elseif (!isset($this->{$property})) {
                    $this->{$property} = $column->default;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $column): mixed
    {
        // Try to get the value
        if ($this->exists($column)) {
            return $this->unserialized[$column];
        }

        // Try to get the property value
        // TODO
//        foreach ($this->columns as $property => $c) {
//            if ($c->name === $column && isset($this->{$property})) {
//                if ($this->{$property} instanceof Entity) {
//                    // Have to be a reference, not a collection
//                    if ($c->isManyToOne || $c->isManyToMany) {
//                        // Find the good property
//                        continue;
//                    } else if ($c->isOneToMany) {
//                        // Resolve the referenced column
//                        $referencedColumn = $this->_getMetadata($property, "@oneToMany", "referencedColumn");
//                        return $this->{$property}->get($referencedColumn);
//                    } else if ($c->isOneToOne) {
//                        // Resolve the referenced column
//                        $referencedColumn = $this->_getMetadata($property, "@oneToOne", "referencedColumn");
//                        return $this->{$property}->get($referencedColumn);
//                    }
//                } else {
//                    return $this->{$property};
//                }
//            }
//        }

        // The value definitively doesn't exist
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $column, $value): void
    {
        $this->hydrate([$column => $value]);
    }

    /**
     * Checks if the given column name exists in the raw values of this entity.
     *
     * @param string $column The column name to search.
     *
     * @return bool
     */
    private function exists(string $column): bool
    {
        return array_key_exists($column, $this->raw);
    }
}
