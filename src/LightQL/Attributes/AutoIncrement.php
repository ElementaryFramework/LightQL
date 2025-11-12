<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;

/**
 * Auto Increment Attribute
 *
 * Used to define that a property is an auto incremented table column.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class AutoIncrement {}
