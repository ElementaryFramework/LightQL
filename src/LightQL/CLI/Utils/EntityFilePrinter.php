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

namespace ElementaryFramework\LightQL\CLI\Utils;

class EntityFilePrinter extends \Nette\PhpGenerator\Printer
{
    public function printNamespace(\Nette\PhpGenerator\PhpNamespace $namespace): string
    {
        $name = $namespace->getName();

        $uses = [];
        foreach ($namespace->getUses() as $alias => $original) {
            if ($alias === $original || substr($original, -(strlen($alias) + 1)) === '\\' . $alias) {
                $uses[$original] = "use {$original};";
            } else {
                $uses[$original] = "use {$original} as {$alias};";
            }
        }

        $classes = [];
        foreach ($namespace->getClasses() as $className => $class) {
            $classes[] = $this->printClass($class, $namespace);
            unset($uses["{$name}\\{$className}"]);
            unset($uses[$className]);
        }

        $body = ($uses ? implode("\n", $uses) . "\n\n" : '')
            . implode("\n", $classes);

        if ($namespace->getBracketedSyntax()) {
            return 'namespace' . ($name ? " {$name}" : '') . "\n{\n"
                . $this->indent($body)
                . "}\n";

        } else {
            return ($name ? "namespace {$name};\n\n" : '')
                . $body;
        }
    }
}