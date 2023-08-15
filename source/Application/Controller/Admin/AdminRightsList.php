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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\RightsRoles;
use OxidEsales\Eshop\Core\Model\ListModel;

/**
 * Collects System information.
 * Admin Menu: Service -> System Requirements.
 */
class AdminRightsList extends \OxidEsales\Eshop\Application\Controller\Admin\AdminListController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'adminrights_list.tpl';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = RightsRoles::class;

    /**
     * Type of list.
     *
     * @var string
     */
    protected $_sListType = ListModel::class;

    /**
     * Collects articles base data and passes them according to filtering rules,
     * returns name of template file "article_list.tpl".
     *
     * @return string
     */
    public function render()
    {
        $oList = $this->getItemList();

        if ($oList) {
            foreach ($oList as $key => $oArticle) {
                $oList[$key] = $oArticle;
            }
        }

        $tpl = parent::render();

        // load fields
        if (!$oArticle && $oList) {
            $oArticle = $oList->getBaseObject();
        }

        return $tpl;
    }

    /**
     * Deletes entry from the database
     */
    public function deleteEntry()
    {
        $sOxId = $this->getEditObjectId();
        $role = oxNew(RightsRoles::class);
        if ($sOxId && $role->load($sOxId)) {
            parent::deleteEntry();
        }
    }
}
