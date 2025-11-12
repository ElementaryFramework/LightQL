<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use ElementaryFramework\LightQL\Entities\IValidator;
use InvalidArgumentException;

/**
 * Validator Attribute
 *
 * Used to define the value validator of an entity.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @template TValidator of IValidator The validator class.
 *
 * @property-read class-string<TValidator> $validator The name of the validator class to use.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Validator
{
    /**
     * Initialize the validator attribute.
     *
     * @param class-string<TValidator> $validator The validator class name.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $validator
    ) {
        if (empty($validator)) {
            throw new InvalidArgumentException(self::class . ' must have a non-empty "validator" property');
        }

        if (!is_subclass_of($validator, IValidator::class)) {
            throw new InvalidArgumentException("Validator class \"$validator\" must implement " . IValidator::class);
        }
    }
}
