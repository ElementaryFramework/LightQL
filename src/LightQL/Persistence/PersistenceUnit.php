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
final class PersistenceUnit
{
    /**
     * The database management system (postgres, sqlite, mysql, etc.)
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
     * The database connection port.
     */
    private(set) ?string $port = null;

    /**
     * The list of registered persistence unit files.
     *
     * @var array<string, string>
     */
    private static array $_registry = [];

    /**
     * @var list<PersistenceUnit>
     */
    private static array $_units = [];

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
            $filepath = self::$_registry[$key];

            if (!file_exists($filepath)) {
                throw new PersistenceUnitException("The persistence unit file at the path '{$filepath}' cannot be found.");
            }

            $filename = basename($filepath);
            $parts = explode(".", $filename);
            $extension = $parts[count($parts) - 1];

            $content = [];

            if ($extension === "ini") {
                $content = $this->parseIniConfig($filepath);
            } elseif ($extension === "json") {
                $content = $this->parseJsonConfig($filepath);
            } elseif ($extension === "xml") {
                $content = $this->parseXmlConfig($filepath);
            } else {
                throw new PersistenceUnitException("Unsupported file type used to create persistence unit {$filename}.");
            }

            if (empty($content)) {
                throw new PersistenceUnitException("Unable to parse persistence unit configuration file '{$filename}'.");
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

            if (array_key_exists("Port", $content)) {
                $this->port = $content["Port"];
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
    public static function create(string $key): self
    {
        return array_key_exists($key, self::$_units)
            ? self::$_units[$key]
            : ((self::$_units[$key] = new self($key)));
    }

    /**
     * Parse a persistence unit in INI format.
     *
     * @param string $filepath The persistence unit file.
     * @return array<string, string>
     */
    private function parseIniConfig(string $filepath): array
    {
        return parse_ini_file($filepath) ?: [];
    }

    /**
     * Parse a persistence unit in JSON format.
     *
     * @param string $filepath The persistence unit file.
     * @return array<string, string>
     */
    private function parseJsonConfig(string $filepath): array
    {
        return json_decode(file_get_contents($filepath), true);
    }

    /**
     * Parse a persistence unit in XML format.
     *
     * @param string $filepath The persistence unit file.
     * @return array<string, string>
     *
     * @throws PersistenceUnitException
     */
    private function parseXmlConfig(string $filepath): array
    {
        if (!extension_loaded("dom"))
            return [];

        $content = [];

        $dom = new \DOMDocument("1.0", "utf-8");
        $dom->loadXML(file_get_contents($filepath));

        if ($dom->documentElement->nodeName !== "PersistenceUnit") {
            throw new PersistenceUnitException("Invalid persistence unit XML configuration file provided.");
        }

        /** @var \DOMElement $node */
        foreach ($dom->documentElement->childNodes as $node) {
            if ($node->nodeType === \XML_TEXT_NODE) {
                continue;
            }

            if (in_array($node->nodeName, ['DBMS', 'Hostname', 'DatabaseName', 'Username', 'Password', 'Port'])) {
                $content[$node->nodeName] = $node->nodeValue;
            }
        }

        return $content;
    }
}
