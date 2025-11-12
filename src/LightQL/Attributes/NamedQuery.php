<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * Named Query Attribute
 *
 * Used to create named queries associated with an entity.
 *
 * This attribute should be used on a class with the {@see Table} attribute to have effect.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class NamedQuery
{
    /**
     * Initialize the named query attribute.
     *
     * @param string $name The query's name.
     * @param string $query The SQL query.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $name,
        public string $query
    )
    {
        if (empty($name)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty name property.');
        }

        if (empty($query)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty query property.');
        }
    }
}
