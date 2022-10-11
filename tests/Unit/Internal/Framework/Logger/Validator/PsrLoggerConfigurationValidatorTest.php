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

use OxidEsales\EshopCommunity\Internal\Framework\Logger\Configuration\PsrLoggerConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Logger\Validator\PsrLoggerConfigurationValidator;
use Psr\Log\LogLevel;

class PsrLoggerConfigurationValidatorTest extends PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider dataProviderValidLogLevels
     */
    public function testValidLogLevelValidation($logLevel)
    {
        /** @var PHPUnit\Framework\MockObject\MockObject|PsrLoggerConfigurationInterface $configurationMock */
        $configurationMock = $this->getMockBuilder(PsrLoggerConfigurationInterface::class)->getMock();
        $configurationMock
            ->expects($this->any())
            ->method('getLogLevel')
            ->will($this->returnValue($logLevel));

        $validator = new PsrLoggerConfigurationValidator();
        $validator->validate($configurationMock);
    }

    public function dataProviderValidLogLevels()
    {
        return [
            [LogLevel::DEBUG],
            [LogLevel::INFO],
            [LogLevel::NOTICE],
            [LogLevel::WARNING],
            [LogLevel::ERROR],
            [LogLevel::CRITICAL],
            [LogLevel::ALERT],
            [LogLevel::EMERGENCY],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidLogLevels
     */
    public function testInvalidLogLevelValidation($logLevel)
    {
        $this->expectException(\InvalidArgumentException::class);

        /** @var PHPUnit\Framework\MockObject\MockObject|PsrLoggerConfigurationInterface $configurationMock */
        $configurationMock = $this->getMockBuilder(PsrLoggerConfigurationInterface::class)->getMock();
        $configurationMock
            ->expects($this->any())
            ->method('getLogLevel')
            ->will($this->returnValue($logLevel));

        $validator = new PsrLoggerConfigurationValidator();
        $validator->validate($configurationMock);
    }

    public function dataProviderInvalidLogLevels()
    {
        return [
            [null],
            [false],
            [true],
            ['string'],
            [0],
            [1.0000],
            [new \stdClass()],
            [['array']],
        ];
    }
}
