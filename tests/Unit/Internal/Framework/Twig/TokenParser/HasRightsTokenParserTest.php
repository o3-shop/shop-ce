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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\HasRightsExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\HasRightsNode;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\HasRightsTokenParser;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;
use Twig\Token;

class HasRightsTokenParserTest extends TestCase
{
    /**
     * @var HasRightsTokenParser
     */
    private $hasRightsParser;

    protected function setUp(): void
    {
        $env = $this->getEnv();
        $parser = new Parser($env);
        $this->hasRightsParser = new HasRightsTokenParser(HasRightsNode::class);
        $this->hasRightsParser->setParser($parser);
        parent::setUp();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\HasRightsParser:getTag
     */
    public function testGetTag()
    {
        $this->assertEquals('hasrights', $this->hasRightsParser->getTag());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\HasRightsParser:decideMyTagFork
     */
    public function testDecideMyTagForkIncorrect()
    {
        $token = new Token(Token::TEXT_TYPE, 1, 1);
        $this->assertEquals(false, $this->hasRightsParser->decideMyTagFork($token));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\HasRightsParser:decideMyTagFork
     */
    public function testDecideMyTagForkCorrect()
    {
        $token = new Token(5, 'endhasrights', 1);
        $this->assertEquals(true, $this->hasRightsParser->decideMyTagFork($token));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\HasRightsParser:parse
     */
    public function testParse()
    {
        /**
         * @var \Twig\Loader\LoaderInterface $loader
         */
        $loader = $this->createMock(\Twig\Loader\LoaderInterface::class);
        $env = new Environment($loader, ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new HasRightsExtension(new HasRightsTokenParser(HasRightsNode::class)));

        $stream = $env->parse($env->tokenize(new Source('{% hasrights {\'id\' : \'1\'} %}{% endhasrights %}', 'index')));
        $stream->compile(new Compiler($env));

        $this->assertInstanceOf(HasRightsTokenParser::class, $env->getTokenParser('hasrights'));
        $this->assertInstanceOf(HasRightsExtension::class, $env->getExtension(HasRightsExtension::class));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\HasRightsParser:parse
     */
    public function testParseException()
    {
        /**
         * @var LoaderInterface $loader
         */
        $loader = $this->createMock(\Twig\Loader\LoaderInterface::class);
        $env = new Environment($loader, ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new HasRightsExtension(new HasRightsTokenParser(HasRightsNode::class)));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unexpected "foo" tag (expecting closing tag for the "hasrights" tag defined near line 1) in "index" at line 1.');
        $env->parse($env->tokenize(new Source('{% hasrights {\'id\' : \'1\'} %}{% foo %}', 'index')));
    }

    /**
     * @return Environment
     */
    private function getEnv(): Environment
    {
        $loader = new ArrayLoader(['tokens' => 'foo']);
        $env = new Environment($loader, ['debug' => false, 'cache' => false]);
        if (!$env->hasExtension('hasrights')) {
            $env->addExtension(new HasRightsExtension(new HasRightsTokenParser(HasRightsNode::class)));
            $env->addTokenParser(new HasRightsTokenParser(HasRightsNode::class));
        }

        return $env;
    }
}
