<?php

/**
 * LightQL - The lightweight PHP ORM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @category  Library
 * @package   LightQL
 * @author    Axel Nana <ax.lnana@outlook.com>
 * @copyright 2018 Aliens Group, Inc.
 * @license   MIT <https://github.com/ElementaryFramework/LightQL/blob/master/LICENSE>
 * @version   1.0.0
 * @link      http://lightql.na2axl.tk
 */

namespace ElementaryFramework\LightQL\Entities;

use ElementaryFramework\Annotations\Annotations;
use ElementaryFramework\Annotations\IAnnotation;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ElementaryFramework\LightQL\Exceptions\AnnotationException;

/**
 * Entity
 *
 * Object Oriented Mapping of a database row.
 *
 * @abstract
 * @category Entities
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Entities/Entity
 */
abstract class Entity implements IEntity
{
    /**
     * Fetch data in eager mode.
     */
    const FETCH_EAGER = 1;

    /**
     * Fetch data in lazy mode.
     */
    const FETCH_LAZY = 2;

    /**
     * The raw data provided from the database.
     *
     * @var array
     */
    protected $raw = array();

    /**
     * The reflection class of this entity.
     *
     * @var \ReflectionClass
     */
    private $_reflection = null;

    /**
     * The array of database columns of this entity.
     *
     * @var Column[]
     */
    private $_columns = array();

    /**
     * Entity constructor.
     *
     * @param array $data The raw database data.
     *
     * @throws EntityException
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function __construct(array $data = array())
    {
        if (!Annotations::classHasAnnotation($this, "@entity")) {
            throw new EntityException("Cannot create an entity without the @entity annotation.");
        }

        $this->_reflection = new \ReflectionClass($this);
        $properties = $this->_reflection->getProperties();

        $pkFound = false;

        foreach ($properties as $property) {
            if ($this->_propertyHasAnnotation($property->name, "@column")) {
                $name = $this->_getMetadata($property->name, "@column", 'name');
                $type = $this->_getMetadata($property->name, "@column", 'type');
                $size = array(
                    $this->_getMetadata($property->name, '@size', 'min'),
                    $this->_getMetadata($property->name, '@size', 'max')
                );

                $column = new Column($name, $type, $size);

                $column->isPrimaryKey = $this->_propertyHasAnnotation($property->name, '@id');
                $column->isUniqueKey = $column->isPrimaryKey || $this->_propertyHasAnnotation($property->name, '@unique');
                $column->isAutoIncrement = $this->_propertyHasAnnotation($property->name, '@autoIncrement');
                $column->isManyToMany = $this->_propertyHasAnnotation($property->name, '@manyToMany');
                $column->isManyToOne = $this->_propertyHasAnnotation($property->name, '@manyToOne');
                $column->isOneToMany = $this->_propertyHasAnnotation($property->name, '@oneToMany');
                $column->isOneToOne = $this->_propertyHasAnnotation($property->name, '@oneToOne');

                $this->_columns[$property->name] = $column;

                if ($column->isPrimaryKey && $pkFound) {
                    throw new EntityException("The entity has declared more than one primary keys. Consider using a class implementing the IPrimaryKey interface instead.");
                }

                $pkFound = $column->isPrimaryKey;
            }
        }

        $this->hydrate($data);
    }

    /**
     * Populates data in the entity.
     *
     * @param array $data The raw database data.
     */
    public function hydrate(array $data)
    {
        // Merge values
        foreach ($data as $name => $value) {
            $this->raw[$name] = $value;
        }

        // Populate @column properties
        foreach ($this->_columns as $property => $column) {
            if (!$column->isManyToMany && !$column->isManyToOne
                && !$column->isOneToMany && !$column->isOneToOne) {
                if (array_key_exists($column->getName(), $this->raw)) {
                    $this->{$property} = $this->raw[$column->getName()];
                } elseif (\is_null($this->{$property}) || $this->{$property} === null) {
                    $this->{$property} = $column->getDefault();
                }
            } else {
                $this->{$property} = null;
            }
        }
    }

    /**
     * Gets the raw value of a table column.
     *
     * @param string $column The table column name.
     *
     * @return mixed
     */
    public function get(string $column)
    {
        // Try to get the raw value
        if ($this->_exists($column)) {
            return $this->raw[$column];
        }

        // Try to get the property value
        /** @var Column $c */
        foreach ($this->_columns as $property => $c) {
            if ($c->getName() === $column && isset($this->{$property})) {
                if ($this->{$property} instanceof Entity) {
                    // Have to be a reference, not a collection
                    if ($c->isManyToOne || $c->isManyToMany) {
                        // Find the good property
                        continue;
                    } else if ($c->isOneToMany) {
                        // Resolve the referenced column
                        $referencedColumn = $this->_getMetadata($property, "@oneToMany", "referencedColumn");
                        return $this->{$property}->get($referencedColumn);
                    } else if ($c->isOneToOne) {
                        // Resolve the referenced column
                        $referencedColumn = $this->_getMetadata($property, "@oneToOne", "referencedColumn");
                        return $this->{$property}->get($referencedColumn);
                    }
                } else {
                    return $this->{$property};
                }
            }
        }

        // The value definitively doesn't exist
        return null;
    }

    /**
     * Sets the raw value of a table column.
     *
     * @param string $column The table column name.
     * @param mixed  $value  The table column value.
     */
    public function set(string $column, $value)
    {
        $this->hydrate(array($column => $value));
    }

    /**
     * Gets the list of table columns
     * of this entity.
     *
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->_columns;
    }

    /**
     * Checks if a property has the given annotation.
     *
     * @param string $property   The name of the property.
     * @param string $annotation The name of the annotation.
     *
     * @return bool
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     */
    private function _propertyHasAnnotation(string $property, string $annotation): bool
    {
        return Annotations::propertyHasAnnotation($this, $property, $annotation);
    }

    /**
     * Returns the annotation, or the value of an annotation property
     * of a property.
     *
     * @param string $property The name of the property.
     * @param string $type     The name of the annotation.
     * @param string $name     The name of the annotation property to retrieve.
     *                         Set it to null to retrieve the entire annotation object.
     * @param mixed  $default  The default value to return if the property has no
     *                         annotation of the given type.
     *
     * @return IAnnotation|mixed
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     */
    private function _getMetadata(string $property, string $type, ?string $name = null, $default = null)
    {
        $a = Annotations::ofProperty($this, $property, $type);

        if (!count($a)) {
            return $default;
        }

        if ($name === null) {
            return $a[0];
        }

        return $a[0]->$name;
    }

    /**
     * Checks if the given column name exists in this entity.
     *
     * @param string $column The column name to search.
     *
     * @return bool
     */
    private function _exists(string $column): bool
    {
        return array_key_exists($column, $this->raw);
    }
}
