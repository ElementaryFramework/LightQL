<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

/**
 * IEntity
 *
 * Provides a base interface for all entity implementations. It is not recommended to use this interface
 * directly. Instead, you should use the {@see Entity} class as a base class for your entities.
 */
interface IEntity
{
    /**
     * @var array<string, Column> Gets the entity columns.
     */
    public array $columns {
        get;
    }

    /**
     * Create a new instance of an entity.
     *
     * @param array $row The raw table row data.
     */
    public function __construct(array $row = []);

    /**
     * Populates data in the entity.
     *
     * @param array<string, mixed> $row The raw database data.
     */
    public function hydrate(array $row): void;

    /**
     * Gets the value of a table column.
     *
     * The returned value is ensured to be transformed by the applied {@see ITransformer} if any.
     *
     * @param string $column The table column name.
     */
    public function get(string $column): mixed;

    /**
     * Sets the value of a table column.
     *
     * The given value will be transformed by the applied {@see ITransformer} if needed.
     *
     * @param string $column The table column name.
     * @param mixed $value The table column value.
     */
    public function set(string $column, mixed $value): void;
}
