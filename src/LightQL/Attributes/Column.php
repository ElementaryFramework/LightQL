<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * Column Attribute
 *
 * Used to mark a property as a table column.
 *
 * @property-read string $name The name of the column.
 * @property-read string|null $type The type of the column.
 * @property-read mixed $default The default value of the column.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * Initialize the column attribute.
     *
     * @param string $name The name of the column
     * @param string|null $type The type of the column
     * @param mixed $default The default value of the column
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $type = null,
        public readonly mixed $default = null
    ) {
        if (empty($name)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"name\" property");
        }
    }
}
