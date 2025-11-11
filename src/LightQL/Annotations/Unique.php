<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Annotations;

use Attribute;
use InvalidArgumentException;

/**
 * Unique Attribute
 *
 * Used to define that a property represents a unique column in the table,
 * or when used on a class, defines a unique constraint on multiple columns.
 *
 * When used on a property:
 * - No parameters required (uses default naming)
 * - Optional name parameter to specify custom constraint name
 *
 * When used on a class:
 * - Requires columns array as first parameter
 * - Optional name parameter to specify custom constraint name
 *
 * This attribute should be used on a property with the {@see Column} attribute or
 * a class with the {@see Table} attribute to have effect.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Unique
{
    /**
     * The columns that form the unique constraint.
     *
     * @var string[]
     */
    public readonly array $columns;

    /**
     * The name of the unique constraint.
     *
     * @var string|null
     */
    public readonly ?string $name;

    /**
     * Initialize the unique attribute.
     *
     * @param string[] $columns The column names for the unique constraint (required when used on class, ignored when used on property).
     * @param string|null $name The name of the unique constraint.
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
