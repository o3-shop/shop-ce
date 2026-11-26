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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\CaptureNode;
use Twig\Error\SyntaxError;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * Class CaptureTokenParser
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser
 */
class CaptureTokenParser extends AbstractTokenParser
{
    private $possibleAttributes = ['name', 'assign', 'append'];

    /**
     * @param Token $token
     *
     * @return CaptureNode
     * @throws SyntaxError
     */
    public function parse(Token $token): CaptureNode
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $attributeName = $this->getAttributeName($stream);
        $stream->expect(Token::OPERATOR_TYPE, '=');
        $variableName = $parser->getExpressionParser()->parseExpression()->getAttribute('value');
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CaptureNode($attributeName, $variableName, $body, $token->getLine(), $this->getTag());
    }

    /**
     * @param TokenStream $stream
     *
     * @return string
     * @throws SyntaxError
     */
    private function getAttributeName(TokenStream $stream): string
    {
        $attributeName = $stream->expect(Token::NAME_TYPE)->getValue();
        if (!in_array($attributeName, $this->possibleAttributes)) {
            throw new SyntaxError("Incorrect attribute name. Possible attribute names are: 'name', 'assign' and 'append'");
        }

        return $attributeName;
    }

    /**
     * @param Token $token
     *
     * @return bool
     */
    public function decideBlockEnd(Token $token): bool
    {
        return $token->test(['endcapture']);
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return 'capture';
    }
}
