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

namespace ElementaryFramework\LightQL\Annotations;

use ElementaryFramework\Annotations\Annotation;
use ElementaryFramework\Annotations\Exceptions\AnnotationException;

/**
 * Named Query Annotation
 *
 * Used to list the set of SQL queries associated to
 * an entity.
 *
 * @usage('class' => true, 'multiple' => true, 'inherited' => true)
 *
 * @category Annotations
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Annotations/NamedQueryAnnotation
 */
class NamedQueryAnnotation extends Annotation
{
    /**
     * The query's name.
     *
     * @var string
     */
    public $name;

    /**
     * The SQL query.
     *
     * @var string
     */
    public $query;

    /**
     * Initialize the annotation.
     *
     * @param array $properties The array of annotation properties.
     *
     * @throws AnnotationException
     */
    public function initAnnotation(array $properties)
    {
        if (isset($properties[0])) {
            $this->name = strval($properties[0]);
            unset($properties[0]);
        }

        if (isset($properties[1])) {
            $this->query = strval($properties[1]);
            unset($properties[1]);
        }

        parent::initAnnotation($properties);

        if ($this->name !== null && strlen($this->name) <= 0) {
            throw new AnnotationException(self::class . ' requires a (string) name property.');
        }

        if ($this->name === null) {
            throw new AnnotationException(self::class . ' requires a (string) name property.');
        }

        if ($this->query !== null && strlen($this->query) <= 0) {
            throw new AnnotationException(self::class . ' requires a (string) query property.');
        }

        if ($this->query === null) {
            throw new AnnotationException(self::class . ' requires a (string) query property.');
        }
    }
}
