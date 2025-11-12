<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use Attribute;
use ElementaryFramework\LightQL\Entities\IPrimaryKeyGenerator;
use InvalidArgumentException;

/**
 * Id Generator Attribute
 *
 * Used to define the primary key generator of an entity.
 *
 * This attribute should be used on a class with the {@see Table} attribute to have effect.
 *
 * @template TGenerator of IPrimaryKeyGenerator The generator class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class IdGenerator
{
    /**
     * Initialize the id generator attribute.
     *
     * @param class-string<TGenerator> $generator The ID generator class name.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $generator
    )
    {
        if (empty($generator)) {
            throw new InvalidArgumentException(self::class . ' must have a non-empty "generator" property');
        }

        if (!is_subclass_of($generator, IPrimaryKeyGenerator::class)) {
            throw new InvalidArgumentException("Generator class \"$generator\" must implement " . IPrimaryKeyGenerator::class);
        }
    }
}
