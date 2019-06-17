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

namespace ElementaryFramework\LightQL\CLI\Commands;

use ElementaryFramework\LightQL\CLI\Utils\EntityFilePrinter;
use ElementaryFramework\LightQL\Entities\Entity;
use ElementaryFramework\LightQL\LightQL;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;
use ElementaryFramework\LightQL\Entities\IPrimaryKey;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidOptionException;

/**
 * Class GenerateEntitiesCommand
 */
class GenerateEntitiesCommand extends Command
{
    const OPTION_FROM_DATABASE = "from-database";
    const OPTION_FROM_DATABASE_SHORT = "D";

    const OPTION_FROM_SCHEMA = "from-schema";
    const OPTION_FROM_SCHEMA_SHORT = "S";

    const OPTION_PERSISTENCE_UNIT = "persistence-unit";
    const OPTION_PERSISTENCE_UNIT_SHORT = "p";

    const OPTION_OUTPUT_DIR = "output";
    const OPTION_OUTPUT_DIR_SHORT = "o";

    const OPTION_NAMESPACE = "namespace";

    /**
     * @var LightQL
     */
    private $_light;

    /**
     * @var PersistenceUnit
     */
    private $_pu;

    public function __construct()
    {
        parent::__construct("generate:entities");
    }

    public function configure()
    {
        $this
            ->setDescription("Generate LightQL entities")
            ->addOption(self::OPTION_FROM_DATABASE, self::OPTION_FROM_DATABASE_SHORT, InputOption::VALUE_NONE, "Generate entities from a database", null)
            ->addOption(self::OPTION_FROM_SCHEMA, self::OPTION_FROM_SCHEMA_SHORT, InputOption::VALUE_NONE, "Generate entities from a LightQL database schema", null)
            ->addOption(self::OPTION_PERSISTENCE_UNIT, self::OPTION_PERSISTENCE_UNIT_SHORT, InputOption::VALUE_OPTIONAL, "The path to the LightQL persistence unit file.")
            ->addOption(self::OPTION_OUTPUT_DIR, self::OPTION_OUTPUT_DIR_SHORT, InputOption::VALUE_OPTIONAL, "The path to the directory in which generated files will output.", ".")
            ->addOption(self::OPTION_NAMESPACE, null, InputOption::VALUE_OPTIONAL, "The namespace of entity classes.", false);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::OPTION_FROM_DATABASE) !== false && $input->getOption(self::OPTION_FROM_SCHEMA) !== false)
            throw new InvalidOptionException("You have to choose to generate entities either from a database or from a schema.");
        elseif ($input->getOption(self::OPTION_FROM_DATABASE) !== false || ($input->getOption(self::OPTION_FROM_DATABASE) === false && $input->getOption(self::OPTION_FROM_SCHEMA) === false))
            $this->_generateFromDatabase($input, $output);
        elseif ($input->getOption(self::OPTION_FROM_SCHEMA) !== false)
            $this->_generateFromSchema($input, $output);
        else throw new InvalidOptionException("You have to choose to generate entities either from a database or from a schema.");
    }

    public function _generateFromDatabase(InputInterface &$input, OutputInterface &$output)
    {
        // Register the defined persistence unit
        PersistenceUnit::register(self::OPTION_PERSISTENCE_UNIT_SHORT, $input->getOption(self::OPTION_PERSISTENCE_UNIT));

        // Create the PU
        $this->_pu = PersistenceUnit::create(self::OPTION_PERSISTENCE_UNIT_SHORT);

        // Create a LightQL instance
        $this->_light = new LightQL(
            array(
                "dbms" => $this->_pu->getDbms(),
                "database" => $this->_pu->getDatabase(),
                "hostname" => $this->_pu->getHostname(),
                "username" => $this->_pu->getUsername(),
                "password" => $this->_pu->getPassword(),
                "port" => $this->_pu->getPort()
            )
        );

        // Get all tables of database
        $results = $this->_light->from("information_schema.tables")
            ->where(array("table_schema" => $this->_light->quote($this->_pu->getDatabase())))
            ->selectArray("table_name");

        $i = 0;

        $output->writeln("Generating entities...");

        foreach ($results as $table) {
            // Generate entity
            $this->_generateEntity($table["table_name"], $input, $output);
            $i++;
        }

        $output->writeln("Successfully generated <comment>{$i}</comment> entities from <comment>{$i}</comment> tables\n");
    }

    public function _generateFromSchema(InputInterface &$input, OutputInterface &$output)
    {

    }

    private function _generateEntity(string $name, InputInterface $input, OutputInterface $output)
    {
        // Create file
        $file = (new \Nette\PhpGenerator\PhpFile)
            ->addComment("THIS FILE IS GENERATED BY LIGHTQL\n")
            ->addComment("LightQL entity class for the table \"{$name}\"\n")
            ->addComment("Generated at " . date("Y-m-d H:i:s"));

        $class = null;
        $namespace = null;
        $properties = array();
        $className = $this->_generateObjectName($name);

        $pkFile = null;
        $pkClass = null;
        $pKeys = array();
        $pkClassName = $className . "PK";

        if ($input->getOption(self::OPTION_NAMESPACE) !== false) {
            $namespace = $file->addNamespace($input->getOption(self::OPTION_NAMESPACE));
            $namespace->addUse(Entity::class);
            $class = $namespace->addClass($className);
        } else {
            $file->addUse(Entity::class);
            $class = $file->addClass($className);
        }

        $class
            ->setExtends(Entity::class)
            ->addComment("Entity {$className}\n")
            ->addComment("@entity('{$name}')")
            ->addComment("@namedQuery('findAll', 'SELECT * FROM {$name}')");

        // Get all columns of database
        $results = $this->_light->from("information_schema.columns")
            ->where(
                array(
                    "table_schema" => $this->_light->quote($this->_pu->getDatabase()),
                    "table_name" => $this->_light->quote($name),
                )
            )
            ->selectArray();

        foreach ($results as $result) {
            $colName = $result["COLUMN_NAME"];
            $colType = $result["DATA_TYPE"];
            $colDefault = $result["COLUMN_DEFAULT"];

            $propertyName = $this->_generateObjectName($colName);

            // Get tables keys properties
            $keys_results = $this->_light->from("information_schema.key_column_usage")
                ->where(
                    array(
                        "information_schema.table_constraints.table_schema" => $this->_light->quote($this->_pu->getDatabase()),
                        "information_schema.table_constraints.table_name" => $this->_light->quote($name),
                        "information_schema.key_column_usage.table_schema" => $this->_light->quote($this->_pu->getDatabase()),
                        "information_schema.key_column_usage.table_name" => $this->_light->quote($name),
                        "information_schema.key_column_usage.column_name" => $this->_light->quote($colName),
                    )
                )
                ->joinArray(
                    array(
                        "information_schema.key_column_usage.*",
                        "information_schema.table_constraints.CONSTRAINT_TYPE",
                    ),
                    array(
                        array(
                            "side" => "INNER",
                            "table" => "information_schema.table_constraints",
                            "cond" => "information_schema.table_constraints.CONSTRAINT_NAME = information_schema.key_column_usage.CONSTRAINT_NAME"
                        )
                    )
                );

            $properties[$propertyName] = new \Nette\PhpGenerator\Property($propertyName);

            $isForeignKey = count($keys_results) > 0;

            foreach ($keys_results as $keys_result) {
                if (strtolower($keys_result["CONSTRAINT_TYPE"]) === "primary key") {
                    $properties[$propertyName]->addComment("@id");
                    $pKeys[] = array($properties[$propertyName], $colName);
                    $isForeignKey = false;
                }

                if (strtolower($keys_result["CONSTRAINT_TYPE"]) === "unique") {
                    $class->addComment("@namedQuery('findBy{$propertyName}', 'SELECT * FROM {$name} WHERE {$name}.{$colName} = :{$propertyName}')");
                    $properties[$propertyName]->addComment("@unique");
                    $isForeignKey = false;
                }

                if (strtolower($keys_result["CONSTRAINT_TYPE"]) === "foreign key") {
                    $referencedEntity = $this->_generateObjectName($keys_result["REFERENCED_TABLE_NAME"]);
                    $referencedColumn = $keys_result["REFERENCED_COLUMN_NAME"];

                    if ($namespace !== null) {
                        $namespace->addUse($input->getOption(self::OPTION_NAMESPACE) . "\\{$referencedEntity}");
                    }

                    $properties[$propertyName]->addComment("@oneToMany('{$referencedEntity}', '{$referencedColumn}')");
                    $isForeignKey = true;
                }
            }

            if ($colDefault !== null) {
                $properties[$propertyName]->addComment("@column('{$colName}', '{$colType}', '{$colDefault}')");
            } else {
                $properties[$propertyName]->addComment("@column('{$colName}', '{$colType}')");
            }

            if (!$isForeignKey) {
                if (strtolower($result["EXTRA"]) === "auto_increment") {
                    $properties[$propertyName]->addComment("@autoIncrement");
                }

                if (strtolower($result["IS_NULLABLE"]) === "no") {
                    $properties[$propertyName]->addComment("@notNull");
                }

                if ($result["CHARACTER_MAXIMUM_LENGTH"] !== null) {
                    $properties[$propertyName]->addComment("@size({$result['CHARACTER_MAXIMUM_LENGTH']})");
                }
            }
        }

        if (count($pKeys) > 1) {
            $pkFile = (new \Nette\PhpGenerator\PhpFile)
                ->addComment("THIS FILE IS GENERATED BY LIGHTQL\n")
                ->addComment("LightQL entity primary key for the table \"{$name}\"\n")
                ->addComment("Generated at " . date("Y-m-d H:i:s"));

            if ($namespace !== null) {
                $pkNamespace = $pkFile->addNamespace($input->getOption(self::OPTION_NAMESPACE));
                $pkNamespace->addUse(IPrimaryKey::class);
                $pkClass = $pkNamespace->addClass($pkClassName);
            } else {
                $pkFile->addUse(IPrimaryKey::class);
                $pkClass = $pkFile->addClass($pkClassName);
            }

            $pkClass->addImplement(IPrimaryKey::class);

            foreach ($pKeys as $key) {
                $pkClass->addMember($key[0]);
            }

            $class->addProperty($pkClassName)->addComment("@id");
        } else {
            $class->addComment("@namedQuery('findById', 'SELECT * FROM {$name} WHERE {$name}.{$pKeys[0][1]} = :id')");
        }

        $printer = new EntityFilePrinter;

        $outClass = $printer->printFile($file);
        file_put_contents($input->getOption(self::OPTION_OUTPUT_DIR) . DIRECTORY_SEPARATOR . "{$className}.php", $outClass);
        $output->writeln("  - <info>Entity <comment>{$className}</comment> generated from table <comment>{$name}</comment></info>");

        if ($pkClass !== null) {
            $outClass = $printer->printFile($pkFile);
            file_put_contents($input->getOption(self::OPTION_OUTPUT_DIR) . DIRECTORY_SEPARATOR . "{$pkClassName}.php", $outClass);
            $output->writeln("    - <info>Primary Key Class <comment>{$pkClassName}</comment> generated from table <comment>{$name}</comment></info>");
        }
    }

    private function _generateObjectName(string $name): string
    {
        return implode("", array_map(function ($item) {
            return ucfirst(trim($item));
        }, preg_split("/[.-_]/", $name)));
    }

}