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
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     */
    public function __construct(array $data = array())
    {
        if (!Annotations::classHasAnnotation($this, "@entity")) {
            throw new EntityException("Cannot create an entity without the @entity annotation.");
        }

        $this->_reflection = new \ReflectionClass($this);
        $properties = $this->_reflection->getProperties();

        foreach ($properties as $property) {
            if ($this->_hasAnnotation($property->name, "@column")) {
                $name = $this->_getMetadata($property->name, "@column", 'name');
                $type = $this->_getMetadata($property->name, "@column", 'type');
                $size = array(
                    $this->_getMetadata($property->name, '@size', 'min'),
                    $this->_getMetadata($property->name, '@size', 'max')
                );

                $column = new Column($name, $type, $size);

                $column->isPrimaryKey = $this->_hasAnnotation($property->name, '@id');
                $column->isUniqueKey = $column->isPrimaryKey || $this->_hasAnnotation($property->name, '@unique');
                $column->isAutoIncrement = $this->_hasAnnotation($property->name, '@autoIncrement');

                $this->_columns[$property->name] = $column;
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
        $this->raw = $data;

        // Populate @column properties
        foreach ($this->_columns as $property => $column) {
            if (array_key_exists($column->getName(), $this->raw)) {
                $this->{$property} = $this->raw[$column->getName()];
            } elseif (\is_null($this->{$property}) || $this->{$property} === null) {
                $this->{$property} = $column->getDefault();
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
        if ($this->_exists($column)) {
            return $this->raw[$column];
        }

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
        if ($this->_exists($column)) {
            $this->raw[$column] = $value;
        }
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
     * @param $property
     * @param $annotation
     * @return bool
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     */
    private function _hasAnnotation($property, $annotation): bool
    {
        return Annotations::propertyHasAnnotation($this, $property, $annotation);
    }

    /**
     * @param $property
     * @param $type
     * @param $name
     * @param null $default
     * @return IAnnotation
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     */
    private function _getMetadata($property, $type, $name = null, $default = null)
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
     * @param string $column
     * @return bool
     */
    private function _exists(string $column): bool
    {
        return array_key_exists($column, $this->raw);
    }
}
