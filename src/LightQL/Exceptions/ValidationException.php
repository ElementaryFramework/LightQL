<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when an entity column doesn't pass a required validation.
 */
class ValidationException extends Exception
{
    private const int CODE = 422;

    /**
     * Thrown when a value can't pass the validator.
     *
     * @param string $property The name of the property.
     * @param Throwable|null $previous The exception that occurred prior to this one, if any.
     */
    public function __construct(string $property, ?Throwable $previous = null)
    {
        parent::__construct(
            message: "The value of the property \"{$property}\" have not pass the validator.",
            code: self::CODE,
            previous: $previous
        );
    }
}