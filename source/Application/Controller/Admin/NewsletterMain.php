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
use OxidEsales\Eshop\Application\Model\Newsletter;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article main newsletter manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Customer Info -> Newsletter -> Main.
 *
 * @deprecated Will be removed in next major
 */
class NewsletterMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxnewsletter object
     * and passes its data to Smarty engine. Returns name of template file
     * "newsletter_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        $oNewsletter = oxNew(Newsletter::class);

        if (isset($soxId) && $soxId != "-1") {
            $oNewsletter->load($soxId);
            $this->_aViewData["edit"] = $oNewsletter;
        }

        // generate editor
        $this->_aViewData["editor"] = $this->generateTextEditor(
            "100%",
            255,
            $oNewsletter,
            "oxnewsletter__oxtemplate"
        );

        return "newsletter_main.tpl";
    }

    /**
     * @deprecated Functionality for Newsletter management will be removed.
     * Saves newsletter HTML format text.
     */
    public function save()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        // shopid
        $sShopID = Registry::getSession()->getVariable("actshop");
        $aParams['oxnewsletter__oxshopid'] = $sShopID;

        $oNewsletter = oxNew(Newsletter::class);
        if ($soxId != "-1") {
            $oNewsletter->load($soxId);
        } else {
            $aParams['oxnewsletter__oxid'] = null;
        }

        $oNewsletter->assign($aParams);
        $oNewsletter->save();

        // set oxid if inserted
        $this->setEditObjectId($oNewsletter->getId());
    }
}
