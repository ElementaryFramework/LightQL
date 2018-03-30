<?php

/**
 * OpenDB - The lightweight PHP ORM
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
 * @package   OpenDB
 * @author    Axel Nana <ax.lnana@outlook.com>
 * @copyright 2018 Aliens Group, Inc.
 * @license   MIT <https://github.com/na2axl/bubble/blob/master/LICENSE>
 * @version   GIT: 0.0.1
 * @link      http://opengl.na2axl.tk
 */

namespace OpenDB;

/**
 * OpenDB - Database Manager Class
 *
 * @package     OpenDB
 * @author      Nana Axel <ax.lnana@outlook.com>
 */
class OpenDB
{
    /**
     * Registered SQL operators
     *
     * @var array
     * @access private
     */
    private static $operators = array('!=', '<>', '<=', '>=', '=', '<', '>');

    /**
     * The database name
     *
     * @var string
     * @access protected
     */
    protected $database;

    /**
     * The table name
     *
     * @var string
     * @access protected
     */
    protected $table;

    /**
     * The database server address
     *
     * @var string
     * @access protected
     */
    protected $hostname;

    /**
     * The database username
     *
     * @var string
     * @access protected
     */
    protected $username;

    /**
     * The database password
     *
     * @var string
     * @access protected
     */
    protected $password;

    /**
     * The current PDO instance
     *
     * @var object
     * @access private
     */
    private $pdo = NULL;

    /**
     * The where clause
     *
     * @var string
     * @access private
     */
    private $where = NULL;

    /**
     * The order clause
     *
     * @var string
     * @access private
     */
    private $order = NULL;

    /**
     * The limit clause
     *
     * @var string
     * @access private
     */
    private $limit = NULL;

    /**
     * Class __constructor
     *
     * @param  string $table The name of the table
     * @param  string $database The name of the database
     * @param  string $server The name of the server
     * @param  string $user The username for your database connection
     * @param  string $pass The password associated to the username
     *
     * @throws \PDOException
     */
    public function __construct($server, $database, $user, $pass, $table = "")
    {
        $this->setDB($database, $table, $server, $user, $pass);
    }

    /**
     * Changes the currently used database
     *
     * @param string $database The database's name
     * @param string $table The table's name
     * @param string $server The server's url
     * @param string $user The user name
     * @param string $pass The password
     *
     * @throws \PDOException
     */
    public function setDB($database, $table = NULL, $server = NULL, $user = NULL, $pass = NULL)
    {
        $this->database = $database;
        $this->table    = (isset($table) && $table != '')       ? $table    : $this->table;
        $this->hostname = (isset($server) && $server != '')     ? $server   : $this->hostname;
        $this->username = (isset($user) && $server != '')       ? $user     : $this->username;
        $this->password = (isset($pass) && $server != '')       ? $pass     : $this->password;

        $this->close();
        $this->_instantiate();
    }

    /**
     * Closes a connection
     */
    public function close()
    {
        $this->pdo = FALSE;
    }

