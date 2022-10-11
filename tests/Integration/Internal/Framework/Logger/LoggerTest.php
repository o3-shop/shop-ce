<?php declare(strict_types=1);
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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Logger;

use OxidEsales\EshopCommunity\Internal\Framework\Logger\LoggerServiceFactory;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\Context;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Psr\Log\LogLevel;

/**
 * Class LoggerTest
 *
 * @package OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Logger
 */
class LoggerTest extends \PHPUnit\Framework\TestCase
{
    private $logFilePath;

    public function setup(): void
    {
        parent::setUp();

        $this->logFilePath = tempnam(sys_get_temp_dir(), 'test_');
    }

    public function tearDown(): void
    {
        unlink($this->logFilePath);
        parent::tearDown();
    }

    public function testLogging()
    {
        $context = $this->getContextStub(LogLevel::ERROR);

        $logger = $this->getLogger($context);
        $logger->critical('Carthago delenda est');

        $this->assertTrue(
            file_exists($context->getLogFilePath())
        );

        $this->assertStringContainsString(
            'Carthago delenda est',
            file_get_contents($context->getLogFilePath())
        );
    }

    public function testLoggerDoesNotLogMessagesLowerAsLogLevel()
    {
        $contextStub = $this->getContextStub(LogLevel::WARNING);
        $logger = $this->getLogger($contextStub);
        $infoMessage = 'Info message';
        $logger->info($infoMessage);

        $this->assertFalse(
            strpos(file_get_contents($contextStub->getLogFilePath()), $infoMessage)
        );
    }

    /**
     * @param $context Context
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function getLogger($context)
    {
        $loggerServiceFactory = new LoggerServiceFactory($context);

        return $loggerServiceFactory->getLogger();
    }

    /**
     * Log level is not configured by default.
     *
     * @param string $logLevelFromConfig
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|ContextInterface
     */
    private function getContextStub($logLevelFromConfig = null)
    {
        $context = $this
            ->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context
            ->method('getLogFilePath')
            ->willReturn($this->logFilePath);

        $context
            ->method('getLogLevel')
            ->willReturn($logLevelFromConfig);

        return $context;
    }
}
