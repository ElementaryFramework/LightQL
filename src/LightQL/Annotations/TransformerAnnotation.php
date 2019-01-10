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
use ElementaryFramework\Annotations\AnnotationFile;
use ElementaryFramework\Annotations\IAnnotationFileAware;
use ElementaryFramework\Annotations\Exceptions\AnnotationException;

/**
 * Transformer Annotation
 *
 * Used to define the value transformer of an entity.
 *
 * This annotation have to be associated with the @entity
 * annotation to take effect.
 *
 * @usage('class' => true, 'inherited' => true)
 *
 * @category Annotations
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Annotations/TransformerAnnotation
 */
class TransformerAnnotation extends Annotation implements IAnnotationFileAware
{
    /**
     * Specify the class name to use as the value transformer
     * of the current entity.
     *
     * @var string
     */
    public $transformer;

    /**
     * Annotation file.
     *
     * @var AnnotationFile
     */
    protected $file;

    /**
     * Initialize the annotation.
     *
     * @param array $properties The array of annotation properties
     *
     * @throws AnnotationException
     *
     * @return void
     */
    public function initAnnotation(array $properties)
    {
        $this->map($properties, array("transformer"));

        parent::initAnnotation($properties);

        if (!isset($this->transformer)) {
            throw new AnnotationException(self::class . " must have a \"transformer\" property");
        }

        $this->transformer = $this->file->resolveType($this->transformer);
    }

    /**
     * Provides information about file, that contains this annotation.
     *
     * @param AnnotationFile $file Annotation file.
     *
     * @return void
     */
    public function setAnnotationFile(AnnotationFile $file)
    {
        $this->file = $file;
    }
}
