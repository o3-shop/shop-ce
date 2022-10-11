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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

/**
 * Tests for VoucherSerie_Generate class
 */
class VoucherSerieGenerateTest extends \OxidTestCase
{

    /**
     * Cleanup
     *
     * @return null
     */
    public function tearDown(): void
    {
        // cleanup
        $this->cleanUpTable("oxvouchers");
        $this->cleanUpTable("oxvoucherseries");

        parent::tearDown();
    }

    /**
     * VoucherSerie_Generate::nextTick() test case
     *
     * @return null
     */
    public function testNextTick()
    {
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieGenerate::class, array("generateVoucher"));
        $oView->expects($this->at(0))->method('generateVoucher')->will($this->returnValue(0));
        $oView->expects($this->at(1))->method('generateVoucher')->will($this->returnValue(1));

        $this->assertFalse($oView->nextTick(1));
        $this->assertEquals(1, $oView->nextTick(1));
    }

    /**
     * VoucherSerie_Generate::generateVoucher() test case
     *
     * @return null
     */
    public function testGenerateVoucher()
    {
        $this->getSession()->setVariable("voucherAmount", 100);

        $oSerie = $this->getMock(\OxidEsales\Eshop\Application\Model\VoucherSerie::class, array("getId"));
        $oSerie->expects($this->exactly(2))->method('getId')->will($this->returnValue("testId"));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieGenerate::class, array("_getVoucherSerie"));
        $oView->expects($this->any())->method('_getVoucherSerie')->will($this->returnValue($oSerie));
        $this->assertEquals(1, $oView->generateVoucher(0));
        $this->assertEquals(2, $oView->generateVoucher(1));
    }

    /**
     * VoucherSerie_Generate::run() test case
     *
     * @return null
     */
    public function testRun()
    {
        $this->setRequestParameter("iStart", 0);

        // first generation call
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieGenerate::class, array("nextTick", "stop"));
        $oView->expects($this->exactly(100))->method('nextTick')->will($this->returnValue(1));
        $oView->expects($this->never())->method('stop');

        $oView->run();

        // last generation call
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieGenerate::class, array("nextTick", "stop"));
        $oView->expects($this->once())->method('nextTick')->will($this->returnValue(false));
        $oView->expects($this->once())->method('stop');

        $oView->run();
    }
}
