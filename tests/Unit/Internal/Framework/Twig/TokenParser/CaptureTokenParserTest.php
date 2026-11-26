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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\CaptureTokenParser;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;
use Twig\Token;

class CaptureTokenParserTest extends TestCase
{
    /** @var Environment */
    private $environment;

    /** @var Parser */
    private $parser;

    /** @var CaptureTokenParser */
    private $captureTokenParser;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        /** @var LoaderInterface $loader */
        $loader = $this->createMock(\Twig\Loader\LoaderInterface::class);
        $this->environment = new Environment($loader, ['cache' => false]);

        $this->captureTokenParser = new CaptureTokenParser();
        $this->environment->addTokenParser($this->captureTokenParser);

        $this->parser = new Parser($this->environment);
    }

    /**
     * @covers CaptureTokenParser::getTag
     */
    public function testGetTag()
    {
        $this->assertEquals('capture', $this->captureTokenParser->getTag());
    }

    /**
     * @covers CaptureTokenParser::decideBlockEnd
     */
    public function testDecideBlockEnd()
    {
        $token = new Token(Token::NAME_TYPE, 'foo', 1);
        $this->assertEquals(false, $this->captureTokenParser->decideBlockEnd($token));

        $token = new Token(Token::NAME_TYPE, 'endcapture', 1);
        $this->assertEquals(true, $this->captureTokenParser->decideBlockEnd($token));
    }

    /**
     * @param $source
     *
     * @covers       CaptureTokenParser::parse
     * @dataProvider templateSourceCodeProvider
     */
    public function testParse($source)
    {
        $stream = $this->environment->tokenize(new Source($source, 'index'));
        $node = $this->parser->parse($stream);

        $this->assertTrue($node->hasNode('body'));
        $bodyNode = $node->getNode('body');

        $captureNode = $bodyNode->getNode('0');
        $this->assertTrue($captureNode->hasAttribute('attributeName'));
        $this->assertTrue($captureNode->hasAttribute('variableName'));

        $ifContentNode = $bodyNode->getIterator()[0];

        $this->assertTrue($ifContentNode->hasNode('body'));
    }

    /**
     * @return array
     */
    public function templateSourceCodeProvider()
    {
        return [
            ['{% capture name = "foo" %}Lorem Ipsum{% endcapture %}'],
            ['{% capture assign = "foo" %}Lorem Ipsum{% endcapture %}'],
            ['{% capture append = "foo" %}Lorem Ipsum{% endcapture %}'],
        ];
    }

    /**
     * @covers CaptureTokenParser::parse
     */
    public function testTwigErrorSyntaxIsThrown()
    {
        $source = '{% capture %}foo{% /endcapture %}';
        $stream = $this->environment->tokenize(new Source($source, 'index'));

        $this->expectException(SyntaxError::class);
        $this->parser->parse($stream);
    }

    /**
     * @covers CaptureTokenParser::parse
     */
    public function testParseException()
    {
        $source = '{% capture foo = "foo" %}Lorem Ipsum{% endcapture %}';

        $stream = $this->environment->tokenize(new Source($source, 'index'));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage("Incorrect attribute name. Possible attribute names are: 'name', 'assign' and 'append'");
        $this->parser->parse($stream);
    }
}
