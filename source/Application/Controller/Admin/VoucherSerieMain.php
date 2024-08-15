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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\DynamicExportBaseController;
use OxidEsales\Eshop\Application\Model\VoucherSerie;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article main voucherserie manager.
 * There is possibility to change voucherserie name, description, valid terms etc.
 * Admin Menu: Shop Settings -> Vouchers -> Main.
 */
class VoucherSerieMain extends DynamicExportBaseController
{
    /**
     * Export class name
     *
     * @var string
     */
    public $sClassDo = "voucherSerie_generate";

    /**
     * Voucher serie object
     *
     * @var VoucherSerie
     */
    protected $_oVoucherSerie = null;

    /**
     * Current class template name
     *
     * @var string
     */
    protected $_sThisTemplate = "voucherserie_main.tpl";

    /**
     * View id, use old class name for compatibility reasons.
     *
     * @var string
     */
    protected $viewId = 'voucherserie_main';

    /**
     * Executes parent method parent::render(), creates VoucherSerie object
     * and returns the name of the template file.
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oVoucherSerie = oxNew(VoucherSerie::class);
            $oVoucherSerie->load($soxId);
            $this->_aViewData["edit"] = $oVoucherSerie;

            //Disable editing for derived items
            if ($oVoucherSerie->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }
        }

        return $this->_sThisTemplate;
    }

    /**
     * Saves main Voucherserie parameters changes.
     *
     * @return void
     * @throws Exception
     */
    public function save()
    {
        parent::save();

        // Parameter Processing
        $soxId = $this->getEditObjectId();
        $aSerieParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        // Voucher Serie Processing
        $oVoucherSerie = oxNew(VoucherSerie::class);
        // if serie already exist use it
        if ($soxId != "-1") {
            $oVoucherSerie->load($soxId);
        } else {
            $aSerieParams["oxvoucherseries__oxid"] = null;
        }

        //Disable editing for derived items
        if ($oVoucherSerie->isDerived()) {
            return;
        }

        $aSerieParams["oxvoucherseries__oxdiscount"] = abs((float) $aSerieParams["oxvoucherseries__oxdiscount"]);

        $oVoucherSerie->assign($aSerieParams);
        $oVoucherSerie->save();

        // set oxid if inserted
        $this->setEditObjectId($oVoucherSerie->getId());
    }

    /**
     * Returns voucher status information array
     *
     * @return array|void
     */
    public function getStatus()
    {
        if ($oSerie = $this->_getVoucherSerie()) {
            return $oSerie->countVouchers();
        }
    }

    /**
     * Overriding parent function, doing nothing..
     */
    public function prepareExport()
    {
    }


    /**
     * Returns voucher serie object
     *
     * @return VoucherSerie
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVoucherSerie" in next major
     */
    protected function _getVoucherSerie() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_oVoucherSerie == null) {
            $oVoucherSerie = oxNew(VoucherSerie::class);
            $sId = Registry::getRequest()->getRequestEscapedParameter('voucherid');
            if ($oVoucherSerie->load($sId ? $sId : Registry::getSession()->getVariable('voucherid'))) {
                $this->_oVoucherSerie = $oVoucherSerie;
            }
        }

        return $this->_oVoucherSerie;
    }

    /**
     * Prepares Export
     *
     * @return void
     */
    public function start()
    {
        $sVoucherNr = trim(Registry::getRequest()->getRequestEscapedParameter('voucherNr'));
        $bRandomNr = Registry::getRequest()->getRequestEscapedParameter('randomVoucherNr');
        $controllerId = Registry::getConfig()->getRequestControllerId();

        if ($controllerId == 'voucherserie_generate' && !$bRandomNr && empty($sVoucherNr)) {
            return;
        }

        $this->_aViewData['refresh'] = 0;
        $this->_aViewData['iStart'] = 0;
        $iEnd = $this->prepareExport();
        Registry::getSession()->setVariable("iEnd", $iEnd);
        $this->_aViewData['iEnd'] = $iEnd;

        // saving export info
        Registry::getSession()->setVariable("voucherid", Registry::getRequest()->getRequestEscapedParameter('voucherid'));
        Registry::getSession()->setVariable("voucherAmount", abs((int) Registry::getRequest()->getRequestEscapedParameter('voucherAmount')));
        Registry::getSession()->setVariable("randomVoucherNr", $bRandomNr);
        Registry::getSession()->setVariable("voucherNr", $sVoucherNr);
    }

    /**
     * Current view ID getter helps to identify navigation position
     * fix for 0003701, passing dynexportbase::getViewId
     *
     * @return string
     */
    public function getViewId()
    {
        return \OxidEsales\Eshop\Application\Controller\Admin\AdminController::getViewId();
    }
}
