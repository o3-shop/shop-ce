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
 * Admin article crosselling/accesories manager.
 * Creates list of available articles, there is ability to assign or remove
 * assigning of article to crosselling/accesories with other products.
 * Admin Menu: Manage Products -> Articles -> Crosssell.
 */
class ArticleCrossselling extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Collects article crosselling and attributes information, passes
     * them to Smarty engine and returns name or template file
     * "article_crossselling.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->_aViewData['edit'] = $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);

        // crossselling
        $this->_createCategoryTree("artcattree");

        // accessoires
        $this->_createCategoryTree("artcattree2");

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oArticle->load($soxId);

            if ($oArticle->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }
        }

        $iAoc = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("aoc");
        if ($iAoc == 1) {
            $oArticleCrossellingAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\ArticleCrosssellingAjax::class);
            $this->_aViewData['oxajax'] = $oArticleCrossellingAjax->getColumns();

            return "popups/article_crossselling.tpl";
        } elseif ($iAoc == 2) {
            $oArticleAccessoriesAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\ArticleAccessoriesAjax::class);
            $this->_aViewData['oxajax'] = $oArticleAccessoriesAjax->getColumns();

            return "popups/article_accessories.tpl";
        }

        return "article_crossselling.tpl";
    }
}
