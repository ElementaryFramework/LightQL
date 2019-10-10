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

use ArgumentCountError;
use Exception;

/**
 * Entities Collection
 *
 * A collection of entities which allow ordering, filtering and more.
 *
 * @category Entities
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Entities/EntitiesCollection
 */
class EntitiesCollection implements \ArrayAccess, \SeekableIterator, \Countable
{
    /**
     * The list of entities
     *
     * @var IEntity[]
     */
    private $_entities;

    /**
     * The current position in the iterator
     *
     * @var int
     */
    private $_position = 0;

    public function __construct(array $entities)
    {
        $this->_entities = $entities;
    }

    public function order(...$params) : self
    {
        if (count($params) === 1) {
            if (is_callable($params[0])) {
                $this->_sort($params[0]);
            } elseif (is_string($params[0])) {
                $this->_sort(function($current, $next) use ($params) {
                    return ($current->get($params[0]) > $next->get($params[0]));
                });
            }
        } elseif (count($params) === 2 && is_string($params[0]) && is_string($params[1])) {
            if (strtolower($params[1]) !== 'asc' && strtolower($params[1]) !== 'desc') {
                throw new Exception("The second parameter must be 'asc' or 'desc'");
            }

            $this->_sort(function($current, $next) use ($params) {
                if (strtolower($params[1]) === 'asc') {
                    return ($current->get($params[0]) > $next->get($params[0]));
                } elseif (strtolower($params[1]) === 'desc') {
                    return ($current->get($params[0]) < $next->get($params[0]));
                }
            });
        } else {
            throw new ArgumentCountError();
        }

        return $this;
    }

    public function filter(callable $func) : self
    {
        $result = array();

        foreach ($this->_entities as $entity) {
            if ($func($entity) === true) {
                array_push($result, $entity);
            }
        }

        $this->_entities = array_values($result);

        return $this;
    }

    public function toArray()
    {
        return $this->_entities;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->_entities[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->_entities[$offset] : null;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->_entities[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->_entities[$offset]);
        }
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        $values = array_values($this->_entities);

        return $values[$this->_position];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->_position++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        $keys = array_keys($this->_entities);

        return $keys[$this->_position];
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->_position < count($this->_entities);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Seeks to a position
     * @link http://php.net/manual/en/seekableiterator.seek.php
     * @param int $position <p>
     * The position to seek to.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function seek($position)
    {
        $this->_position = $position;
    }

    public function count()
    {
        return count($this->_entities);
    }

    private function _sort(callable $func)
    {
        for ($i = 0, $l = count($this->_entities); $i < $l - 1; $i++) {
            for ($j = $i+1; $j < $l; $j++) {
                if ($func($this->_entities[$i], $this->_entities[$j])) {
                    $t = $this->_entities[$i];
                    $this->_entities[$i] = $this->_entities[$j];
                    $this->_entities[$j] = $t;
                }
            }
        }
    }
}
