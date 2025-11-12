<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use InvalidArgumentException;
use ElementaryFramework\LightQL\Enums\FetchMode;

/**
 * Entity Attribute
 *
 * Used to mark a class as an entity.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Table
{
    /**
     * Initialize the entity attribute.
     *
     * @param string $table The name of the table represented by the entity.
     * @param FetchMode $fetchMode The fetch mode used when querying the entity.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string    $table,
        public FetchMode $fetchMode = FetchMode::LAZY
    )
    {
        if (empty($table)) {
            throw new InvalidArgumentException(self::class . ' requires a non-empty "table" property');
        }
    }
}
