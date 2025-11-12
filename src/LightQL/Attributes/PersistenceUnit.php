<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * Persistence Unit Attribute
 *
 * Used to specify the persistence unit to use for an entity manager.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class PersistenceUnit
{
    /**
     * Initialize the persistence unit attribute.
     *
     * @param string $name The name of the persistence unit.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $name
    )
    {
        if (empty($name)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "name" property.');
        }
    }
}
