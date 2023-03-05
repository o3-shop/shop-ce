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

namespace OxidEsales\EshopCommunity\Application\Component;

/**
 * News list manager, loads some news informetion.
 *
 * @subpackage oxcmp
 *
 * @deprecated 6.5.6 "News" feature will be removed completely
 */
class NewsComponent extends \OxidEsales\Eshop\Core\Controller\BaseController
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_blIsComponent = true;

    /**
     * Executes parent::render() and loads news list. Returns current
     * news array element (if user in admin sets to show more than 1
     * item in news box - will return whole array).
     *
     * @return array $oActNews a List of news, or null if not configured to load news
     */
    public function render()
    {
        parent::render();

        $myConfig = $this->getConfig();
        $oActView = $myConfig->getActiveView();

        // news loading is disabled
        if (
            !$myConfig->getConfigParam('bl_perfLoadNews') ||
            ($myConfig->getConfigParam('blDisableNavBars') &&
             $oActView->getIsOrderStep())
        ) {
            return;
        }

        // if news must be displayed only on start page ?
        if (
            $myConfig->getConfigParam('bl_perfLoadNewsOnlyStart') &&
            $oActView->getClassName() != "start"
        ) {
            return;
        }

        $iNewsToLoad = $myConfig->getConfigParam('sCntOfNewsLoaded');
        $iNewsToLoad = $iNewsToLoad ? $iNewsToLoad : 1;

        $oActNews = oxNew(\OxidEsales\Eshop\Application\Model\NewsList::class);
        $oActNews->loadNews(0, $iNewsToLoad);

        return $oActNews;
    }
}
