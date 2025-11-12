<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;

/**
 * Not Null Attribute
 *
 * Used to set a property as a non-nullable value.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class NotNull {}
