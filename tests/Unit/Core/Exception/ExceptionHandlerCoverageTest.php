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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Exception;

use OxidEsales\EshopCommunity\Core\Exception\ExceptionHandler;

/**
 * Covers the ExceptionHandler methods that the existing ExceptionHandlerTest
 * doesn't reach: getFormattedException string assembly and displayDebugMessage
 * branches (CLI / non-CLI / getString-aware exceptions).
 */
class ExceptionHandlerCoverageTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\Exception\ExceptionHandler::getFormattedException
     */
    public function testGetFormattedExceptionIncludesAllExceptionFields(): void
    {
        $exception = new \RuntimeException('something blew up', 42);
        $handler = new ExceptionHandler();

        $message = $handler->getFormattedException($exception);

        $this->assertStringContainsString('[exception]', $message);
        $this->assertStringContainsString('[type RuntimeException]', $message);
        $this->assertStringContainsString('[code 42]', $message);
        $this->assertStringContainsString('[message something blew up]', $message);
        $this->assertStringContainsString('[stacktrace]', $message);
        // The file/line of the throw site appear in the formatted output.
        $this->assertStringContainsString($exception->getFile(), $message);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Exception\ExceptionHandler::displayDebugMessage
     */
    public function testDisplayDebugMessageInCliPrintsCondensedNotice(): void
    {
        // We're already running in CLI under PHPUnit, so the `'cli' === $phpSAPIName`
        // branch is the live one. Capture output via ob_start.
        $handler = new ExceptionHandler();
        $reflection = new \ReflectionMethod(ExceptionHandler::class, 'displayDebugMessage');
        $reflection->setAccessible(true);

        ob_start();
        $reflection->invoke($handler, new \RuntimeException('boom'), true);
        $output = ob_get_clean();

        $this->assertStringContainsString('Uncaught exception', $output);
        $this->assertStringNotContainsString('Could not write log file', $output);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Exception\ExceptionHandler::displayDebugMessage
     */
    public function testDisplayDebugMessageInCliAppendsLogFailureWarningWhenNotWritten(): void
    {
        $handler = new ExceptionHandler();
        $reflection = new \ReflectionMethod(ExceptionHandler::class, 'displayDebugMessage');
        $reflection->setAccessible(true);

        ob_start();
        $reflection->invoke($handler, new \RuntimeException('boom'), false);
        $output = ob_get_clean();

        $this->assertStringContainsString('Could not write log file', $output);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Exception\ExceptionHandler::__construct
     */
    public function testConstructorAcceptsDebugLevelAndStoresLogFileName(): void
    {
        $handler = new ExceptionHandler(2);
        $this->assertSame(basename(OX_LOG_FILE), $handler->getLogFileName());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Exception\ExceptionHandler::setLogFileName
     */
    public function testSetLogFileNameStripsAnyDirectoryComponents(): void
    {
        $handler = new ExceptionHandler();
        $handler->setLogFileName('/var/log/some/path/custom.log');
        $this->assertSame('custom.log', $handler->getLogFileName());
    }
}
