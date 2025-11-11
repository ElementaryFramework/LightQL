<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Annotations;

use Attribute;
use InvalidArgumentException;

/**
 * Size Attribute
 *
 * Used to set various sizes for a mapped property.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @property-read int|null $min The minimal size of the value.
 * @property-read int|null $max The maximal size of the value.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Size
{
    /**
     * Initialize the size attribute.
     *
     * @param int|null $max The maximal size of the value.
     * @param int|null $min The minimal size of the value.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly ?int $max = null,
        public readonly ?int $min = null
    ) {
        if ($max === null && $min === null) {
            throw new InvalidArgumentException(self::class . ' requires a min and/or max property');
        }
    }
}
