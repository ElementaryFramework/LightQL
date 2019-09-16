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
use ElementaryFramework\LightQL\Exceptions\QueryException;
use ElementaryFramework\LightQL\Sessions\Facade;

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
     * The facade running this query.
     *
     * @var Facade
     */
    private $_facade;

    /**
     * The named query string.
     *
     * @var string
     */
    private $_namedQuery;

    /**
     * Query parameters.
     *
     * @var array
     */
    private $_parameters = array();

    /**
     * The query executed by this instance.
     *
     * @var \PDOStatement
     */
    private $_query = null;

    /**
     * Query constructor.
     *
     * @param Facade $facade
     */
    public function __construct(Facade $facade)
    {
        $this->_facade = $facade;
    }

    /**
     * Sets the named query to execute.
     *
     * @param string $query The named query.
     */
    public function setQuery(string $query)
    {
        $this->_namedQuery = $query;
    }

    /**
     * Defines the value of one of query parameters.
     *
     * @param string $name  The name of the parameter in the query.
     * @param mixed  $value The value of this parameter.
     */
    public function setParam(string $name, $value)
    {
        $this->_parameters[$name] = $value;
    }

    /**
     * Executes the query.
     *
     * @return bool
     */
    public function run(): bool
    {
        try {
            $this->_query = $this->_facade
                ->getEntityManager()
                ->getLightQL()
                ->prepare($this->_namedQuery);

            foreach ($this->_parameters as $name => $value) {
                $this->_query->bindValue($name, $value);
            }

            return $this->_query->execute();
        } catch (\Exception $e) {
            throw new QueryException($e->getMessage());
        }
    }

    /**
     * Returns the set of results after the execution of the query.
     *
     * @return Entity[]
     */
    public function getResults(): array
    {
        if ($this->_query === null) {
            throw new QueryException("Cannot get results, have you ran the query?");
        }

        $className = $this->_facade->getEntityClassName();

        $results = $this->_facade->_parseRawEntities(
            $this->_query->fetchAll(),
            $className,
            Annotations::ofClass($className, "@entity")
        );

        return $results;
    }

    /**
     * Returns the first result of the set after the execution
     * of the query.
     *
     * @return IEntity|null
     */
    public function getFirstResult(): ?IEntity
    {
        $results = $this->getResults();
        return count($results) > 0 ? $results[0] : null;
    }
}
