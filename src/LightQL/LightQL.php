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
 * @version   GIT: 0.0.1
 * @link      http://lightql.na2axl.tk
 */

namespace ElementaryFramework\LightQL;

use ElementaryFramework\Annotations\Annotations;
use ElementaryFramework\LightQL\Annotations\AutoIncrementAnnotation;
use ElementaryFramework\LightQL\Annotations\ColumnAnnotation;
use ElementaryFramework\LightQL\Annotations\EntityAnnotation;
use ElementaryFramework\LightQL\Annotations\IdAnnotation;
use ElementaryFramework\LightQL\Annotations\ManyToManyAnnotation;
use ElementaryFramework\LightQL\Annotations\ManyToOneAnnotation;
use ElementaryFramework\LightQL\Annotations\NamedQueryAnnotation;
use ElementaryFramework\LightQL\Annotations\NotNullAnnotation;
use ElementaryFramework\LightQL\Annotations\OneToManyAnnotation;
use ElementaryFramework\LightQL\Annotations\OneToOneAnnotation;
use ElementaryFramework\LightQL\Annotations\PersistenceUnitAnnotation;
use ElementaryFramework\LightQL\Annotations\SizeAnnotation;
use ElementaryFramework\LightQL\Annotations\UniqueAnnotation;
use ElementaryFramework\LightQL\Exceptions\LightQLException;

/**
 * LightQL - Database Manager Class
 *
 * @package LightQL
 * @author  Nana Axel <ax.lnana@outlook.com>
 * @link    http://lightql.na2axl.tk/docs/api/LightQL/LightQL
 */
class LightQL
{
    /**
     * Registered SQL operators.
     *
     * @var    array
     * @access private
     */
    private static $_operators = array('!=', '<>', '<=', '>=', '=', '<', '>');

    /**
     * Register all annotations in the manager
     */
    public static function registerAnnotations()
    {
        $manager = Annotations::getManager();

        $manager->registerAnnotation("entity", EntityAnnotation::class);
        $manager->registerAnnotation("column", ColumnAnnotation::class);
        $manager->registerAnnotation("id", IdAnnotation::class);
        $manager->registerAnnotation("unique", UniqueAnnotation::class);
        $manager->registerAnnotation("autoIncrement", AutoIncrementAnnotation::class);
        $manager->registerAnnotation("notNull", NotNullAnnotation::class);
        $manager->registerAnnotation("size", SizeAnnotation::class);
        $manager->registerAnnotation("manyToMany", ManyToManyAnnotation::class);
        $manager->registerAnnotation("manyToOne", ManyToOneAnnotation::class);
        $manager->registerAnnotation("oneToMany", OneToManyAnnotation::class);
        $manager->registerAnnotation("oneToOne", OneToOneAnnotation::class);
        $manager->registerAnnotation("persistenceUnit", PersistenceUnitAnnotation::class);
        $manager->registerAnnotation("namedQuery", NamedQueryAnnotation::class);
    }

    /**
     * The database name.
     *
     * @var    string
     * @access protected
     */
    protected $database;

    /**
     * The table name.
     *
     * @var    string
     * @access protected
     */
    protected $table;

    /**
     * The database server address.
     *
     * @var    string
     * @access protected
     */
    protected $hostname;

    /**
     * The database username.
     *
     * @var    string
     * @access protected
     */
    protected $username;

    /**
     * The database password.
     *
     * @var    string
     * @access protected
     */
    protected $password;

    /**
     * The PDO driver to use.
     *
     * @var    string
     * @access private
     */
    private $_driver;

    /**
     * The DBMS to use.
     *
     * @var    string
     * @access private
     */
    private $_dbms;

    /**
     * The PDO connection options.
     *
     * @var    array
     * @access private
     */
    private $_options;

    /**
     * The DSN used for the PDO connection.
     *
     * @var    string
     * @access private
     */
    private $_dsn;

    /**
     * The current PDO instance.
     *
     * @var    \PDO
     * @access private
     */
    private $_pdo = null;

    /**
     * The where clause.
     *
     * @var    string
     * @access private
     */
    private $_where = null;

    /**
     * The order clause.
     *
     * @var    string
     * @access private
     */
    private $_order = null;

    /**
     * The limit clause.
     *
     * @var    string
     * @access private
     */
    private $_limit = null;

    /**
     * The "group by" clause
     *
     * @var    string
     * @access private
     */
    private $_group = null;

    /**
     * The distinct clause
     *
     * @var    bool
     * @access private
     */
    private $_distinct = false;

