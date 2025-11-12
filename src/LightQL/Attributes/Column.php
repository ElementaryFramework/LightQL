<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * Column Attribute
 *
 * Used to mark a property as a table column.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Column
{
    /**
     * Initialize the column attribute.
     *
     * @param string $name The name of the column.
     * @param string|null $type The type of the column.
     * @param mixed $default The default value of the column.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string  $name,
        public ?string $type = null,
        public mixed   $default = null
    )
    {
        if (empty($name)) {
            throw new InvalidArgumentException(self::class . " requires a non-empty \"name\" property");
        }
    }
}
