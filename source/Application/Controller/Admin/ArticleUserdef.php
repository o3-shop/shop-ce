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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;

/**
 * Class reserved for extending (for customization - you can add you own fields, etc.).
 */
class ArticleUserdef extends AdminDetailsController
{
    /**
     * Loads article data from DB, passes it to Smarty engine, returns name
     * of template file "article_userdef.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        $oArticle = oxNew(Article::class);
        $this->_aViewData["edit"] = $oArticle;

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            if ($oArticle->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

            // load object
            $oArticle->load($soxId);
        }

        return "article_userdef.tpl";
    }
}
