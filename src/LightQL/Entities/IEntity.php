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
     * Populates data in the entity.
     *
     * @param array<string, mixed> $data The raw database data.
     */
    public function hydrate(array $data): void;

    /**
     * Gets the raw value of a table column.
     *
     * @param string $column The table column name.
     */
    public function get(string $column): mixed;

    /**
     * Sets the raw value of a table column.
     *
     * @param string $column The table column name.
     * @param mixed $value The table column value.
     */
    public function set(string $column, mixed $value): void;

    /**
     * Gets the entity columns.
     *
     * @return array<string, Column>
     */
    public function getColumns(): array;
}
