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
 * Newsletter preview manager.
 * Creates plaintext and HTML format newsletter preview.
 * Admin Menu: Customer Info -> Newsletter -> Preview.
 */
class NewsletterPreview extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxnewsletter object
     * and passes it's data to Smarty engine, returns name of template file
     * "newsletter_preview.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->getEditObjectId();
        if ($soxId != "-1" && isset($soxId)) {
            // load object
            $oNewsletter = oxNew(\OxidEsales\Eshop\Application\Model\Newsletter::class);
            $oNewsletter->load($soxId);
            $this->_aViewData["edit"] = $oNewsletter;

            // user
            $sUserID = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable("auth");

            // assign values to the newsletter and show it
            $oNewsletter->prepare($sUserID, $this->getConfig()->getConfigParam('bl_perfLoadAktion'));

            $this->_aViewData["previewhtml"] = $oNewsletter->getHtmlText();
            $this->_aViewData["previewtext"] = $oNewsletter->getPlainText();
        }

        return "newsletter_preview.tpl";
    }
}
