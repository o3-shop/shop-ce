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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use oxCompanyVatIn;
use SoapFault;
use stdClass;

/**
 * Testable subclass that allows injecting a mock SoapClient
 * instead of connecting to the real EU VIES service.
 */
class OnlineVatIdCheckTestable extends \OxidEsales\EshopCommunity\Core\OnlineVatIdCheck
{
    /** @var object|null */
    public $mockSoapClient;

    /** @var bool */
    public $serviceAvailable = true;

    protected function _isServiceAvailable() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->serviceAvailable;
    }

    public function _checkOnline($oCheckVat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$this->_isServiceAvailable()) {
            $this->setError('SERVICE_UNREACHABLE');
            return false;
        }

        $aRetryErrors = [
            'SERVER_BUSY',
            'GLOBAL_MAX_CONCURRENT_REQ',
            'MS_MAX_CONCURRENT_REQ',
            'SERVICE_UNAVAILABLE',
            'MS_UNAVAILABLE',
            'TIMEOUT',
        ];

        $iTryMoreCnt = self::BUSY_RETRY_CNT;
        $oRes = null;

        set_error_handler([$this, 'catchWarning'], E_WARNING);

        do {
            try {
                $oSoapClient = $this->mockSoapClient;
                $this->setError('');
                $oRes = $oSoapClient->checkVat($oCheckVat);
                $iTryMoreCnt = 0;
            } catch (SoapFault $e) {
                $this->setError($e->faultstring);
                if (in_array($this->getError(), $aRetryErrors)) {
                    // Skip usleep in tests
                } else {
                    $iTryMoreCnt = 0;
                }
            }
        } while (0 < $iTryMoreCnt--);

        restore_error_handler();

        return (bool) $oRes->valid;
    }
}

class OnlineVatIdCheckTest extends \OxidTestCase
{
    /**
     * Initialize the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        ini_set('soap.wsdl_cache_enabled', '0');
    }

    /**
     * Testing vat id online checker with a valid VAT ID (mocked SOAP response)
     */
    public function testCheckOnlineWithGoodVatId()
    {
        $oCheckVat = new stdClass();
        $oCheckVat->countryCode = 'DE';
        $oCheckVat->vatNumber = '231450866';

        $oResponse = new stdClass();
        $oResponse->valid = true;

        $mockSoapClient = $this->getMockBuilder(stdClass::class)
            ->addMethods(['checkVat'])
            ->getMock();
        $mockSoapClient->expects($this->once())
            ->method('checkVat')
            ->with($this->equalTo($oCheckVat))
            ->willReturn($oResponse);

        $oOnlineVatCheck = new OnlineVatIdCheckTestable();
        $oOnlineVatCheck->mockSoapClient = $mockSoapClient;

        $this->assertTrue($oOnlineVatCheck->_checkOnline($oCheckVat));
        $this->assertEquals('', $oOnlineVatCheck->getError());
    }

    /**
     * Testing vat id online checker with a wrong/invalid country code (mocked SOAP fault)
     */
    public function testCheckOnlineWithWrongVatId()
    {
        $oCheckVat = new stdClass();
        $oCheckVat->countryCode = 'ABC';
        $oCheckVat->vatNumber = '111111';

        $mockSoapClient = $this->getMockBuilder(stdClass::class)
            ->addMethods(['checkVat'])
            ->getMock();
        $mockSoapClient->expects($this->once())
            ->method('checkVat')
            ->willThrowException(new SoapFault('soap:Server', 'INVALID_INPUT'));

        $oOnlineVatCheck = new OnlineVatIdCheckTestable();
        $oOnlineVatCheck->mockSoapClient = $mockSoapClient;

        $this->assertFalse($oOnlineVatCheck->_checkOnline($oCheckVat));
        $this->assertEquals('INVALID_INPUT', $oOnlineVatCheck->getError());
    }

    /**
     * Testing vat id online checker with an invalid VAT number (mocked SOAP response valid=false)
     */
    public function testCheckOnlineWithInvalidVatId()
    {
        $oCheckVat = new stdClass();
        $oCheckVat->countryCode = 'DE';
        $oCheckVat->vatNumber = '111111';

        $oResponse = new stdClass();
        $oResponse->valid = false;

        $mockSoapClient = $this->getMockBuilder(stdClass::class)
            ->addMethods(['checkVat'])
            ->getMock();
        $mockSoapClient->expects($this->once())
            ->method('checkVat')
            ->with($this->equalTo($oCheckVat))
            ->willReturn($oResponse);

        $oOnlineVatCheck = new OnlineVatIdCheckTestable();
        $oOnlineVatCheck->mockSoapClient = $mockSoapClient;

        $this->assertFalse($oOnlineVatCheck->_checkOnline($oCheckVat));
        $this->assertEquals('', $oOnlineVatCheck->getError());
    }

    /**
     * Testing vat id online checker - with invalid service
     */
    public function testCheckOnlineWithServiceNotReachable()
    {
        $oOnlineVatIdCheck = $this->getMock($this->getProxyClassName('oxOnlineVatIdCheck'), ['_isServiceAvailable']);
        $oOnlineVatIdCheck->expects($this->once())->method('_isServiceAvailable')->will($this->returnValue(false));

        $this->assertEquals(false, $oOnlineVatIdCheck->UNITcheckOnline(new stdClass()));
        $this->assertEquals('SERVICE_UNREACHABLE', $oOnlineVatIdCheck->getError());
    }

    /**
     * Testing oxOnlineVatIdCheck::getWsdlUrl()
     */
    public function testGetWsdlUrl_default()
    {
        $oOnline = oxNew('oxOnlineVatIdCheck');
        $this->assertEquals('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', $oOnline->getWsdlUrl());
    }

    /**
     * Testing oxOnlineVatIdCheck::getWsdlUrl()
     */
    public function testGetWsdlUrl_custom()
    {
        $oOnline = oxNew('oxOnlineVatIdCheck');
        $this->getConfig()->setConfigParam('sVatIdCheckInterfaceWsdl', 'sVatIdCheckInterfaceWsdl');
        $this->assertEquals('sVatIdCheckInterfaceWsdl', $oOnline->getWsdlUrl());
    }

    public function testValidate()
    {
        $oVatIn = new oxCompanyVatIn('LT1212');

        $oExpect = new stdClass();
        $oExpect->countryCode = 'LT';
        $oExpect->vatNumber = '1212';

        $oOnlineVatCheck = $this->getMock(\OxidEsales\Eshop\Core\OnlineVatIdCheck::class, ['_checkOnline']);
        $oOnlineVatCheck->expects($this->once())->method('_checkOnline')->with($this->equalTo($oExpect));

        $oOnlineVatCheck->validate($oVatIn);
    }

    public function testValidateOnFailSetError()
    {
        $oVatIn = new oxCompanyVatIn('LT1212');

        $oExpect = new stdClass();
        $oExpect->countryCode = 'LT';
        $oExpect->vatNumber = '1212';

        $oOnlineVatCheck = $this->getMock(\OxidEsales\Eshop\Core\OnlineVatIdCheck::class, ['_checkOnline']);
        $oOnlineVatCheck->expects($this->once())->method('_checkOnline')->with($this->equalTo($oExpect))->will($this->returnValue(false));

        $this->assertFalse($oOnlineVatCheck->validate($oVatIn));
        $this->assertSame('ID_NOT_VALID', $oOnlineVatCheck->getError());
    }
}
