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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Database\Logger;

use OxidEsales\EshopCommunity\Tests\Unit\Internal\ContextStub;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Logger\QueryLogger;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Logger\QueryFilter;
use Psr\Log\LoggerInterface;
use OxidEsales\Eshop\Core\Registry;

class QueryLoggerTest extends \PHPUnit\Framework\TestCase
{
    public function providerTestLogging()
    {
        $data = [
             [
                 'query_pass' => true,
                 'expected'   => 'once'
             ],
             [
                 'query_pass' => false,
                 'expected'   => 'never'
             ]
        ];

        return $data;
    }

    /**
     * @param bool   $queryPass
     * @param string $expected
     *
     * @dataProvider providerTestLogging
     */
    public function testLogging(bool $queryPass, string $expected)
    {
        $context = new ContextStub();

        $queryFilter = $this->getQueryFilterMock($queryPass);
        $psrLogger = $this->getPsrLoggerMock();

        $psrLogger->expects($this->$expected())
            ->method('debug');

        $logger = new QueryLogger($queryFilter, $context, $psrLogger);
        $query = 'dummy test query where oxid = :id ';

        $logger->startQuery($query, [':id' => 'testid']);
        $logger->stopQuery();
    }

    /**
     * Test helper.
     *
     * @param bool $pass
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|QueryFilter
     */
    private function getQueryFilterMock($pass = true)
    {
        $queryFilter = $this->getMockBuilder(QueryFilter::class)
            ->setMethods(['shouldLogQuery'])
            ->getMock();

        $queryFilter->expects($this->any())
            ->method('shouldLogQuery')
            ->willReturn($pass);

        return $queryFilter;
    }

    /**
     * Test helper.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function getPsrLoggerMock()
    {
        $psrLogger = $this->getMockBuilder(LoggerInterface::class)
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
                    'log'
                ]
            )
            ->getMock();

        return $psrLogger;
    }
}
