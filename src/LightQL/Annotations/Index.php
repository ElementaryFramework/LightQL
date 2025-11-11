<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Annotations;

use Attribute;
use InvalidArgumentException;

/**
 * Index Attribute
 *
 * Used to define that a property should have an index created on it,
 * or when used on a class, defines an index on multiple columns.
 *
 * When used on a property:
 * - No parameters required (uses default naming)
 * - Optional name parameter to specify custom index name
 *
 * When used on a class:
 * - Requires columns array as first parameter
 * - Optional name parameter to specify custom index name
 *
 * This attribute should be used on a property with the {@see Column} attribute or
 * a class with the {@see Table} attribute to have effect.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Index
{
    /**
     * The columns that form the index.
     *
     * @var string[]
     */
    public array $columns;

    /**
     * The name of the index.
     *
     * @var string|null
     */
    public ?string $name;

    /**
     * Initialize the index attribute.
     *
     * @param string[] $columns The column names for the index (required when used on class, ignored when used on property).
     * @param string|null $name The name of the index.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $columns = [], ?string $name = null)
    {
        if ($name !== null && empty(trim($name))) {
            throw new InvalidArgumentException(self::class . ' name must be a non-empty string when provided');
        }

        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (!is_string($column) || empty(trim($column))) {
                    throw new InvalidArgumentException(self::class . ' requires non-empty column names');
                }
            }
        }

        $this->columns = $columns;
        $this->name = $name;
    }
}
