<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Annotations;

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
 * @property-read class-string<IPrimaryKeyGenerator> $generator Specify the class name to use as the ID generator of the current entity.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class IdGenerator
{
    /**
     * Initialize the id generator attribute.
     *
     * @param class-string<IPrimaryKeyGenerator> $generator The ID generator class name
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $generator
    ) {
        if (empty($generator)) {
            throw new InvalidArgumentException(self::class . " must have a non-empty \"generator\" property");
        }

        if (!is_subclass_of($generator, IPrimaryKeyGenerator::class)) {
            throw new InvalidArgumentException("Generator class \"$generator\" must implement " . IPrimaryKeyGenerator::class);
        }
    }
}
