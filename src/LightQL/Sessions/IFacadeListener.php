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

namespace ElementaryFramework\LightQL\Sessions;

use ElementaryFramework\LightQL\Entities\Entity;

/**
 * IFacadeListener
 *
 * Provide methods for all entity facade listeners.
 *
 * @category Sessions
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Sessions/IFacadeListener
 */
interface IFacadeListener
{
    /**
     * An entity will be created.
     *
     * @param Entity $entity The entity to create.
     *
     * @return bool true if we can execute the query, false to cancel the entity creation.
     */
    function beforeCreate(Entity &$entity): bool;

    /**
     * An entity was just created.
     *
     * @param Entity $entity The created entity.
     */
    function onCreate(Entity $entity);

    /**
     * An entity will be edited.
     *
     * @param Entity $entity The entity to edit.
     *
     * @return bool true if we can execute the query, false to cancel the entity edition.
     */
    function beforeEdit(Entity &$entity): bool;

    /**
     * An entity was just edited.
     *
     * @param Entity $entity The entity to edit.
     */
    function onEdit(Entity $entity);

    /**
     * An entity will be deleted.
     *
     * @param Entity $entity The entity to delete.
     *
     * @return bool true if we can execute the query, false to cancel the entity deletion.
     */
    function beforeDelete(Entity &$entity): bool;

    /**
     * An entity was just deleted.
     *
     * @param Entity $entity The entity to delete.
     */
    function onDelete(Entity $entity);
}