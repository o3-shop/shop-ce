<?php

declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Logger\Wrapper;

use OxidEsales\EshopCommunity\Internal\Framework\Logger\Wrapper\LoggerWrapper;

/**
 * Class LoggerServiceWrapperTest
 *
 * @package OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Logger\Wrapper
 */
class LoggerServiceWrapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProviderPsrInterfaceMethods
     *
     * @param string $methodName The name of the method to test
     */
    public function testAllInterfaceMethodsExceptLogAreHandled($methodName)
    {
        $messageToLog = 'The message is {myMessage}';
        $contextToLog = ['myMessage' => 'Hello World!'];
        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->once())
            ->method($methodName)
            ->with(
                $this->equalTo($messageToLog),
                $this->equalTo($contextToLog)
            );

        $loggerServiceWrapper = new LoggerWrapper($loggerMock);
        $loggerServiceWrapper->$methodName($messageToLog, $contextToLog);
    }

    /**
     * @return array
     */
    public function dataProviderPsrInterfaceMethods()
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
            ['warning'],
            ['notice'],
            ['info'],
            ['debug'],
            ['log'],
        ];
    }

    public function testLog()
    {
        $messageToLog = 'The message is {myMessage}';
        $contextToLog = ['myMessage' => 'Hello World!'];
        $levelToLog = 'aLevelToLog';
        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($levelToLog),
                $this->equalTo($messageToLog),
                $this->equalTo($contextToLog)
            );

        $loggerServiceWrapper = new LoggerWrapper($loggerMock);
        $loggerServiceWrapper->log($levelToLog, $messageToLog, $contextToLog);
    }

    /**
     * A failing log write (e.g. an unwritable log dir) must not change the
     * outcome of the request that tried to log (o3-shop/o3-shop#259).
     *
     * @dataProvider dataProviderLevelMethods
     */
    public function testFailingLoggerDoesNotThrowAndFallsBackToErrorLog(string $methodName)
    {
        $loggerMock = $this->getLoggerMock();
        $loggerMock->method($methodName)
            ->willThrowException(new \UnexpectedValueException("Log dir missing.\nSecond line."));

        $errorLog = $this->captureErrorLog(function () use ($loggerMock, $methodName) {
            (new LoggerWrapper($loggerMock))->$methodName("Mail failed.\nDetails.", ['key' => 'value']);
        });

        $this->assertStringContainsString(LoggerWrapper::class . '::writeSafely - ', $errorLog);
        $this->assertStringContainsString("Writing a '$methodName' log entry failed: 'Log dir missing. Second line.'.", $errorLog);
        $this->assertStringContainsString("Original message: 'Mail failed. Details.'.", $errorLog);
        $this->assertSame(1, substr_count(trim($errorLog), "\n") + 1, 'Fallback entry must be a single line.');
    }

    public function testFailingLoggerLogMethodDoesNotThrowAndFallsBackToErrorLog()
    {
        $loggerMock = $this->getLoggerMock();
        $loggerMock->method('log')->willThrowException(new \RuntimeException('Disk full.'));

        $errorLog = $this->captureErrorLog(function () use ($loggerMock) {
            (new LoggerWrapper($loggerMock))->log('critical', 'Order failed.');
        });

        $this->assertStringContainsString("Writing a 'critical' log entry failed: 'Disk full.'.", $errorLog);
        $this->assertStringContainsString("Original message: 'Order failed.'.", $errorLog);
    }

    /**
     * @return array
     */
    public function dataProviderLevelMethods()
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
            ['warning'],
            ['notice'],
            ['info'],
            ['debug'],
        ];
    }

    /**
     * Runs $callback with PHP's error_log() redirected to a temp file; returns what was written.
     */
    private function captureErrorLog(callable $callback): string
    {
        $file = tempnam(sys_get_temp_dir(), 'errorlog');
        $previous = ini_set('error_log', $file);
        try {
            $callback();
        } finally {
            ini_set('error_log', (string) $previous);
        }
        $content = (string) file_get_contents($file);
        unlink($file);

        return $content;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Psr\Log\LoggerInterface
     */
    private function getLoggerMock()
    {
        $loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'emergency',
                    'alert',
                    'critical',
                    'error',
                    'warning',
                    'notice',
                    'info',
                    'debug',
                    'log',
                ]
            )
            ->getMock();

        return $loggerMock;
    }
}
