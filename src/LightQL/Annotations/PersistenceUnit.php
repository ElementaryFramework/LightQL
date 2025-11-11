<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Annotations;

use Attribute;
use InvalidArgumentException;

/**
 * Persistence Unit Attribute
 *
 * Used to specify the persistence unit to use for an entity manager.
 *
 * @property-read string $name The name of this persistence unit.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class PersistenceUnit
{
    /**
     * Initialize the persistence unit attribute.
     *
     * @param string $name The name of the persistence unit.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $name
    ) {
        if (empty($name)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty name property.');
        }
    }
}
