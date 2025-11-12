<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use ElementaryFramework\LightQL\Entities\ITransformer;
use InvalidArgumentException;

/**
 * Transformer Attribute
 *
 * Used to define the value transformer of an entity.
 *
 * This attribute should be used on a property with the {@see Column} attribute to have effect.
 *
 * @template TTransformer of ITransformer The transformer class.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Transformer
{
    /**
     * Initialize the transformer attribute.
     *
     * @param class-string<TTransformer> $transformer The transformer class name.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $transformer
    )
    {
        if (empty($transformer)) {
            throw new InvalidArgumentException(self::class . ' must have a non-empty "transformer" property');
        }

        if (!is_subclass_of($transformer, ITransformer::class)) {
            throw new InvalidArgumentException("Transformer class \"$transformer\" must implement " . ITransformer::class);
        }
    }
}