    /**
     * The computed query string.
     *
     * @var    string
     * @access private
     */
    private $_queryString = null;

    /**
     * Class __constructor
     *
     * @param array $options The lists of options
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function __construct(array $options = null)
    {
        if (!is_array($options)) {
            return false;
        }

        $attr = array();

        if (isset($options["dbms"])) {
            $this->_dbms = strtolower($options["dbms"]);
        }

        if (isset($options["options"])) {
            $this->_options = $options["options"];
        }

        if (isset($options["command"]) && is_array($options["command"])) {
            $commands = $options["command"];
        } else {
            $commands = [];
        }

        if (isset($options["dsn"])) {
            if (is_array($options["dsn"]) && isset($options["dsn"]["driver"])) {
                $this->_driver = $options["dsn"]["driver"];
                unset($options["dsn"]["driver"]);
                $attr = $options["dsn"];
            } else {
                return false;
            }
        } else {
            if (isset($options["port"]) && is_int($options["port"] * 1)) {
                $port = $options["port"];
            }

            switch ($this->_dbms) {
                case "mariadb":
                case "mysql":
                    $this->_driver = "mysql";
                    $attr = array(
                        "dbname" => $options["database"]
                    );

                    if (isset($options["socket"])) {
                        $attr["unix_socket"] = $options["socket"];
                    } else {
                        $attr["host"] = $options["hostname"];
                        if (isset($port)) {
                            $attr["port"] = $port;
                        }
                    }

                    // Make MySQL using standard quoted identifier
                    $commands[] = "SET SQL_MODE=ANSI_QUOTES";
                    break;

                case "pgsql":
                    $this->_driver = "pgsql";
                    $attr = array(
                        "host" => $options["hostname"],
                        "dbname" => $options['database']
                    );

                    if (isset($port)) {
                        $attr["port"] = $port;
                    }
                    break;

                case "sybase":
                    $this->_driver = "dblib";
                    $attr = array(
                        "host" => $options["hostname"],
                        "dbname" => $options["database"]
                    );

                    if (isset($port)) {
                        $attr["port"] = $port;
                    }
                    break;

                case "oracle":
                    $this->_driver = "oci";
                    $attr = array(
                        "dbname" => $options["hostname"] ?
                            "//{$options['server']}" . (isset($port) ? ":{$port}" : ":1521") . "/{$options['database']}" :
                            $options['database']
                    );

                    if (isset($options["charset"])) {
                        $attr["charset"] = $options["charset"];
                    }
                    break;

                case "mssql":
                    if (isset($options["driver"]) && $options["driver"] === "dblib") {
                        $this->_driver = "dblib";
                        $attr = array(
                            "host" => $options["hostname"] . (isset($port) ? ":{$port}" : ""),
                            "dbname" => $options["database"]
                        );
                    } else {
                        $this->_driver = "sqlsrv";
                        $attr = array(
                            "Server" => $options["hostname"] . (isset($port) ? ",{$port}" : ""),
                            "Database" => $options["database"]
                        );
                    }

                    // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
                    $commands[] = "SET QUOTED_IDENTIFIER ON";
                    // Make ANSI_NULLS is ON for NULL value
                    $commands[] = "SET ANSI_NULLS ON";
                    break;

                case "sqlite":
                    $this->_driver = "sqlite";
                    $attr = array(
                        $options['database']
                    );
                    break;
            }
        }

        $stack = [];
        foreach ($attr as $key => $value) {
            $stack[] = is_int($key) ? $value : "{$key}={$value}";
        }

        $this->_dsn = $this->_driver . ":" . implode($stack, ";");

        if (in_array($this->_dbms, ['mariadb', 'mysql', 'pgsql', 'sybase', 'mssql']) && isset($options['charset'])) {
            $commands[] = "SET NAMES '{$options['charset']}'";
        }

        $this->hostname = $options["hostname"];
        $this->database = $options["database"];
        $this->username = isset($options['username']) ? $options['username'] : null;
        $this->password = isset($options['password']) ? $options['password'] : null;

        $this->_instantiate();

        foreach ($commands as $value) {
            $this->_pdo->exec($value);
        }

        return $this;
    }

    /**
     * Closes a connection
     *
     * @return void
     */
    public function close(): void
    {
        $this->_pdo = false;
    }

    /**
     * Connect to the database / Instantiate PDO
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException When the connexion fails.
     *
     * @return void
     */
    private function _instantiate(): void
    {
        try {
            $this->_pdo = new \PDO(
                $this->_dsn,
                $this->username,
                $this->password,
                $this->_options
            );
        } catch (\PDOException $e) {
            throw new LightQLException($e->getMessage());
        }
    }

