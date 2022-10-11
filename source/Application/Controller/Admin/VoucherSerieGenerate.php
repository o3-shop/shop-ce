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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use oxRegistry;
use oxField;

/**
 * Voucher Serie generator class
 *
 */
class VoucherSerieGenerate extends \OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieMain
{
    /**
     * Voucher generator class name
     *
     * @var string
     */
    public $sClassDo = "voucherserie_generate";

    /**
     * Number of vouchers to generate per tick
     *
     * @var int
     */
    public $iGeneratePerTick = 100;

    /**
     * Current class template name
     *
     * @var string
     */
    protected $_sThisTemplate = "voucherserie_generate.tpl";

    /**
     * Voucher serie object
     *
     * @var oxvoucherserie
     */
    protected $_oVoucherSerie = null;

    /**
     * Generated vouchers count
     *
     * @var int
     */
    protected $_iGenerated = false;

    /**
     * Generates vouchers by offset iCnt
     *
     * @param integer $iCnt voucher offset
     *
     * @return bool
     */
    public function nextTick($iCnt)
    {
        if ($iGeneratedItems = $this->generateVoucher($iCnt)) {
            return $iGeneratedItems;
        }

        return false;
    }

    /**
     * Generates and saves vouchers. Returns number of saved records
     *
     * @param int $iCnt voucher counter offset
     *
     * @return int saved record count
     */
    public function generateVoucher($iCnt)
    {
        $iAmount = abs((int) \OxidEsales\Eshop\Core\Registry::getSession()->getVariable("voucherAmount"));

        // creating new vouchers
        if ($iCnt < $iAmount && ($oVoucherSerie = $this->_getVoucherSerie())) {
            if (!$this->_iGenerated) {
                $this->_iGenerated = $iCnt;
            }

            $blRandomNr = (bool) \OxidEsales\Eshop\Core\Registry::getSession()->getVariable("randomVoucherNr");
            $sVoucherNr = $blRandomNr ? \OxidEsales\Eshop\Core\Registry::getUtilsObject()->generateUID() : \OxidEsales\Eshop\Core\Registry::getSession()->getVariable("voucherNr");

            $oNewVoucher = oxNew(\OxidEsales\Eshop\Application\Model\Voucher::class);
            $oNewVoucher->oxvouchers__oxvoucherserieid = new \OxidEsales\Eshop\Core\Field($oVoucherSerie->getId());
            $oNewVoucher->oxvouchers__oxvouchernr = new \OxidEsales\Eshop\Core\Field($sVoucherNr);
            $oNewVoucher->save();

            $this->_iGenerated++;
        }

        return $this->_iGenerated;
    }

    /**
     * Runs voucher generation
     */
    public function run()
    {
        $blContinue = true;
        $iExportedItems = 0;

        // file is open
        $iStart = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("iStart");

        for ($i = $iStart; $i < $iStart + $this->iGeneratePerTick; $i++) {
            if (($iExportedItems = $this->nextTick($i)) === false) {
                // end reached
                $this->stop(ERR_SUCCESS);
                $blContinue = false;
                break;
            }
        }

        if ($blContinue) {
            // make ticker continue
            $this->_aViewData['refresh'] = 0;
            $this->_aViewData['iStart'] = $i;
            $this->_aViewData['iExpItems'] = $iExportedItems;
        }
    }
}
