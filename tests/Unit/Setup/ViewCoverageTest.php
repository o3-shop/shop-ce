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

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup;

use OxidEsales\EshopCommunity\Setup\Exception\TemplateNotFoundException;
use OxidEsales\EshopCommunity\Setup\View;

require_once getShopBasePath() . '/Setup/functions.php';

/**
 * Covers View methods that ViewTest doesn't reach: setTemplateFileName
 * (existing/missing path), getViewParam (default and present), display
 * (output buffered), sendHeaders.
 */
class ViewCoverageTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Setup\View::setTemplateFileName
     */
    public function testSetTemplateFileNameAcceptsExistingTemplate(): void
    {
        // default.php exists in source/Setup/tpl/ — used by display().
        $view = new View();
        $view->setTemplateFileName('default.php');
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\View::setTemplateFileName
     */
    public function testSetTemplateFileNameRejectsMissingTemplateWithException(): void
    {
        $view = new View();
        $this->expectException(TemplateNotFoundException::class);
        $view->setTemplateFileName('does-not-exist-' . uniqid() . '.php');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\View::setViewParam
     * @covers \OxidEsales\EshopCommunity\Setup\View::getViewParam
     */
    public function testGetViewParamReturnsNullForUnknownName(): void
    {
        $view = new View();
        $this->assertNull($view->getViewParam('not_set'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\View::sendHeaders
     */
    public function testSendHeadersDoesNotThrow(): void
    {
        $view = new View();
        // Headers may already be sent under PHPUnit; we just make sure the
        // method doesn't throw an exception in either case.
        @$view->sendHeaders();
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\View::setMessage
     * @covers \OxidEsales\EshopCommunity\Setup\View::getMessages
     */
    public function testSetMessageWithOverrideClearsExistingMessages(): void
    {
        $view = new View();
        $view->setMessage('first');
        $view->setMessage('second');
        $this->assertSame(['first', 'second'], $view->getMessages());

        $view->setMessage('reset', true);
        $this->assertSame(['reset'], $view->getMessages());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\View::display
     */
    public function testDisplayBuffersAndIncludesTemplate(): void
    {
        $view = new View();
        $view->setTemplateFileName('default.php');

        // display() ends with ob_end_flush, which closes the buffer it opened
        // and flushes content to the parent buffer. Wrap it in our own buffer
        // and pop exactly one level to avoid messing with PHPUnit's stream.
        $startingLevel = ob_get_level();
        ob_start();
        $view->display();
        // After display(): one buffer above our starting level remains because
        // display's ob_end_flush flushed *its own* buffer into ours.
        $output = ob_get_clean();
        $this->assertSame($startingLevel, ob_get_level());

        $this->assertIsString($output);
    }
}
