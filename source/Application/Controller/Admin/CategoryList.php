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

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin category list manager.
 * Collects attributes base information (sorting, title, etc.), there is ability to
 * filter them by sorting, title or delete them.
 * Admin Menu: Manage Products -> Categories.
 */
class CategoryList extends AdminListController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxcategory';

    /**
     * Type of list.
     *
     * @var string
     */
    protected $_sListType = 'oxcategorylist';

    /**
     * Returns sorting fields array
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getListSorting()
    {
        $sSortParameter = Registry::getRequest()->getRequestEscapedParameter('sort');
        if ($this->_aCurrSorting === null && !$sSortParameter && ($oBaseObject = $this->getItemListBaseObject())) {
            $sCatView = $oBaseObject->getCoreTableName();

            $this->_aCurrSorting[$sCatView]["oxrootid"] = "desc";
            $this->_aCurrSorting[$sCatView]["oxleft"] = "asc";

            return $this->_aCurrSorting;
        } else {
            return parent::getListSorting();
        }
    }

    /**
     * Loads category tree, passes data to Smarty and returns name of
     * template file "category_list.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        $oLang = Registry::getLang();
        $iLang = $oLang->getTplLanguage();

        // parent category tree
        $oCatTree = oxNew(\OxidEsales\Eshop\Application\Model\CategoryList::class);
        $oCatTree->loadList();

        // add Root as fake category
        // rebuild list as we need the root entry at the first position
        $aNewList = [];
        $oRoot = new stdClass();
        $oRoot->oxcategories__oxid = new Field(null, Field::T_RAW);
        $oRoot->oxcategories__oxtitle = new Field($oLang->translateString("viewAll", $iLang), Field::T_RAW);
        $aNewList[] = $oRoot;

        $oRoot = new stdClass();
        $oRoot->oxcategories__oxid = new Field("oxrootid", Field::T_RAW);
        $oRoot->oxcategories__oxtitle = new Field("-- " . $oLang->translateString("mainCategory", $iLang) . " --", Field::T_RAW);
        $aNewList[] = $oRoot;

        foreach ($oCatTree as $oCategory) {
            $aNewList[] = $oCategory;
        }

        $oCatTree->assign($aNewList);
        $aFilter = $this->getListFilter();
        if (is_array($aFilter) && isset($aFilter["oxcategories"]["oxparentid"])) {
            foreach ($oCatTree as $oCategory) {
                if ($oCategory->oxcategories__oxid->value == $aFilter["oxcategories"]["oxparentid"]) {
                    $oCategory->selected = 1;
                    break;
                }
            }
        }

        $this->_aViewData["cattree"] = $oCatTree;

        return "category_list.tpl";
    }
}
