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

/**
 * Generic Entity
 *
 * Represent an entity which can adapt to any table structure.
 *
 * @package ElementaryFramework\LightQL\Entities
 * @author  Nana Axel <ax.lnana@outlook.com>
 * @link    http://lightql.na2axl.tk/docs/api/LightQL/Entities/GenericEntity
 */
final class GenericEntity implements IEntity
{
    /**
     * The name of the table held by this entity.
     *
     * @var string
     */
    private $_name;

    /**
     * The name of the column defined as the primary key of the table, if any.
     *
     * @var string
     */
    private $_pk;

    /**
     * The collection of key-value pairs, in which keys represent columns names.
     *
     * @var array
     */
    private $_data;

    /**
     * GenericEntity constructor.
     *
     * @param string $name The name of the table managed by this GenericEntity.
     * @param null|string $pk The name of the column defined as the primary key of the table, if any.
     * @param array $data The initial values of columns in this GenericEntity.
     */
    public function __construct(string $name, string $pk, array $data = array())
    {
        $this->_name = $name;
        $this->_pk = $pk;

        $this->hydrate($data);
    }

    /**
     * Populates data in the entity.
     *
     * @param array $data The raw database data.
     */
    public function hydrate(array $data)
    {
        foreach ($data as $column => $value) {
            $this->set($column, $value);
        }
    }

    /**
     * Sets the raw value of a table column.
     *
     * @param string $column The table column name.
     * @param mixed $value The table column value.
     */
    public function set(string $column, $value)
    {
        $this->_data[$column] = $value;
    }

    /**
     * @param string $name The column's name.
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name The column's name.
     * @param mixed $value The value to assign.
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
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
        return array_key_exists($column, $this->_data) ? $this->_data[$column] : null;
    }

    /**
     * Returns the name of the table managed by this GenericEntity.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Return the data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     * Return the column name of the primary key.
     *
     * @return string
     */
    public function getPk(): string
    {
        return $this->_pk;
    }
}