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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IfContentExtension;
use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Node;

/**
 * Class IfContentNode
 */
class IfContentNode extends Node
{
    /**
     * IfContentNode constructor.
     *
     * @param Node   $body
     * @param array  $reference
     * @param Node   $variable
     * @param int    $lineno
     * @param string $tag
     */
    public function __construct(Node $body, array $reference, Node $variable, int $lineno, string $tag = 'ifcontent')
    {
        $nodes = [
                     'body' => $body,
                     'variable' => $variable,
                 ] + $reference;

        parent::__construct($nodes, [], $lineno, $tag);
    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->subcompile($this->getNode('variable'), false)
            ->raw(' = ')
            ->raw("\$this->extensions['" . IfContentExtension::class . "']->getContent(");

        if ($this->hasNode('ident')) {
            $compiler->subcompile($this->getNode('ident'))->raw(', null');
        } elseif ($this->hasNode('oxid')) {
            $compiler->raw('null, ')->subcompile($this->getNode('oxid'));
        }

        $compiler->raw(");\n");

        // As Smarty's oxifcontent: the rendered body is CMS content and goes through Smarty (see renderContent()).
        $body = new CaptureNode($this->getNode('body'), $this->getTemplateLine());
        $body->setAttribute('raw', true);

        $compiler
            ->write('if (')->subcompile($this->getNode('variable'))->raw(") {\n")
            ->indent()
            ->write('$ifContentBody = ')->subcompile($body)->raw("\n")
            ->write("yield \$this->extensions['" . IfContentExtension::class . "']->renderContent(\$ifContentBody, ");

        if ($this->hasNode('ident')) {
            $compiler->subcompile($this->getNode('ident'));
        } else {
            $compiler->raw('null');
        }

        $compiler
            ->raw(");\n")
            ->outdent()
            ->write("}\n")
            ->write('unset(')->subcompile($this->getNode('variable'))->raw(");\n");
    }
}
