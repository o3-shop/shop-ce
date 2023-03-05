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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Exception;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\ExceptionHandler;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Webmozart\PathUtil\Path;

/**
 * Class ExceptionHandlerLoggerErrorTest
 *
 * This is quite an ugly test that checks that errors are logged
 * even if fetching the DI container fails. If first manipulates
 * the ContainerFactory using reflection so that it throws an
 * exception when some code tries to fetch the DI container.
 *
 * There is an extra test to verify that this test setup is working.
 *
 * Then the test redirects the logging output to some file that
 * allows to check that the exception handler logs an exception
 * even if the DI container can't be used to fetch the logger.
 *
 * @package OxidEsales\EshopCommunity\Tests\Unit\Core\Exception
 */
class ExceptionHandlerLoggerErrorTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    /** @var string */
    private $logFileName;

    /** @var \ReflectionProperty */
    private $instanceProperty;

    /** @var ContainerFactory */
    private $containerFactoryInstance;

    /** @var Config */
    private $configInstance;

    public function setup(): void
    {
        parent::setUp();

        $this->logFileName = Path::join(__DIR__, 'oxideshop.log');

        // Tamper the container factory so that it throws an exception
        // when somebody wants to use it
        $reflectionClass = new \ReflectionClass(ContainerFactory::class);
        $this->instanceProperty = $reflectionClass->getProperty('instance');
        $this->instanceProperty->setAccessible(true);
        // Save the container factory instance to restore it after the test
        $this->containerFactoryInstance = $this->instanceProperty->getValue();
        // This will provoke an exception when somebody wants to use the
        // container factory
        $this->instanceProperty->setValue(new \stdClass());


        // write our own log file
        $this->configInstance = Registry::getConfig();

        /** @var Config|MockObject $config */
        $config = $this->getMockBuilder(Config::class)->getMock();
        $config->method('getLogsDir')->willReturn(__DIR__);
        Registry::set(Config::class, $config);

    }

    public function tearDown(): void
    {
        Registry::set(Config::class, $this->configInstance);

        // Clean up the log file if written
        if (file_exists($this->logFileName)) {
            unlink($this->logFileName);
        }

        // Restore the container factory instance
        $this->instanceProperty->setValue($this->containerFactoryInstance);

        parent::tearDown();
    }

    public function testDIContainerFailure()
    {
        // This is a test for the test setup :-)

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method stdClass::getContainer()');

        $containerFactory = ContainerFactory::getInstance();
        $containerFactory->getContainer();
    }

    public function testErrorLoggingOnFailingDIContainer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('My test exception');

        $exceptionHandler = new ExceptionHandler();
        $exception = new \Exception('My test exception');
        $exceptionHandler->handleUncaughtException($exception);

        // Check logfile
        $log = file_get_contents($this->logFileName);
        $this->assertTrue(strpos($log, 'My test exception') !== false);
    }

}
