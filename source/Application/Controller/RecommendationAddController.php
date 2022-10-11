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

namespace OxidEsales\EshopCommunity\Application\Controller;

use oxUBase;

/**
 * Handles adding article to recommendation list process.
 * Due to possibility of external modules we recommned to extend the vews from oxUBase view.
 * However expreimentally we extend RecommAdd from Details view here.
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class RecommendationAddController extends \OxidEsales\Eshop\Application\Controller\ArticleDetailsController
{
    /**
     * Template name
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/recommendationadd.tpl';

    /**
     * User recommendation lists
     *
     * @var array
     */
    protected $_aUserRecommList = null;

    /**
     * Renders the view
     *
     * @return unknown
     */
    public function render()
    {
        \OxidEsales\Eshop\Application\Controller\FrontendController::render();

        return $this->_sThisTemplate;
    }

    /**
     * Returns user recommlists
     *
     * @return array
     */
    public function getRecommLists()
    {
        if ($this->_aUserRecommList === null) {
            $oUser = $this->getUser();
            if ($oUser) {
                $this->_aUserRecommList = $oUser->getUserRecommLists();
            }
        }

        return $this->_aUserRecommList;
    }

    /**
     * Returns the title of the product added to the recommendation list.
     *
     * @return string
     */
    public function getTitle()
    {
        $oProduct = $this->getProduct();

        return $oProduct->oxarticles__oxtitle->value . ' ' . $oProduct->oxarticles__oxvarselect->value;
    }
}
