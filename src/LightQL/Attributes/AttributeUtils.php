<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Attributes;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Helper class for operations with PHP attributes.
 */
final readonly class AttributeUtils
{
    /**
     * Gets the attribute instance for a class.
     *
     * @template TClass of object The class type.
     * @template TAttribute of object The attribute type.
     *
     * @param class-string<TClass>|TClass $class The class on which get the attribute.
     * @param class-string<TAttribute> $attribute The attribute to get.
     * @return TAttribute An instance of the attribute class of found.
     *
     * @throws ReflectionException When the attribute was not found on the class.
     */
    public static function ofClass(string|object $class, string $attribute): object
    {
        $attributes = new ReflectionClass($class)->getAttributes($attribute);
        if (empty($attributes)) {
            throw new ReflectionException("The attribute '$class' does not exist on class '$class'.");
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Gets the attribute instance for a property.
     *
     * @template TClass of object The class type.
     * @template TAttribute of object The attribute type.
     *
     * @param class-string<TClass>|TClass $class The class owner of the property.
     * @param string $property The name of the property.
     * @param class-string<TAttribute> $attribute The attribute to get.
     * @return TAttribute An instance of the attribute if found.
     *
     * @throws ReflectionException When the attribute was not found on the property.
     */
    public static function ofProperty(string|object $class, string $property, string $attribute): object
    {
        $attributes = new ReflectionProperty($class, $property)->getAttributes($attribute);
        if (empty($attributes)) {
            throw new ReflectionException("The attribute '$attribute' does not exist on property '$property' in class '$class'.");
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Gets the attribute instance for a method.
     *
     * @template TClass of object The class type.
     * @template TAttribute of object The attribute type.
     *
     * @param class-string<TClass>|TClass $class The class owner of the method.
     * @param string $method The name of the method.
     * @param class-string<TAttribute> $attribute The attribute to get.
     * @return TAttribute An instance of the attribute if found.
     *
     * @throws ReflectionException When the attribute was not found on the method.
     */
    public static function ofMethod(string|object $class, string $method, string $attribute): object
    {
        $attributes = new ReflectionMethod($class, $method)->getAttributes($attribute);
        if (empty($attributes)) {
            throw new ReflectionException("The attribute '$attribute' does not exist on method '$method' in class '$class'.");
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Checks whether a class has the given attribute.
     *
     * @template TClass of object The class type.
     * @template TAttribute of object The attribute type.
     *
     * @param class-string<TClass>|TClass $class The class.
     * @param class-string<TAttribute> $attribute The attribute to check.
     * @return bool Whether the class has the given annotation.
     *
     * @throws ReflectionException
     */
    public static function classHasAttribute(string|object $class, string $attribute): bool
    {
        $attributes = new ReflectionClass($class)->getAttributes($attribute);
        return !empty($attributes);
    }

    /**
     * Checks whether a class property has the given attribute.
     *
     * @template TClass of object The class type.
     * @template TAttribute of object The attribute type.
     *
     * @param class-string<TClass>|TClass $class The class owner of the property.
     * @param string $property The name of the property.
     * @param class-string<TAttribute> $attribute The attribute to check.
     * @return bool Whether the property has the given annotation.
     *
     * @throws ReflectionException
     */
    public static function propertyHasAttribute(string|object $class, string $property, string $attribute): bool
    {
        $attributes = new ReflectionProperty($class, $property)->getAttributes($attribute);
        return !empty($attributes);
    }
}