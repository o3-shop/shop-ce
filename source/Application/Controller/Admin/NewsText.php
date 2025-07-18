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
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\News;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin Menu: Customer Info -> News -> Text.
 * @deprecated 6.5.6 "News" feature will be removed completely
 */
class NewsText extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxnews object and
     * passes news text to smarty. Returns name of template file "news_text.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        $oNews = oxNew(News::class);

        if (isset($soxId) && $soxId != "-1") {
            $iNewsLang = Registry::getRequest()->getRequestEscapedParameter('newslang');

            if (!isset($iNewsLang)) {
                $iNewsLang = $this->_iEditLang;
            }

            $this->_aViewData["newslang"] = $iNewsLang;
            $oNews->loadInLang($iNewsLang, $soxId);

            foreach (Registry::getLang()->getLanguageNames() as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }

            // Disable editing for derived items.
            if ($oNews->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

            $this->_aViewData["edit"] = $oNews;
        }
        $this->_aViewData["editor"] = $this->generateTextEditor("100%", 255, $oNews, "oxnews__oxlongdesc", "news.tpl.css");

        return "news_text.tpl";
    }

    /**
     * Saves news text.
     *
     * @return void
     * @throws Exception
     */
    public function save()
    {
        parent::save();
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oNews = oxNew(News::class);

        $iNewsLang = Registry::getRequest()->getRequestEscapedParameter('newslang');

        if (!isset($iNewsLang)) {
            $iNewsLang = $this->_iEditLang;
        }

        if ($soxId != "-1") {
            $oNews->loadInLang($iNewsLang, $soxId);
        } else {
            $aParams['oxnews__oxid'] = null;
        }

        // Disable editing for derived items.
        if ($oNews->isDerived()) {
            return;
        }

        $oNews->setLanguage(0);
        $oNews->assign($aParams);
        $oNews->setLanguage($iNewsLang);

        $oNews->save();
        // set oxid if inserted
        $this->setEditObjectId($oNews->getId());
    }
}
