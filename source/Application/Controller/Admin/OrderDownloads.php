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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderFile;
use OxidEsales\Eshop\Application\Model\OrderFileList;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin order article manager.
 * Collects order articles information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Articles.
 */
class OrderDownloads extends AdminDetailsController
{
    /**
     * Active order object
     *
     * @var Order
     */
    protected $_oEditObject = null;

    /**
     * Executes parent method parent::render(), passes data
     * to Smarty engine, returns name of template file "order_downloads.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        if ($oOrder = $this->getEditObject()) {
            $this->_aViewData["edit"] = $oOrder;
        }

        return "order_downloads.tpl";
    }

    /**
     * Returns editable order object
     *
     * @return Order
     */
    public function getEditObject()
    {
        $soxId = $this->getEditObjectId();
        if ($this->_oEditObject === null && isset($soxId) && $soxId != "-1") {
            $this->_oEditObject = oxNew(OrderFileList::class);
            $this->_oEditObject->loadOrderFiles($soxId);
        }

        return $this->_oEditObject;
    }

    /**
     * Returns editable order object
     */
    public function resetDownloadLink()
    {
        $sOrderFileId = Registry::getRequest()->getRequestEscapedParameter('oxorderfileid');
        $oOrderFile = oxNew(OrderFile::class);
        if ($oOrderFile->load($sOrderFileId)) {
            $oOrderFile->reset();
            $oOrderFile->save();
        }
    }
}
