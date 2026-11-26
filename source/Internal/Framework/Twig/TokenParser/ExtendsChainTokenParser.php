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
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Replaces Twig's "extends" tag: inside a template chain, a constant parent name is replaced by the
 * next template of the chain, so module extensions of one theme template extend each other.
 *
 * @see \Twig\TokenParser\ExtendsTokenParser::parse
 */
class ExtendsChainTokenParser extends AbstractTokenParser
{
    /** @var TemplateChainResolver */
    private $templateChainResolver;

    public function __construct(TemplateChainResolver $templateChainResolver)
    {
        $this->templateChainResolver = $templateChainResolver;
    }

    /**
     * @throws SyntaxError
     */
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        if ($this->parser->peekBlockStack()) {
            throw new SyntaxError('Cannot use "extend" in a block.', $token->getLine(), $stream->getSourceContext());
        }
        if (!$this->parser->isMainScope()) {
            throw new SyntaxError('Cannot use "extend" in a macro.', $token->getLine(), $stream->getSourceContext());
        }
        if ($this->parser->getParent() !== null) {
            throw new SyntaxError('Multiple extends tags are forbidden.', $token->getLine(), $stream->getSourceContext());
        }

        $expression = $this->parser->getExpressionParser()->parseExpression();
        if ($expression instanceof ConstantExpression) {
            $parent = $this->templateChainResolver->getParent($stream->getSourceContext()->getName());
            if ($parent !== null) {
                $expression->setAttribute('value', $parent);
            }
        }

        $this->parser->setParent($expression);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new Node();
    }

    public function getTag(): string
    {
        return 'extends';
    }
}
