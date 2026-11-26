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
 * Modified for O3-Shop (2026): ported from twig-component v2.0.1 to PHP 7.4
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver\TemplateChain\TemplateChainResolver;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\IncludeTokenParser;

/**
 * Replaces Twig's "include" tag: a constant template name is replaced by the outermost template of its
 * chain, so module extensions of included templates apply.
 */
class IncludeChainTokenParser extends IncludeTokenParser
{
    /** @var TemplateChainResolver|null */
    private $templateChainResolver;

    public function __construct(?TemplateChainResolver $templateChainResolver = null)
    {
        $this->templateChainResolver = $templateChainResolver;
    }

    public function parse(Token $token): Node
    {
        $expression = $this->parseTemplateExpression();
        [$variables, $only, $ignoreMissing] = $this->parseArguments();

        return new IncludeNode($expression, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    protected function parseTemplateExpression(): Node
    {
        $expression = $this->parser->getExpressionParser()->parseExpression();
        if (
            $this->templateChainResolver !== null
            && $expression instanceof ConstantExpression
            && is_string($expression->getAttribute('value'))
        ) {
            $expression->setAttribute(
                'value',
                $this->templateChainResolver->getLastChild($expression->getAttribute('value'))
            );
        }

        return $expression;
    }
}