    /**
     * Gets the current query string.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->_queryString;
    }

    /**
     * Changes the currently used table
     *
     * @param string $table The table's name
     *
     * @return \ElementaryFramework\LightQL\LightQL
     */
    public function from(string $table): LightQL
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Add a where condition.
     *
     * @param string|array $condition SQL condition in valid format
     *
     * @return \ElementaryFramework\LightQL\LightQL
     */
    public function where($condition): LightQL
    {
        // where(array('field1'=>'value', 'field2'=>'value'))
        $this->_where = (null !== $this->_where) ? "{$this->_where} OR (" : "(";
        if (is_array($condition)) {
            $i = 0;
            $operand = "=";
            foreach ($condition as $column => $value) {
                $this->_where .= ($i > 0) ? " AND " : "";
                if (is_int($column)) {
                    $this->_where .= $value;
                } else {
                    $parts = explode(" ", $this->parseValue($value));
                    foreach (self::$_operators as $operator) {
                        if (in_array($operator, $parts, true) && $parts[0] === $operator) {
                            $operand = $operator;
                        }
                    }
                    $this->_where .= "{$column} {$operand} " . str_replace($operand, "", $value);
                    $operand = "=";
                }
                ++$i;
            }
        } else {
            $this->_where .= $condition;
        }
        $this->_where .= ")";

        return $this;
    }

    /**
     * Add an order clause.
     *
     * @param string $column The column to sort.
     * @param string $mode   The sort mode.
     *
     * @return \ElementaryFramework\LightQL\LightQL
     */
    public function order(string $column, string $mode = "ASC"): LightQL
    {
        $this->_order = " ORDER BY {$column} {$mode} ";
        return $this;
    }

    /**
     * Add a limit clause.
     *
     * @param int $offset The limit offset.
     * @param int $count  The number of elements after the offset.
     *
     * @return \ElementaryFramework\LightQL\LightQL
     */
    public function limit(int $offset, int $count): LightQL
    {
        $this->_limit = " LIMIT {$offset}, {$count} ";
        return $this;
    }

    /**
     * Add a group clause.
     *
     * @param string $column The column used to group results.
     *
     * @return \ElementaryFramework\LightQL\LightQL
     */
    public function groupBy(string $column): LightQL
    {
        $this->_group = $column;
        return $this;
    }

    /**
     * Add a distinct clause.
     *
     * @return \ElementaryFramework\LightQL\LightQL
     */
    public function distinct(): LightQL
    {
        $this->_distinct = true;
        return $this;
    }

    /**
     * Selects data in database.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return \PDOStatement
     */
    public function select($columns = "*"): \PDOStatement
    {
        return $this->_select($columns);
    }

