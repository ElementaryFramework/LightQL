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

use ElementaryFramework\LightQL\Exceptions\QueryException;

/**
 * Query
 *
 * Manage, run and get results from named queries.
 *
 * @category Entities
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Entities/Query
 */
class Query
{
    /**
     * @var EntityManager
     */
    private $_entityManager;

    /**
     * @var \ReflectionClass
     */
    private $_entityReflection;

    /**
     * @var string
     */
    private $_namedQuery;

    /**
     * @var array
     */
    private $_parameters = array();

    /**
     * @var \PDOStatement
     */
    private $_query = null;

    /**
     * Query constructor.
     *
     * @param EntityManager $manager
     */
    public function __construct(EntityManager $manager)
    {
        $this->_entityManager = $manager;
    }

    public function setEntity(\ReflectionClass $entity)
    {
        $this->_entityReflection = $entity;
    }

    public function setQuery(string $query)
    {
        $this->_namedQuery = $query;
    }

    public function setParam(string $name, $value)
    {
        $this->_parameters[$name] = $value;
    }

    public function run(): bool
    {
        try {
            $this->_query = $this->_entityManager->getLightQL()->prepare($this->_namedQuery);

            foreach ($this->_parameters as $name => $value) {
                $this->_query->bindValue($name, $value);
            }

            return $this->_query->execute();
        } catch (\Exception $e) {
            throw new QueryException($e->getMessage());
        }
    }

    public function getResults(): array
    {
        if ($this->_query === null) {
            throw new QueryException("Cannot get results, have you ran the query?");
        }

        $results = array_map(function ($item) {
            return $this->_entityReflection->newInstance($item);
        }, $this->_query->fetchAll());

        return $results;
    }

    public function getFirstResult(): IEntity
    {
        $results = $this->getResults();
        return $results[0];
    }
}
