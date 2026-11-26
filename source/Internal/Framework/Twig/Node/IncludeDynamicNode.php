<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Node;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IncludeExtension;
use Twig\Compiler;
use Twig\Node\IncludeNode;

/**
 * Class IncludeDynamic
 */
class IncludeDynamicNode extends IncludeNode
{
    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $this->preCompile($compiler);

        if ($this->hasNode('variables')) {
            $compiler->write('$parameters = ');
            $compiler->subcompile($this->getNode('variables'));
            $compiler->raw(";\n");
        }

        $compiler->write("if (!empty(\$context[\"_render4cache\"])) {\n");
        $compiler->indent();
        $this->ifRender4Cache($compiler);
        $compiler->outdent();
        $compiler->write("} else {\n");
        $compiler->indent();
        $this->ifNotRender4Cache($compiler);
        $compiler->outdent();
        $compiler->write("}\n");

        $this->postCompile($compiler);
    }

    /**
     * @param Compiler $compiler
     */
    private function preCompile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        if ($this->getAttribute('ignore_missing')) {
            $compiler
                ->write("try {\n")
                ->indent();
        }
    }

    /**
     * @param Compiler $compiler
     */
    private function ifRender4Cache(Compiler $compiler): void
    {
        $compiler->write('yield $this');
        $compiler->raw("->extensions['" . IncludeExtension::class . "']");
        if ($this->hasNode('variables')) {
            $compiler->raw("->renderForCache(\$parameters);\n");
        } else {
            $compiler->raw("->renderForCache([]);\n");
        }
    }

    /**
     * @param Compiler $compiler
     */
    private function ifNotRender4Cache(Compiler $compiler): void
    {
        if ($this->hasNode('variables')) {
            $compiler->write('$parameters = $this');
            $compiler->raw("->extensions['" . IncludeExtension::class . "']");
            $compiler->raw("->includeDynamicPrefix(\$parameters);\n");
        }

        $this->includeTemplate($compiler);
    }

    /**
     * @param Compiler $compiler
     */
    private function includeTemplate(Compiler $compiler): void
    {
        $compiler
            ->write('yield from $this->loadTemplate(')
            ->subcompile($this->getNode('expr'))
            ->raw(', ')
            ->repr($this->getTemplateName())
            ->raw(', ')
            ->repr($this->getTemplateLine())
            ->raw(')->unwrap()->yield(');

        $this->addTemplateArguments($compiler);

        $compiler->raw(");\n");
    }

    /**
     * @param Compiler $compiler
     */
    protected function addTemplateArguments(Compiler $compiler)
    {
        if (!$this->hasNode('variables')) {
            $compiler->raw(false === $this->getAttribute('only') ? '$context' : '[]');
        } elseif (false === $this->getAttribute('only')) {
            $compiler->raw('array_merge($context, $parameters)');
        } else {
            $compiler->raw('$parameters');
        }
    }

    /**
     * @param Compiler $compiler
     */
    private function postCompile(Compiler $compiler): void
    {
        if ($this->getAttribute('ignore_missing')) {
            $compiler
                ->outdent()
                ->write("} catch (\\Twig\\Error\\LoaderError \$e) {\n")
                ->indent()
                ->write("// ignore missing template\n")
                ->outdent()
                ->write("}\n\n");
        }
    }
}
