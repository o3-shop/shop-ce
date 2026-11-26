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

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\HasRightsNode;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class HasRightsTokenParser
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser
 */
class HasRightsTokenParser extends AbstractTokenParser
{
    /**
     * @var string
     */
    private $nodeClass;

    /**
     * HasRightsTokenParser constructor.
     *
     * @param string $nodeClass
     */
    public function __construct(string $nodeClass)
    {
        $this->nodeClass = $nodeClass;
    }

    /**
     * @param Token $token
     *
     * @return HasRightsNode
     *
     * @throws SyntaxError
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $variables = $this->getVariables();

        $continue = true;
        while ($continue) {
            // create subtree until the decideMyTagFork() callback returns true
            $body = $this->parser->subparse([$this, 'decideMyTagFork']);

            $tag = $stream->next()->getValue();
            switch ($tag) {
                case 'endhasrights':
                    $continue = false;
                    break;
                default:
                    throw new SyntaxError(sprintf('Unexpected end of template. Twig was looking for the following tags "endhasrights" to close the "hasrights" block started at line %d)', $lineno), -1);
            }

            $stream->expect(Token::BLOCK_END_TYPE);
        }

        return new $this->nodeClass($body, $variables, $lineno, $this->getTag());
    }

    /**
     * @return Node
     */
    private function getVariables(): Node
    {
        $stream = $this->parser->getStream();
        $variables = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        return $variables;
    }

    /**
     * Callback called at each tag name when subparsing, must return
     * true when the expected end tag is reached.
     *
     * @param Token $token
     *
     * @return bool
     */
    public function decideMyTagFork(Token $token): bool
    {
        return $token->test(['endhasrights']);
    }

    /**
     * Your tag name: if the parsed tag match the one you put here, your parse()
     * method will be called.
     *
     * @return string
     */
    public function getTag(): string
    {
        return 'hasrights';
    }
}
