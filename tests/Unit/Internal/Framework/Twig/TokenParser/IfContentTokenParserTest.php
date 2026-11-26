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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\TokenParser;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\IfContentTokenParser;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;
use Twig\Token;

class IfContentTokenParserTest extends TestCase
{
    /** @var Environment */
    private $environment;

    /** @var Parser */
    private $parser;

    /** @var IfContentTokenParser */
    private $ifContentParser;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        /** @var LoaderInterface $loader */
        $loader = $this->createMock(\Twig\Loader\LoaderInterface::class);
        $this->environment = new \Twig\Environment($loader, ['cache' => false]);

        $this->ifContentParser = new IfContentTokenParser();
        $this->environment->addTokenParser($this->ifContentParser);

        $this->parser = new Parser($this->environment);
    }

    /**
     * @covers IfContentTokenParser::getTag
     */
    public function testGetTag(): void
    {
        $this->assertEquals('ifcontent', $this->ifContentParser->getTag());
    }

    /**
     * @covers IfContentTokenParser::parse
     */
    public function testParse(): void
    {
        $source = '{% ifcontent ident "oxsomething" set myVar %}Lorem Ipsum{% endifcontent %}';

        $stream = $this->environment->tokenize(new Source($source, 'index'));
        $node = $this->parser->parse($stream);

        $this->assertTrue($node->hasNode('body'));

        $bodyNode = $node->getNode('body');

        $ifContentNode = $bodyNode->getIterator()[0];

        $this->assertTrue($ifContentNode->hasNode('body'));
        $this->assertTrue($ifContentNode->hasNode('variable'));
        $this->assertTrue($ifContentNode->hasNode('ident'));
    }

    /**
     * @covers IfContentTokenParser::decideBlockEnd
     */
    public function testDecideBlockEnd(): void
    {
        $token = new Token(Token::NAME_TYPE, 'foo', 1);
        $this->assertEquals(false, $this->ifContentParser->decideBlockEnd($token));

        $token = new Token(Token::NAME_TYPE, 'endifcontent', 1);
        $this->assertEquals(true, $this->ifContentParser->decideBlockEnd($token));
    }
}
