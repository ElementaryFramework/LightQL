<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Persistence;

use ElementaryFramework\LightQL\Enums\DBMS;
use ElementaryFramework\LightQL\Exceptions\PersistenceUnitException;

/**
 * Persistence Unit
 *
 * Configures parameters to use for database connection.
 */
class PersistenceUnit
{
    /**
     * The DBMS.
     */
    private(set) DBMS $dbms;

    /**
     * The database server address.
     */
    private(set) string $hostname;

    /**
     * The database name.
     */
    private(set) string $database;

    /**
     * The username to use on connection.
     */
    private(set) string $username;

    /**
     * The password associated to the username.
     */
    private(set) string $password;

    /**
     * The list of registered persistence unit files.
     *
     * @var array<string, string>
     */
    private static array $_registry = array();

    /**
     * @var list<PersistenceUnit>
     */
    private static array $_units = array();

    /**
     * Registers a new persistence unit.
     *
     * @param string $key The name of the persistence unit.
     * @param string $path The path to the persistence unit file.
     */
    public static function register(string $key, string $path): void
    {
        self::$_registry[$key] = $path;
    }

    /**
     * Cleans the persistence unit registry and cache.
     */
    public static function purge(): void
    {
        self::$_registry = [];
        self::$_units = [];
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
                $this->dbms = DBMS::from($content["DBMS"]);
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the DBMS value.");
            }

            if (array_key_exists("Hostname", $content)) {
                $this->hostname = $content["Hostname"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the Hostname value.");
            }

            if (array_key_exists("DatabaseName", $content)) {
                $this->database = $content["DatabaseName"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the DatabaseName value.");
            }

            if (array_key_exists("Username", $content)) {
                $this->username = $content["Username"];
            } else {
                throw new PersistenceUnitException("Malformed persistence unit configuration file, missing the Username value.");
            }

            if (array_key_exists("Password", $content)) {
                $this->password = $content["Password"];
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
     * @return PersistenceUnit
     *
     * @throws PersistenceUnitException
     */
    public static function create(string $key): static
    {
        return array_key_exists($key, self::$_units)
            ? self::$_units[$key]
            : ((self::$_units[$key] = new static($key)));
    }
}