    /**
     * Executes the SELECT SQL query.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return \PDOStatement
     */
    private function _select($columns): \PDOStatement
    {
        // Constructing the fields list
        if (is_array($columns)) {
            $_fields = "";

            foreach ($columns as $column => $alias) {
                if (is_int($column)) {
                    $_fields .= "{$alias}, ";
                } elseif (is_string($column)) {
                    $_fields .= "{$column} AS {$alias}, ";
                } else {
                    throw new LightQLException(
                        "Invalid data given for the parameter \$columns." .
                        " Only string and array are supported."
                    );
                }
            }

            $columns = trim($_fields, ", ");
        }

        // Constructing the SELECT query string
        $this->_queryString = trim("SELECT" . (($this->_distinct) ? " DISTINCT " : " ") . "{$columns} FROM {$this->table}" . ((null !== $this->_where) ? " WHERE {$this->_where}" : " ") . ((null !== $this->_order) ? $this->_order : " ") . ((null !== $this->_limit) ? $this->_limit : " ") . ((null !== $this->_group) ? "GROUP BY {$this->_group}" : " "));

        // Preparing the query
        $getFieldsData = $this->prepare($this->_queryString);

        // Executing the query
        if ($getFieldsData->execute() !== false) {
            $this->resetClauses();

            return $getFieldsData;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Prepares a query.
     *
     * @param string $query   The query to execute
     * @param array  $options PDO options
     *
     * @uses \PDO::prepare()
     *
     * @return \PDOStatement
     */
    public function prepare(string $query, array $options = array()): \PDOStatement
    {
        return $this->_pdo->prepare($query, $options);
    }

    /**
     * Reset all clauses.
     */
    protected function resetClauses()
    {
        $this->_distinct = false;
        $this->_where = null;
        $this->_order = null;
        $this->_limit = null;
        $this->_group = null;
    }

    /**
     * Selects the first data result of the query.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return array
     */
    public function selectFirst($columns = "*")
    {
        $result = $this->selectArray($columns);

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * Selects data as array of arrays in database.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return array
     */
    public function selectArray($columns = "*"): array
    {
        $select = $this->_select($columns);
        $result = array();

        while ($r = $select->fetch(\PDO::FETCH_LAZY)) {
            $result[] = array_diff_key((array)$r, array("queryString" => "queryString"));
        }

        return $result;
    }

    /**
     * Selects data as array of objects in database.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return array
     */
    public function selectObject($columns = "*"): array
    {
        $select = $this->_select($columns);
        $result = array();

        while ($r = $select->fetch(\PDO::FETCH_OBJ)) {
            $result[] = $r;
        }

        return $result;
    }

    /**
     * Selects data in database with table joining.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     * @param mixed $params  The information used for JOIN.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return \PDOStatement
     */
    public function join($columns, $params): \PDOStatement
    {
        return $this->_join($columns, $params);
    }

    /**
     * Executes a SELECT ... JOIN query.
     *
     * @param string|array $columns The fields to select. This value can be an array of fields,
     *                              or a string of fields (according to the SELECT SQL query syntax).
     * @param string|array $params  The information used for JOIN.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return \PDOStatement
     */
    private function _join($columns, $params): \PDOStatement
    {
        $joints = $params;

        if (is_array($columns)) {
            $columns = implode(",", $columns);
        }

        if (is_array($params)) {
            $joints = "";

            foreach ($params as $param) {
                if (is_array($param)) {
                    $joints .= " {$param['side']} JOIN {$param['table']} ON {$param['cond']} ";
                } elseif (is_string($param)) {
                    $joints .= " {$param} ";
                } else {
                    throw new LightQLException("Invalid value used for join.");
                }
            }
        }

        $this->_queryString = trim("SELECT" . (($this->_distinct) ? " DISTINCT " : " ") . "{$columns} FROM {$this->table} {$joints}" . ((null !== $this->_where) ? " WHERE {$this->_where}" : " ") . ((null !== $this->_order) ? $this->_order : " ") . ((null !== $this->_limit) ? $this->_limit : ""));

        $getFieldsData = $this->prepare($this->_queryString);

        if ($getFieldsData->execute() !== false) {
            $this->resetClauses();
            return $getFieldsData;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Selects data as array of arrays in database with table joining.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     * @param mixed $params  The information used for JOIN.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return array
     */
    public function joinArray($columns, $params): array
    {
        $join = $this->_join($columns, $params);
        $result = array();

        while ($r = $join->fetch(\PDO::FETCH_LAZY)) {
            $result[] = array_diff_key((array)$r, array("queryString" => "queryString"));
        }

        return $result;
    }

    /**
     * Selects data as array of objects in database with table joining.
     *
     * @param mixed $columns The fields to select. This value can be an array of fields,
     *                       or a string of fields (according to the SELECT SQL query syntax).
     * @param mixed $params  The information used for JOIN.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return array
     */
    public function joinObject($columns, $params): array
    {
        $join = $this->_join($columns, $params);
        $result = array();

        while ($r = $join->fetch(\PDO::FETCH_OBJ)) {
            $result[] = $r;
        }

        return $result;
    }

    /**
     * Counts data in table.
     *
     * @param string|array $columns The fields to select. This value can be an array of fields,
     *                              or a string of fields (according to the SELECT SQL query syntax).
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return int|array
     */
    public function count($columns = "*")
    {
        if (is_array($columns)) {
            $column = implode(",", $columns);
        }

        $this->_queryString = trim("SELECT" . ((null !== $this->_group) ? "{$this->_group}," : " ") . "COUNT(" . ((isset($column)) ? $column : $columns) . ") AS lightql_count FROM {$this->table}" . ((null !== $this->_where) ? " WHERE {$this->_where}" : " ") . ((null !== $this->_limit) ? $this->_limit : " ") . ((null !== $this->_group) ? "GROUP BY {$this->_group}" : " "));

        $getFieldsData = $this->prepare($this->_queryString);

        if ($getFieldsData->execute() !== false) {
            if (null === $this->_group) {
                $this->resetClauses();
                $data = $getFieldsData->fetch();
                return (int) $data['lightql_count'];
            }

            $this->resetClauses();
            $res = array();

            while ($data = $getFieldsData->fetch()) {
                $res[$data[$this->_group]] = $data['lightql_count'];
            }

            return $res;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Inserts one set of data in table.
     *
     * @param array $fieldsAndValues The fields and the associated values to insert.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return boolean
     */
    public function insert(array $fieldsAndValues): bool
    {
        $columns = array();
        $values = array();

        foreach ($fieldsAndValues as $column => $value) {
            $columns[] = $column;
            $values[] = $this->parseValue($value);
        }

        $column = implode(",", $columns);
        $value = implode(",", $values);

        $this->_queryString = trim("INSERT INTO {$this->table}({$column}) VALUE ({$value})");

        $getFieldsData = $this->prepare($this->_queryString);

        if ($getFieldsData->execute() !== false) {
            $this->resetClauses();
            return true;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Inserts a multiple set of data at once in table.
     *
     * @param array $columns The list of fields to use.
     * @param array $values  The array of list of values to insert
     *                       into the specified fields.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return boolean
     */
    public function insertMany(array $columns, array $values): bool
    {
        $column = implode(",", $columns);

        $this->_queryString = "INSERT INTO {$this->table}({$column}) VALUES";

        foreach ($values as $i => $value) {
            $value = implode(",", array_map(array($this, "parseValue"), $value));
            $this->_queryString .= ($i === 0 ? "" : ", ") . " ({$value})";
        }

        $getFieldsData = $this->prepare($this->_queryString);

        if ($getFieldsData->execute() !== false) {
            $this->resetClauses();
            return true;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Updates data in table.
     *
     * @param array $fieldsAndValues The fields and the associated values to update.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return boolean
     */
    public function update(array $fieldsAndValues): bool
    {
        $updates = "";
        $count = count($fieldsAndValues);

        if (is_array($fieldsAndValues)) {
            foreach ($fieldsAndValues as $column => $value) {
                $count--;
                $updates .= "{$column} = " . $this->parseValue($value);
                $updates .= ($count != 0) ? ", " : "";
            }
        } else {
            $updates = $fieldsAndValues;
        }

        $this->_queryString = trim("UPDATE {$this->table} SET {$updates}" . ((null !== $this->_where) ? " WHERE {$this->_where}" : ""));

        $getFieldsData = $this->prepare($this->_queryString);

        if ($getFieldsData->execute() !== false) {
            $this->resetClauses();
            return true;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Deletes data in table.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return boolean
     */
    public function delete(): bool
    {
        $this->_queryString = trim("DELETE FROM {$this->table}" . ((null !== $this->_where) ? " WHERE {$this->_where}" : ""));

        $getFieldsData = $this->prepare($this->_queryString);

        if ($getFieldsData->execute() !== false) {
            $this->resetClauses();
            return true;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Truncates a table.
     *
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     *
     * @return boolean
     */
    public function truncate(): bool
    {
        $this->_queryString = "TRUNCATE {$this->table}";

        $getFieldsData = $this->prepare($this->_queryString);

        if ($getFieldsData->execute() !== false) {
            $this->resetClauses();
            return true;
        } else {
            throw new LightQLException($getFieldsData->errorInfo()[2]);
        }
    }

    /**
     * Executes a query.
     *
     * @param string $query The query to execute
     * @param int    $mode  The fetch mode
     *
     * @uses \PDO::query()
     *
     * @return \PDOStatement
     */
    public function query(string $query, int $mode = \PDO::FETCH_LAZY): \PDOStatement
    {
        return $this->_pdo->query($query, $mode);
    }

    /**
     * Gets the last inserted id by an
     * INSERT query.
     *
     * @uses \PDO::lastInsertId()
     *
     * @return int
     */
    public function lastInsertID(): int
    {
        return intval($this->_pdo->lastInsertId());
    }

    /**
     * Quotes a value.
     *
     * @param mixed $value The value to quote.
     *
     * @uses \PDO::quote()
     *
     * @return string
     */
    public function quote($value): string
    {
        return $this->_pdo->quote($value);
    }

    /**
     * Disable auto commit mode and start a transaction.
     *
     * @uses \PDO::beginTransaction()
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->_pdo->beginTransaction();
    }

    /**
     * Commit changes made during a transaction.
     *
     * @uses \PDO::commit()
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->_pdo->commit();
    }

    /**
     * Rollback changes made during a transaction.
     *
     * @uses \PDO::rollBack()
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->_pdo->rollBack();
    }

    /**
     * Converts a value to a string.
     *
     * @param mixed $value The value to convert.
     *
     * @return string
     */
    public function parseValue($value): string
    {
        if (is_null($value)) {
            return "NULL";
        } elseif (is_bool($value)) {
            return $value ? "1" : "0";
        } else {
            return strval($value);
        }
    }
}
