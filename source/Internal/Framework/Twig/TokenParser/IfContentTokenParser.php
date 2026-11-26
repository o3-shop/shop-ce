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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\IfContentNode;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class IfContentNodeParser
 */
class IfContentTokenParser extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token
     *
     * @return IfContentNode A Twig node instance
     */
    public function parse(Token $token): IfContentNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $reference = [];

        if ($stream->nextIf(Token::NAME_TYPE, 'ident')) {
            $reference['ident'] = $this->parser->getExpressionParser()->parseExpression();
        }

        if ($stream->nextIf(Token::NAME_TYPE, 'oxid')) {
            $reference['oxid'] = $this->parser->getExpressionParser()->parseExpression();
        }

        if (empty($reference)) {
            throw new SyntaxError('No Ident nor Oxid provided for ifcontent.');
        }

        if ($stream->nextIf(Token::NAME_TYPE, 'set')) {
            /** @var Node $variable */
            $variable = $this->parser->getExpressionParser()->parseAssignmentExpression();
        } else {
            $variable = new AssignNameExpression('oCont', $lineno);
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new IfContentNode($body, $reference, $variable, $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     *
     * @return bool
     */
    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('endifcontent');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag(): string
    {
        return 'ifcontent';
    }
}
