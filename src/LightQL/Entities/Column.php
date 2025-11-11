<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

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
    public bool $isAutoIncrement;

    /**
     * Defines if the column is a primary key.
     */
    public bool $isPrimaryKey;

    /**
     * Defines if the column has the UNIQUE constraint.
     */
    public bool $isUniqueKey;

    /**
     * Defines if the column is in a one-to-many relation with another.
     */
    public bool $isOneToMany;

    /**
     * Defines if the column is in a many-to-one relation with another.
     */
    public bool $isManyToOne;

    /**
     * Defines if the column is in a many-to-many relation with another.
     */
    public bool $isManyToMany;

    /**
     * Defines if the column is in a one-to-one relation with another.
     */
    public bool $isOneToOne;

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
}
