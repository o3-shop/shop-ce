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

/**
 * @deprecated Functionality for Newsletter management will be removed.
 * Newsletter plain manager.
 * Performs newsletter creation (plain text format, collects neccessary information).
 * Admin Menu: Customer Info -> Newsletter -> Text.
 */
class NewsletterPlain extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Executes prent method parent::render(), creates oxnewsletter object
     * and passes it's data to smarty. Returns name of template file
     * "newsletter_plain.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oNewsletter = oxNew(\OxidEsales\Eshop\Application\Model\Newsletter::class);
            $oNewsletter->load($soxId);
            $this->_aViewData["edit"] = $oNewsletter;
        }

        return "newsletter_plain.tpl";
    }

    /**
     * Saves newsletter text in plain text format.
     */
    public function save()
    {
        $soxId = $this->getEditObjectId();
        $aParams = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("editval");

        // shopid
        $sShopID = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable("actshop");
        $aParams['oxnewsletter__oxshopid'] = $sShopID;

        $oNewsletter = oxNew(\OxidEsales\Eshop\Application\Model\Newsletter::class);
        if ($soxId != "-1") {
            $oNewsletter->load($soxId);
        } else {
            $aParams['oxnewsletter__oxid'] = null;
        }
        //$aParams = $oNewsletter->ConvertNameArray2Idx( $aParams);
        $oNewsletter->assign($aParams);
        $oNewsletter->save();

        // set oxid if inserted
        $this->setEditObjectId($oNewsletter->getId());
    }
}
