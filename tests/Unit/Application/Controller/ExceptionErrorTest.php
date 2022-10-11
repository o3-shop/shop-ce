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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use \oxRegistry;

/**
 * Tests for contact class
 */
class ExceptionErrorTest extends \OxidTestCase
{

    /**
     * Test view render.
     *
     * @return null
     */
    public function testRender()
    {
        $oErr = oxNew('exceptionError');
        $this->assertEquals('message/exception.tpl', $oErr->render());
    }

    /**
     * Test setting errors to view
     *
     * @return null
     */
    public function testDisplayExceptionError()
    {
        $sEx = "testText";
        $aErrors = array("default" => array("aaa" => serialize($sEx)));

        $oErr = $this->getMock(\OxidEsales\Eshop\Application\Controller\ExceptionErrorController::class, array("_getErrors"));
        $oErr->expects($this->once())->method('_getErrors')->will($this->returnValue($aErrors));

        $oErr->displayExceptionError();

        $aTplVars = $oErr->getViewDataElement("Errors");
        $oViewEx = $aTplVars["default"]["aaa"];

        $this->assertEquals($sEx, $oViewEx);
    }

    /**
     * Test setting errors to view resets errors in session
     *
     * @return null
     */
    public function testDisplayExceptionError_resetsErrorsInSession()
    {
        $this->getSession()->setVariable("Errors", "testValue");
        $this->assertEquals("testValue", $this->getSession()->getVariable("Errors"));

        $oErr = $this->getMock(\OxidEsales\Eshop\Application\Controller\ExceptionErrorController::class, array("_getErrors", 'getViewData'));
        $oErr->expects($this->once())->method('getViewData')->will($this->returnValue(array()));
        $oErr->expects($this->once())->method('_getErrors')->will($this->returnValue(array()));

        $oErr->displayExceptionError();

        $this->assertEquals(array(), $this->getSession()->getVariable("Errors"));
    }

    /**
     * Test getting errors array
     *
     * @return null
     */
    public function testGetErrors()
    {
        $this->getSession()->setVariable("Errors", "testValue");

        $oErr = $this->getProxyClass("exceptionError");
        $this->assertEquals("testValue", $oErr->UNITgetErrors());
    }
}
