<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;

/**
 * Id Attribute
 *
 * Used to mark a property as the primary key of a table.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Id {}
