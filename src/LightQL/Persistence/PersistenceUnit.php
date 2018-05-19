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

namespace ElementaryFramework\LightQL\Persistence;

use ElementaryFramework\LightQL\Exceptions\PersistenceUnitException;

/**
 * Persistence Unit
 *
 * Configures parameters to use for database connection.
 *
 * @category Persistence
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Persistence/PersistenceUnit
 */
class PersistenceUnit
{
    /**
     * @var string
     */
    private $_dbms;

    /**
     * @var string
     */
    private $_hostname;

    /**
     * @var string
     */
    private $_database;

    /**
     * @var string
     */
    private $_username;

    /**
     * @var string
     */
    private $_password;

    /**
     * @var array
     */
    private static $_registry = array();

    /**
     * @var PersistenceUnit[]
     */
    private static $_units = array();

    /**
     * Registers a new persistence unit.
     *
     * @param string $key  The name of the persistence unit.
     * @param string $path The path to the persistence unit file.
     */
    public static function register(string $key, string $path)
    {
        self::$_registry[$key] = $path;
    }

    /**
     * Cleans the persistence unit registry and cache.
     */
    public static function purge()
    {
        self::$_registry = array();
        self::$_units = array();
    }

    /**
     * PersistenceUnit constructor.
     *
     * @param string $key The persistence unit name.
     *
     * @throws PersistenceUnitException
     */
    private function __construct(string $key)
    {
        if (array_key_exists($key, self::$_registry)) {
            $filename = basename(self::$_registry[$key]);
            $parts = explode(".", $filename);
            $extension = $parts[count($parts) - 1];

            $content = null;
            if ($extension === "ini") {
                $content = parse_ini_file(self::$_registry[$key]);
            } elseif ($extension === "json") {
                $content = json_decode(file_get_contents(self::$_registry[$key]), true);
            } else {
                throw new PersistenceUnitException("Unsupported file type used to create persistence unit {$filename}.");
            }

            if (array_key_exists("DBMS", $content)) {
                $this->_dbms = $content["DBMS"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the DBMS value.");
            }

            if (array_key_exists("Hostname", $content)) {
                $this->_hostname = $content["Hostname"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the Hostname value.");
            }

            if (array_key_exists("DatabaseName", $content)) {
                $this->_database = $content["DatabaseName"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the DatabaseName value.");
            }

            if (array_key_exists("Username", $content)) {
                $this->_username = $content["Username"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the Username value.");
            }

            if (array_key_exists("Password", $content)) {
                $this->_password = $content["Password"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the Password value.");
            }
        } else {
            throw new PersistenceUnitException('Unable to find the persistence unit with the key "' . $key . '". Have you registered this persistence unit?');
        }
    }

    /**
     * Creates a new persistence unit by the given key.
     *
     * @param string $key The persistence unit name.
     *
     * @return PersistenceUnit
     */
    public static function create(string $key)
    {
        if (array_key_exists($key, self::$_units)) {
            return self::$_units[$key];
        } else {
            return (self::$_units[$key] = new self($key));
        }
    }

    /**
     * @return string
     */
    public function getDbms()
    {
        return $this->_dbms;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->_database;
    }

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->_hostname;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->_password;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->_username;
    }
}
