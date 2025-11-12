<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\LightQL\Attributes\Transformer;

/**
 * ITransformer
 *
 * This interface provides a contract for implementations of values transformers. Value transformers are middlewares
 * that cast a value after it is retrieved from the database, and before it is sent to the database.
 *
 * You can implement this interface to provide to your entities a way to serialize values not directly storable in a
 * database, like PHP objects or arrays.
 *
 * Value transformers are applied to entity columns through the {@see Transformer} attribute. Only one transformer is
 * allowed on the same column.
 *
 * @template TDatabaseValue of mixed The serialized value type.
 * @template TEntityValue of mixed The unserialized value type.
 */
interface ITransformer
{
    /**
     * Serializes a value before written it in database.
     *
     * @param TEntityValue $value The value to serialize.
     * @param string $property The name of the property to serialize.
     * @param IEntity $entity The entity being stored in database.
     *
     * @return TDatabaseValue The serialized value.
     */
    public function serialize(mixed $value, string $property, IEntity $entity): mixed;

    /**
     * Unserializes a value after read from database.
     *
     * @param TDatabaseValue $value The value to unserialize.
     * @param string $column The name of the column which hold the value.
     * @param array<string, mixed> $row The table row being read.
     *
     * @return TEntityValue The unserialized value.
     */
    public function unserialize(mixed $value, string $column, array $row): mixed;
}