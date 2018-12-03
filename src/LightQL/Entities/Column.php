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
 * Column
 *
 * Describe a database table's column.
 *
 * @category Entities
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Entities/Column
 */
class Column
{
    /**
     * The column name.
     *
     * @var string
     */
    private $_name;

    /**
     * The column type.
     *
     * @var string
     */
    private $_type;

    /**
     * The column size (if any).
     *
     * @var int[]
     */
    private $_size;

    /**
     * The column's default value.
     *
     * @var mixed
     */
    private $_default = null;

    /**
     * Defines if the column has the
     * AUTO_INCREMENT property.
     *
     * @var bool
     */
    public $isAutoIncrement;

    /**
     * Defines if the column is a
     * primary key.
     *
     * @var bool
     */
    public $isPrimaryKey;

    /**
     * Defines if the column has the
     * UNIQUE property.
     *
     * @var bool
     */
    public $isUniqueKey;

    /**
     * Defines if the column is in
     * a one-to-many relation with another.
     *
     * @var bool
     */
    public $isOneToMany;

    /**
     * Defines if the column is in
     * a many-to-one relation with another.
     *
     * @var bool
     */
    public $isManyToOne;

    /**
     * Defines if the column is in
     * a many-to-many relation with another.
     *
     * @var bool
     */
    public $isManyToMany;

    /**
     * Defines if the column is in
     * a one-to-one relation with another.
     *
     * @var bool
     */
    public $isOneToOne;

    /**
     * Create a new instance of the table column descriptor.
     *
     * @param string $name    The column's name.
     * @param string $type    The column's type.
     * @param int[]  $size    The array of sizes containing (min, max) values only.
     * @param mixed  $default The default value of the column.
     */
    public function __construct(string $name, string $type, array $size, $default = null)
    {
        $this->_name = $name;
        $this->_type = $type;
        $this->_size = $size;
        $this->_default = $default;
    }

    /**
     * Returns the column's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Returns the column's type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * Returns the column's size.
     *
     * @return array
     */
    public function getSize(): array
    {
        return $this->_size;
    }

    /**
     * Returns the column's default value.
     *
     * @return mixed
     */
    public function getDefault()
    {
        return $this->_default;
    }
}
