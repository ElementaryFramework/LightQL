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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateDatabaseCommand
 */
class GenerateDatabaseCommand extends Command
{
    const ARGUMENT_PERSISTENCE_UNIT = "persistence-unit";
    const ARGUMENT_PERSISTENCE_UNIT_SHORT = "p";

    const ARGUMENT_INPUT_DIR = "input";
    const ARGUMENT_INPUT_DIR_SHORT = "i";

    public function __construct()
    {
        parent::__construct("generate:database");
    }

    public function configure()
    {
        $this
            ->setDescription("Generate database from LightQL entities")
            ->addOption(self::ARGUMENT_PERSISTENCE_UNIT, self::ARGUMENT_PERSISTENCE_UNIT_SHORT, InputOption::VALUE_REQUIRED, "The path to the LightQL persistence unit file.")
            ->addOption(self::ARGUMENT_INPUT_DIR, self::ARGUMENT_INPUT_DIR_SHORT, InputOption::VALUE_REQUIRED, "The path to the directory in which LightQL entities resides.");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
    }
}