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

use Twig\Compiler;
use Twig\Node\CaptureNode as TwigCaptureNode;
use Twig\Node\Node;

/**
 * Class CaptureNode
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig\Node
 */
class CaptureNode extends Node
{
    /**
     * @var string
     */
    private $captureNameAttribute = 'name';

    /**
     * @var string
     */
    private $captureAssignAttribute = 'assign';

    /**
     * @var string
     */
    private $captureAppendAttribute = 'append';

    /**
     * @var string
     */
    private $attributeName;

    /**
     * @var string
     */
    private $variableName;

    /**
     * CaptureNode constructor.
     *
     * @param string      $attributeName
     * @param string      $variableName
     * @param Node        $body
     * @param int         $line
     * @param string|null $tag
     */
    public function __construct(string $attributeName, string $variableName, Node $body, int $line, ?string $tag = null)
    {
        parent::__construct(['body' => $body], ['attributeName' => $attributeName, 'variableName' => $variableName], $line, $tag);
    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $this->attributeName = $this->getAttribute('attributeName');
        $this->variableName = $this->getAttribute('variableName');

        $this->compileCaptureCode($compiler);
    }

    /**
     * @param Compiler $compiler
     */
    private function compileCaptureCode(Compiler $compiler): void
    {
        $this->setCaptureContentToVariable($compiler);
        $this->compileCodeForGivenAttribute($compiler);
        $this->unsetCaptureContent($compiler);
    }

    /**
     * Twig 3 templates are generators, so the body cannot be captured with ob_start().
     * The body is compiled the way the Twig core "set ... endset" capture does it.
     *
     * @param Compiler $compiler
     */
    private function setCaptureContentToVariable(Compiler $compiler): void
    {
        $body = new TwigCaptureNode($this->getNode('body'), $this->getTemplateLine());
        $body->setAttribute('raw', true);

        $compiler
            ->write('$captureContent = ')
            ->subcompile($body)
            ->raw("\n");
    }

    /**
     * @param Compiler $compiler
     */
    private function compileCodeForGivenAttribute(Compiler $compiler): void
    {
        if ($this->attributeName == $this->captureNameAttribute) {
            $this->compileCaptureName($compiler);
        } elseif ($this->attributeName == $this->captureAssignAttribute) {
            $this->compileCaptureAssign($compiler);
        } elseif ($this->attributeName == $this->captureAppendAttribute) {
            $this->compileCaptureAppend($compiler);
        }
    }

    /**
     * @param Compiler $compiler
     */
    private function compileCaptureName(Compiler $compiler): void
    {
        $compiler
            ->write("\$context['twig']['capture']['" . $this->variableName . "'] = \$captureContent;\n");
    }

    /**
     * @param Compiler $compiler
     */
    private function compileCaptureAssign(Compiler $compiler): void
    {
        $compiler
            ->write("if ('" . $this->variableName . "' != '') {\n")
            ->write("\$context['" . $this->variableName . "'] = \$captureContent;\n")
            ->write(("}\n"));
    }

    /**
     * @param Compiler $compiler
     */
    private function compileCaptureAppend(Compiler $compiler): void
    {
        $compiler
            ->write("if ('" . $this->variableName . "' != '' && isset(\$captureContent)) {\n")
            ->write("if (!isset(\$context['" . $this->variableName . "'])) {\n")
            ->write("\$context['" . $this->variableName . "'] = [];\n")
            ->write(("}\n"))
            ->write("if (!is_array(\$context['" . $this->variableName . "'])) {\n")
            ->write("\$context['" . $this->variableName . "'] = [\$context['" . $this->variableName . "']];\n")
            ->write(("}\n"))
            ->write("\$context['" . $this->variableName . "'][] = \$captureContent;\n")
            ->write(("}\n"));
    }

    /**
     * @param Compiler $compiler
     */
    private function unsetCaptureContent(Compiler $compiler): void
    {
        $compiler
            ->write("unset(\$captureContent);\n");
    }
}
