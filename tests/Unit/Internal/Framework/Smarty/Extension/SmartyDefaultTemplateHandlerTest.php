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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Smarty\Extension;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Extension\SmartyDefaultTemplateHandler;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoaderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Smarty calls the default-template handler when its `file:` resource cannot
 * locate the requested template. The handler asks the loader for a fallback
 * path and, if that path is readable, hands its contents back through Smarty's
 * `$resourceContent` / `$resourceTimestamp` reference parameters.
 */
class SmartyDefaultTemplateHandlerTest extends TestCase
{
    /**
     * Smarty exposes _read_file in its resource API; tests stand in with a
     * stub that records the called path and returns canned content.
     */
    private function makeSmartyStub(string $cannedContent = '<smarty-canned/>'): object
    {
        return new class ($cannedContent) {
            /** @var string */
            public $cannedContent;
            /** @var string|null */
            public $readPath = null;
            public function __construct(string $cannedContent)
            {
                $this->cannedContent = $cannedContent;
            }
            public function _read_file($path) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
            {
                $this->readPath = $path;
                return $this->cannedContent;
            }
        };
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Smarty\Extension\SmartyDefaultTemplateHandler::handleTemplate
     */
    public function testReturnsFalseForNonFileResourceTypes(): void
    {
        $loader = $this->createMock(TemplateLoaderInterface::class);
        $loader->expects($this->never())->method('getPath');

        $handler = new SmartyDefaultTemplateHandler($loader);

        $content = '';
        $timestamp = 0;
        $smarty = $this->makeSmartyStub();

        $this->assertFalse($handler->handleTemplate('eval', 'something.tpl', $content, $timestamp, $smarty));
        $this->assertSame('', $content);
        $this->assertSame(0, $timestamp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Smarty\Extension\SmartyDefaultTemplateHandler::handleTemplate
     */
    public function testReturnsFalseWhenOriginalPathIsAlreadyReadable(): void
    {
        // If is_readable($resourceName) is true, the handler does not enter the
        // fallback-loader path at all.
        $tmp = tempnam(sys_get_temp_dir(), 'smarty_def_');
        file_put_contents($tmp, 'already there');

        $loader = $this->createMock(TemplateLoaderInterface::class);
        $loader->expects($this->never())->method('getPath');

        $handler = new SmartyDefaultTemplateHandler($loader);

        $content = '';
        $timestamp = 0;
        $smarty = $this->makeSmartyStub();

        $this->assertFalse($handler->handleTemplate('file', $tmp, $content, $timestamp, $smarty));
        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Smarty\Extension\SmartyDefaultTemplateHandler::handleTemplate
     */
    public function testReturnsFalseWhenLoaderReturnsUnreadablePath(): void
    {
        $loader = $this->createMock(TemplateLoaderInterface::class);
        $loader->expects($this->once())
            ->method('getPath')
            ->with('missing.tpl')
            ->willReturn('/no/such/dir/' . uniqid() . '.tpl');

        $handler = new SmartyDefaultTemplateHandler($loader);

        $content = '';
        $timestamp = 0;
        $smarty = $this->makeSmartyStub();

        $this->assertFalse($handler->handleTemplate('file', 'missing.tpl', $content, $timestamp, $smarty));
        $this->assertSame('', $content);
        $this->assertSame(0, $timestamp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Smarty\Extension\SmartyDefaultTemplateHandler::handleTemplate
     */
    public function testWritesContentAndTimestampWhenLoaderResolvesAReadableFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'smarty_def_');
        file_put_contents($tmp, 'fallback template body');
        $expectedMtime = filemtime($tmp);

        $loader = $this->createMock(TemplateLoaderInterface::class);
        $loader->expects($this->once())
            ->method('getPath')
            ->with('shop/foo.tpl')
            ->willReturn($tmp);

        $handler = new SmartyDefaultTemplateHandler($loader);

        $content = '';
        $timestamp = 0;
        $smarty = $this->makeSmartyStub('<smarty-fallback-content/>');

        $result = $handler->handleTemplate('file', 'shop/foo.tpl', $content, $timestamp, $smarty);

        $this->assertTrue($result);
        $this->assertSame('<smarty-fallback-content/>', $content);
        $this->assertSame($expectedMtime, $timestamp);
        $this->assertSame($tmp, $smarty->readPath);

        unlink($tmp);
    }
}
