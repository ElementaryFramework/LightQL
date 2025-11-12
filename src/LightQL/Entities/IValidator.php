<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\LightQL\Attributes\Validator;

/**
 * IValueValidator
 *
 * This interface provides a contract for implementations of values validators. Values validators are applied to entity
 * columns through the {@see Validator} attribute. Only one validator is allowed on the same attribute.
 *
 * @template TValue The value type being validated
 */
interface IValidator
{
    /**
     * Validates a value before it is sent to the database.
     *
     * @param TValue $value The value to validate.
     * @param string $property The name of the column property being validated.
     * @param IEntity $entity The entity object.
     *
     * @return bool `true` if the value passes the validator, `false` otherwise.
     */
    public function validate(mixed $value, string $property, IEntity $entity): bool;
}