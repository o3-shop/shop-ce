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

namespace OxidEsales\EshopCommunity\Core;

use stdClass;
use DOMDocument;
use Exception;
use SoapClient;
use SoapFault;

/**
 * Online VAT id checker class.
 */
class OnlineVatIdCheck extends \OxidEsales\Eshop\Core\CompanyVatInChecker
{
    /**
     * Keeps service check state
     *
     * @var bool
     */
    protected $_blServiceIsOn = null;

    /**
     * VAT check results cache
     *
     * @var array
     */
    protected static $_aVatCheckCache = [];

    /**
     * How many times to retry check if server is busy
     *
     */
    const BUSY_RETRY_CNT = 1;

    /**
     * How much to wait between retries (in micro seconds)
     *
     */
    const BUSY_RETRY_WAITUSEC = 500000;

    /**
     * Wsdl url
     *
     * @var string
     */
    protected $_sWsdl = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

    /**
     * Class constructor.
     */
    public function __construct()
    {
    }

    /**
     * Validates VAT.
     *
     * @param \OxidEsales\Eshop\Application\Model\CompanyVatIn $oVatIn Company VAT identification number object.
     *
     * @return bool
     */
    public function validate(\OxidEsales\Eshop\Application\Model\CompanyVatIn $oVatIn)
    {
        $oCheckVat = new stdClass();
        $oCheckVat->countryCode = $oVatIn->getCountryCode();
        $oCheckVat->vatNumber = $oVatIn->getNumbers();

        $blResult = $this->_checkOnline($oCheckVat);
        if (!$blResult) {
            $this->setError('ID_NOT_VALID');
        }

        return $blResult;
    }

    /**
     * Catches soap warning which is usually thrown due to service problems.
     * Return true and allows to continue process
     *
     * @deprecated since v6.3.0 (2018-04-24); This method won't return a value in future.
     *
     * @param int    $iErrNo   error type number
     * @param string $sErrStr  error message
     * @param string $sErrFile error file
     * @param int    $iErrLine error line
     *
     * @return bool
     */
    public function catchWarning($iErrNo, $sErrStr, $sErrFile, $iErrLine)
    {
        \OxidEsales\Eshop\Core\Registry::getLogger()->warning($sErrStr, [
            'file' => $sErrFile,
            'line' => $iErrLine,
            'code' => $iErrNo
        ]);

        return true;
    }

    /**
     * Checks if VAT check can be performed:
     *  - if SoapClient class exists;
     *  - if service returns any output;
     *  - if output, returned by service, is valid.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isServiceAvailable" in next major
     */
    protected function _isServiceAvailable() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_blServiceIsOn === null) {
            $this->_blServiceIsOn = class_exists('SoapClient') ? true : false;
            if ($this->_blServiceIsOn) {
                $rFp = @fopen($this->getWsdlUrl(), 'r');
                $this->_blServiceIsOn = $rFp !== false;
                if ($this->_blServiceIsOn) {
                    $sWsdl = '';
                    while (!feof($rFp)) {
                        $sWsdl .= fread($rFp, 8192);
                    }
                    fclose($rFp);

                    // validating wsdl file
                    try {
                        $oDomDocument = new DOMDocument();
                        $oDomDocument->loadXML($sWsdl);
                    } catch (Exception $oExcp) {
                        // invalid xml
                        $this->_blServiceIsOn = false;
                    }
                }
            }
        }

        return $this->_blServiceIsOn;
    }

    /**
     * Checks online if USt.ID number is valid.
     * Returns true on success. On error sets error value.
     *
     * @param object $oCheckVat vat object
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkOnline" in next major
     */
    protected function _checkOnline($oCheckVat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        //Default D3 Source: https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl
        $aRetryErrors = [
            'SERVER_BUSY',
            'GLOBAL_MAX_CONCURRENT_REQ',
            'MS_MAX_CONCURRENT_REQ',
            'SERVICE_UNAVAILABLE',
            'MS_UNAVAILABLE',
            'TIMEOUT',
        ];
        
        if ($this->_isServiceAvailable()) {
            $iTryMoreCnt = self::BUSY_RETRY_CNT;

            //T2009-07-02
            //how long socket should wait for server RESPONSE
            ini_set('default_socket_timeout', 5);

            // setting local error handler to catch possible soap errors
            set_error_handler([$this, 'catchWarning'], E_WARNING);

            do {
                try {
                    //connection_timeout = how long we should wait to CONNECT to wsdl server
                    $oSoapClient = new SoapClient($this->getWsdlUrl(), ["connection_timeout" => 5]);
                    $this->setError('');
                    $oRes = $oSoapClient->checkVat($oCheckVat);
                    $iTryMoreCnt = 0;
                } catch (SoapFault $e) {
                    $this->setError($e->faultstring);
                    if (in_array($this->getError(), $aRetryErrors)) {
                        usleep(self::BUSY_RETRY_WAITUSEC);
                    } else {
                        $iTryMoreCnt = 0;
                    }
                }
            } while (0 < $iTryMoreCnt--);

            // restoring previous error handler
            restore_error_handler();

            return (bool) $oRes->valid;
        } else {
            $this->setError("SERVICE_UNREACHABLE");

            return false;
        }
    }

    /**
     * Returns wsdl url
     *
     * @return string
     */
    public function getWsdlUrl()
    {
        // overriding wsdl url
        if (($sWsdl = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam("sVatIdCheckInterfaceWsdl"))) {
            $this->_sWsdl = $sWsdl;
        }

        return $this->_sWsdl;
    }
}