    /**
     * Connect to the database / Instantiate PDO
     *
     * @throws \PDOException
     */
    private function _instantiate()
    {
        try {
            $this->pdo = new \PDO("mysql:host={$this->hostname};dbname={$this->database}", $this->username, $this->password, array(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE, \PDO::ATTR_PERSISTENT => TRUE));
        }
        catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * Changes the currently used table
     *
     * @param string $table The table's name
     *
     * @return OpenDB
     */
    public function from($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Add a where condition
     *
     * @param string|array $condition
     *
     * @return OpenDB
     */
    public function where($condition)
    {
        // where(array('field1'=>'value', 'field2'=>'value'))
        $this->where = (NULL !== $this->where) ? "{$this->where} OR (" : "(";
        if (is_array($condition)) {
            $i = 0;
            $operand = "=";
            foreach ($condition as $field => $value) {
                $this->where .= ($i > 0) ? " AND " : "";
                if (is_int($field)) {
                    $this->where .= $value;
                }
                else {
                    $parts = explode(' ', $value);
                    foreach (self::$operators as $operator) {
                        if (in_array($operator, $parts, TRUE) && $parts[0] === $operator) {
                            $operand = $operator;
                        }
                    }
                    $this->where .= "{$field} {$operand} " . str_replace($operand, "", $value);
                    $operand = "=";
                }
                ++$i;
            }
        }
        else {
            $this->where .= $condition;
        }
        $this->where .= ")";

        return $this;
    }

    /**
     * Add an order clause.
     *
     * @param string $field
     * @param string $mode
     *
     * @return OpenDB
     */
    public function order($field, $mode = "ASC")
    {
        $this->order = " ORDER BY {$field} {$mode} ";
        return $this;
    }

    /**
     * Add a limit clause.
     *
     * @param  int  $offset
     * @param  int  $count
     *
     * @return OpenDB
     */
    public function limit($offset, $count)
    {
        $this->limit = " LIMIT {$offset}, {$count} ";
        return $this;
    }

    /**
     * Selects data in database.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws OpenDBException
     *
     * @return \PDOStatement
     */
    public function select($fields = "*")
    {
        return $this->_select($fields);
    }

    /**
     * Executes the SELECT SQL query.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws OpenDBException
     *
     * @return \PDOStatement
     */
    protected function _select($fields)
    {
        // Constructing the fields list
        if (is_array($fields)) {
            $_fields = "";
            foreach ($fields as $field => $alias) {
                if (is_int($field))
                    $_fields .= "{$alias}, ";
                elseif (is_string($field))
                    $_fields .= "{$field} AS {$alias}, ";
            }
            $fields = trim($_fields, ", ");
        }

        // Constructing the SELECT query string
        $query = "SELECT {$fields} FROM {$this->table}" . ((NULL !== $this->where) ? " WHERE {$this->where}" : " ") . ((NULL !== $this->order) ? $this->order : " ") . ((NULL !== $this->limit) ? $this->limit : " ");

        // Preparing the query
        $getFieldsData = $this->prepare($query);

        // Executing the query
        if ($getFieldsData->execute() !== FALSE) {
            $this->_reset_clauses();
            return $getFieldsData;
        }
        else {
            throw new OpenDBException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Prepares a query.
     *
     * @uses   \PDO::prepare()
     *
     * @param  string  $query      The query to execute
     * @param  array   $options    PDO options
     *
     * @return \PDOStatement
     */
    public function prepare($query, array $options = array()): \PDOStatement
    {
        return $this->pdo->prepare($query, $options);
    }

    /**
     * Reset all clauses
     * @access protected
     */
    protected function _reset_clauses()
    {
        $this->where = NULL;
        $this->order = NULL;
        $this->limit = NULL;
    }

    /**
     * Selects the first data result of the query.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws OpenDBException
     *
     * @return array
     */
    public function select_first($fields = "*"): array
    {
        $result = $this->select_array($fields);

        if (count($result))
            return $result[0];

        return NULL;
    }

    /**
     * Selects data as array of arrays in database.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws OpenDBException
     *
     * @return array
     */
    public function select_array($fields = "*"): array
    {
        $select = $this->_select($fields);
        $result = array();

        while ($r = $select->fetch(\PDO::FETCH_LAZY)) {
            $result[] = array_diff_key((array) $r, array("queryString" => "queryString"));
        }

        return $result;
    }

    /**
     * Selects data as array of objects in database.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws OpenDBException
     *
     * @return array
     */
    public function select_object($fields = "*"): array
    {
        $select = $this->_select($fields);
        $result = array();

        while ($r = $select->fetch(\PDO::FETCH_OBJ)) {
            $result[] = $r;
        }

        return $result;
    }

    /**
     * Selects data in database with table joining.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     * @param  mixed  $params      The information used for jointures.
     *
     * @throws OpenDBException
     *
     * @return \PDOStatement
     */
    public function join($fields, $params)
    {
        return $this->_join($fields, $params);
    }

    /**
     * Executes a SELECT ... JOIN query.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     * @param  mixed  $params      The information used for jointures.
     *
     * @throws OpenDBException
     *
     * @return \PDOStatement
     */
    private function _join($fields, $params)
    {
        $jcond = "";

        if (is_array($fields)) {
            $fields = implode(",", $fields);
        }

        if (is_array($params)) {
            foreach ($params as $param) {
                $jcond .= " {$param['side']} JOIN {$param['table']} ON {$param['cond']} ";
            }
        }

        $query = "SELECT {$fields} FROM {$this->table} {$jcond} " . ((NULL !== $this->where) ? " WHERE {$this->where}" : " ") . ((NULL !== $this->order) ? $this->order : " ") . ((NULL !== $this->limit) ? $this->limit : "");

        $getFieldsData = $this->prepare($query);

        if ($getFieldsData->execute() !== FALSE) {
            $this->_reset_clauses();
            return $getFieldsData;
        }
        else {
            throw new OpenDBException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Selects data as array of arrays in database with table joining.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     * @param  mixed  $params      The information used for jointures.
     *
     * @throws OpenDBException
     *
     * @return array
     */
    public function join_array($fields, $params): array
    {
        $join = $this->_join($fields, $params);
        $result = array();

        while ($r = $join->fetch(\PDO::FETCH_LAZY)) {
            $result[] = array_diff_key((array) $r, array("queryString" => "queryString"));
        }

        return $result;
    }

    /**
     * Selects data as array of objects in database with table joining.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     * @param  mixed  $params      The information used for jointures.
     *
     * @throws OpenDBException
     *
     * @return array
     */
    public function join_object($fields, $params): array
    {
        $join = $this->_join($fields, $params);
        $result = array();

        while ($r = $join->fetch(\PDO::FETCH_OBJ)) {
            $result[] = $r;
        }

        return $result;
    }

    /**
     * Counts data in table.
     *
     * @param  mixed  $fields      The fields to select. This value can be an array of fields,
     *                             or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws OpenDBException
     *
     * @return integer
     */
    public function count($fields = "*"): int
    {
        if (is_array($fields)) {
            $field = implode(",", $fields);
        }

        $query = "SELECT COUNT(" . ((isset($field)) ? $field : $fields) . ") AS opendb_count FROM {$this->table}" . ((NULL !== $this->where) ? " WHERE {$this->where}" : " ") . ((NULL !== $this->limit) ? $this->limit : "");

        $getFieldsData = $this->prepare($query);

        if ($getFieldsData->execute() !== FALSE) {
            $this->_reset_clauses();
            $data = $getFieldsData->fetch();
            return (int) $data["opendb_count"];
        }
        else {
            throw new OpenDBException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Inserts data in table.
     *
     * @param  mixed  $fieldsAndValues  The fields and the associated values to insert.
     *
     * @throws OpenDBException
     *
     * @return boolean
     */
    public function insert($fieldsAndValues): bool
    {
        $fields = array();
        $values = array();

        foreach ($fieldsAndValues as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
        }

        $field = implode(",", $fields);
        $value = implode(",", $values);

        $query = "INSERT INTO {$this->table}({$field}) VALUES({$value})";

        $getFieldsData = $this->prepare($query);

        if ($getFieldsData->execute() !== FALSE) {
            $this->_reset_clauses();
            return TRUE;
        }
        else {
            throw new OpenDBException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Updates data in table.
     *
     * @param  mixed  $fieldsAndValues  The fields and the associated values to update.
     *
     * @throws OpenDBException
     *
     * @return boolean
     */
    public function update($fieldsAndValues): bool
    {
        $updates = "";
        $count   = count($fieldsAndValues);

        if (is_array($fieldsAndValues)) {
            foreach ($fieldsAndValues as $field => $value) {
                $count--;
                $updates .= "{$field} = {$value}";
                $updates .= ($count != 0) ? ", " : "";
            }
        }
        else {
            $updates = $fieldsAndValues;
        }

        $query = "UPDATE {$this->table} SET {$updates}" . ((NULL !== $this->where) ? " WHERE {$this->where}" : "");

        $getFieldsData = $this->prepare($query);

        if ($getFieldsData->execute() !== FALSE) {
            $this->_reset_clauses();
            return TRUE;
        }
        else {
            throw new OpenDBException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Deletes data in table.
     *
     * @throws OpenDBException
     *
     * @return boolean
     */
    public function delete(): bool
    {
        $query = "DELETE FROM {$this->table}" . ((NULL !== $this->where) ? " WHERE {$this->where}" : "");

        $getFieldsData = $this->prepare($query);

        if ($getFieldsData->execute() !== FALSE) {
            $this->_reset_clauses();
            return TRUE;
        }
        else {
            throw new OpenDBException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Executes a query.
     *
     * @uses   \PDO::query()
     *
     * @param  string  $query      The query to execute
     * @param  array   $options    PDO options
     *
     * @return \PDOStatement
     */
    public function query($query, array $options = array()): \PDOStatement
    {
        return $this->pdo->query($query, $options);
    }

    /**
     * Quotes a value.
     *
     * @uses   \PDO::quote()
     *
     * @param  string  $value
     *
     * @return string
     */
    public function quote($value): string
    {
        return $this->pdo->quote($value);
    }
}

/**
 * Dummy class used to throw exceptions
 */
class OpenDBException extends \Exception { }
