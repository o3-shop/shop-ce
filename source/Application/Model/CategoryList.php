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

namespace OxidEsales\EshopCommunity\Application\Model;

use oxDb;
use Exception;

/**
 * Category list manager.
 * Collects available categories, performs some SQL queries to create category
 * list structure.
 *
 */
class CategoryList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = 'oxcategory';

    /**
     * Performance option mapped to config option blDontShowEmptyCategories
     *
     * @var boolean
     */
    protected $_blHideEmpty = false;

    /**
     * Performance option used to force full tree loading
     *
     * @var boolean
     */
    protected $_blForceFull = false;

    /**
     * Levels count should be loaded available options 1 - only root and 2 - root and second level
     *
     * @var boolean
     */
    protected $_iForceLevel = 2;

    /**
     * Active category id, used in path building, and performance optimization
     *
     * @var string
     */
    protected $_sActCat = null;

    /**
     * Active category path array
     *
     * @var array
     */
    protected $_aPath = [];

    /**
     * Category update info array
     *
     * @var array
     */
    protected $_aUpdateInfo = [];

    /**
     * Class constructor, initiates parent constructor (parent::oxList()).
     *
     * @param string $sObjectsInListName optional parameter, the objects contained in the list, always oxCategory
     */
    public function __construct($sObjectsInListName = 'oxcategory')
    {
        $this->_blHideEmpty = $this->getConfig()->getConfigParam('blDontShowEmptyCategories');
        parent::__construct($sObjectsInListName);
    }

    /**
     * Set how to load tree true - for full tree
     *
     * @param boolean $blForceFull - true to load full
     */
    public function setLoadFull($blForceFull)
    {
        $this->_blForceFull = $blForceFull;
    }

    /**
     * Return true if load full tree
     *
     * @return boolean
     */
    public function getLoadFull()
    {
        return $this->_blForceFull;
    }

    /**
     * Set tree level 1- load root or 2 - root and second level
     *
     * @param int $iForceLevel - level number
     */
    public function setLoadLevel($iForceLevel)
    {
        if ($iForceLevel > 2) {
            $iForceLevel = 2;
        } elseif ($iForceLevel < 1) {
            $iForceLevel = 0;
        }
        $this->_iForceLevel = $iForceLevel;
    }

    /**
     * Returns tree load level
     *
     * @return integer
     */
    public function getLoadLevel()
    {
        return $this->_iForceLevel;
    }

    /**
     * return fields to select while loading category tree
     *
     * @param string $sTable   table name
     * @param array  $aColumns required column names (optional)
     *
     * @return string return
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSqlSelectFieldsForTree" in next major
     */
    protected function _getSqlSelectFieldsForTree($sTable, $aColumns = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($aColumns && count($aColumns)) {
            foreach ($aColumns as $key => $val) {
                $aColumns[$key] .= ' as ' . $val;
            }

            return "$sTable." . implode(", $sTable.", $aColumns);
        }

        $sFieldList = "$sTable.oxid as oxid, $sTable.oxactive as oxactive,"
                      . " $sTable.oxhidden as oxhidden, $sTable.oxparentid as oxparentid,"
                      . " $sTable.oxdefsort as oxdefsort, $sTable.oxdefsortmode as oxdefsortmode,"
                      . " $sTable.oxleft as oxleft, $sTable.oxright as oxright,"
                      . " $sTable.oxrootid as oxrootid, $sTable.oxsort as oxsort,"
                      . " $sTable.oxtitle as oxtitle, $sTable.oxdesc as oxdesc,"
                      . " $sTable.oxpricefrom as oxpricefrom, $sTable.oxpriceto as oxpriceto,"
                      . " $sTable.oxicon as oxicon, $sTable.oxextlink as oxextlink,"
                      . " $sTable.oxthumb as oxthumb, $sTable.oxpromoicon as oxpromoicon";

        $sFieldList .= $this->getActivityFieldsSql($sTable);

        return $sFieldList;
    }

    /**
     * Get activity related fields
     *
     * @param string $tableName
     *
     * @return string SQL snippet
     */
    protected function getActivityFieldsSql($tableName)
    {
        return ",not $tableName.oxactive as oxppremove";
    }

    /**
     * constructs the sql string to get the category list
     *
     * @param bool   $blReverse list loading order, true for tree, false for simple list (optional, default false)
     * @param array  $aColumns  required column names (optional)
     * @param string $sOrder    order by string (optional)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSelectString" in next major
     */
    protected function _getSelectString($blReverse = false, $aColumns = null, $sOrder = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sViewName = $this->getBaseObject()->getViewName();
        $sFieldList = $this->_getSqlSelectFieldsForTree($sViewName, $aColumns);

        //excluding long desc
        if (!$this->isAdmin() && !$this->_blHideEmpty && !$this->getLoadFull()) {
            $oCat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
            if (!($this->_sActCat && $oCat->load($this->_sActCat) && $oCat->oxcategories__oxrootid->value)) {
                $oCat = null;
                $this->_sActCat = null;
            }

            $sUnion = $this->_getDepthSqlUnion($oCat, $aColumns);
            $sWhere = $this->_getDepthSqlSnippet($oCat);
        } else {
            $sUnion = '';
            $sWhere = '1';
        }

        if (!$sOrder) {
            $sOrdDir = $blReverse ? 'desc' : 'asc';
            $sOrder = "oxrootid $sOrdDir, oxleft $sOrdDir";
        }

        return "select $sFieldList from $sViewName where $sWhere $sUnion order by $sOrder";
    }

    /**
     * constructs the sql snippet responsible for depth optimizations,
     * loads only selected category's siblings
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat selected category
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDepthSqlSnippet" in next major
     */
    protected function _getDepthSqlSnippet($oCat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sViewName = $this->getBaseObject()->getViewName();
        $sDepthSnippet = ' ( 0';

        // load complete tree of active category, if it exists
        if ($oCat) {
            // select children here, siblings will be selected from union
            $sDepthSnippet .= " or ($sViewName.oxparentid = " . \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($oCat->oxcategories__oxid->value) . ")";
        }

        // load 1'st category level (roots)
        if ($this->getLoadLevel() >= 1) {
            $sDepthSnippet .= " or $sViewName.oxparentid = 'oxrootid'";
        }

        // load 2'nd category level ()
        if ($this->getLoadLevel() >= 2) {
            $sDepthSnippet .= " or $sViewName.oxrootid = $sViewName.oxparentid or $sViewName.oxid = $sViewName.oxrootid";
        }

        $sDepthSnippet .= ' ) ';

        return $sDepthSnippet;
    }

    /**
     * returns sql snippet for union of select category's and its upper level
     * siblings of the same root (siblings of the category, and parents and
     * grandparents etc)
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat     current category object
     * @param array                                        $aColumns required column names (optional)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDepthSqlUnion" in next major
     */
    protected function _getDepthSqlUnion($oCat, $aColumns = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$oCat) {
            return '';
        }

        $sViewName = $this->getBaseObject()->getViewName();

        return "UNION SELECT " . $this->_getSqlSelectFieldsForTree('maincats', $aColumns)
               . " FROM oxcategories AS subcats"
               . " LEFT JOIN $sViewName AS maincats on maincats.oxparentid = subcats.oxparentid"
               . " WHERE subcats.oxrootid = " . \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($oCat->oxcategories__oxrootid->value)
               . " AND subcats.oxleft <= " . (int) $oCat->oxcategories__oxleft->value
               . " AND subcats.oxright >= " . (int) $oCat->oxcategories__oxright->value;
    }

    /**
     * Get data from db
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadFromDb" in next major
     */
    protected function _loadFromDb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSql = $this->_getSelectString(false, null, 'oxparentid, oxsort, oxtitle');
        $aData = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC)->getAll($sSql);

        return $aData;
    }

    /**
     * Load category list data
     */
    public function load()
    {
        $aData = $this->_loadFromDb();
        $this->assignArray($aData);
    }

    /**
     * Fetches reversed raw categories and does all necessary postprocessing for
     * removing invisible or forbidden categories, building oc navigation path,
     * adding content categories and building tree structure.
     *
     * @param string $sActCat Active category (default null)
     */
    public function buildTree($sActCat)
    {
        startProfile("buildTree");

        $this->_sActCat = $sActCat;
        $this->load();

        // PostProcessing
        if (!$this->isAdmin()) {
            // remove inactive categories
            $this->_ppRemoveInactiveCategories();

            // add active cat as full object
            $this->_ppLoadFullCategory($sActCat);

            // builds navigation path
            $this->_ppAddPathInfo();

            // add content categories
            $this->_ppAddContentCategories();

            // build tree structure
            $this->_ppBuildTree();
        }

        stopProfile("buildTree");
    }

    /**
     * set full category object in tree
     *
     * @param string $sId category id
     * @deprecated underscore prefix violates PSR12, will be renamed to "ppLoadFullCategory" in next major
     */
    protected function _ppLoadFullCategory($sId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (isset($this->_aArray[$sId])) {
            $oNewCat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
            if ($oNewCat->load($sId)) {
                // replace aArray object with fully loaded category
                $this->_aArray[$sId] = $oNewCat;
            }
        } else {
            $this->_sActCat = null;
        }
    }

    /**
     * Fetches raw categories and does postprocessing for adding depth information
     */
    public function loadList()
    {
        startProfile('buildCategoryList');

        $this->setLoadFull(true);
        $this->selectString($this->_getSelectString(false, null, 'oxparentid, oxsort, oxtitle'));

        // build tree structure
        $this->_ppBuildTree();

        // PostProcessing
        // add tree depth info
        $this->_ppAddDepthInformation();
        stopProfile('buildCategoryList');
    }

    /**
     * setter for shopID
     *
     * @param int $sShopID ShopID
     */
    public function setShopID($sShopID)
    {
        $this->_sShopID = $sShopID;
    }

    /**
     * Getter for active category path
     *
     * @return array
     */
    public function getPath()
    {
        return $this->_aPath;
    }

    /**
     * Getter for active category
     *
     * @return \OxidEsales\Eshop\Application\Model\Category
     */
    public function getClickCat()
    {
        if (count($this->_aPath)) {
            return end($this->_aPath);
        }
    }

    /**
     * Getter for active root category
     *
     * @return array of oxCategory
     */
    public function getClickRoot()
    {
        if (count($this->_aPath)) {
            return [reset($this->_aPath)];
        }
    }

    /**
     * Category list postprocessing routine, responsible for removal of inactive of forbidden categories, and subcategories.
     * @deprecated underscore prefix violates PSR12, will be renamed to "ppRemoveInactiveCategories" in next major
     */
    protected function _ppRemoveInactiveCategories() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // Collect all items which must be remove
        $aRemoveList = [];
        foreach ($this->_aArray as $sId => $oCat) {
            if ($oCat->oxcategories__oxppremove->value) {
                if (!isset($aRemoveList[$oCat->oxcategories__oxrootid->value])) {
                    $aRemoveList[$oCat->oxcategories__oxrootid->value] = [];
                }
                $aRemoveList[$oCat->oxcategories__oxrootid->value][$oCat->oxcategories__oxleft->value] = $oCat->oxcategories__oxright->value;
                unset($this->_aArray[$sId]);
            } else {
                unset($oCat->oxcategories__oxppremove);
            }
        }

        // Remove collected item's children from the list too (in the ranges).
        foreach ($this->_aArray as $sId => $oCat) {
            if (
                isset($aRemoveList[$oCat->oxcategories__oxrootid->value]) &&
                is_array($aRemoveList[$oCat->oxcategories__oxrootid->value])
            ) {
                foreach ($aRemoveList[$oCat->oxcategories__oxrootid->value] as $iLeft => $iRight) {
                    if (
                        ($iLeft <= $oCat->oxcategories__oxleft->value)
                        && ($iRight >= $oCat->oxcategories__oxleft->value)
                    ) {
                        // this is a child in an inactive range (parent already gone)
                        unset($this->_aArray[$sId]);
                        break 1;
                    }
                }
            }
        }
    }

    /**
     * Category list postprocessing routine, responsible for generation of active category path
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "ppAddPathInfo" in next major
     */
    protected function _ppAddPathInfo() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_null($this->_sActCat)) {
            return;
        }

        $aPath = [];
        $sCurrentCat = $this->_sActCat;

        while ($sCurrentCat != 'oxrootid' && isset($this[$sCurrentCat])) {
            $oCat = $this[$sCurrentCat];
            $oCat->setExpanded(true);
            $aPath[$sCurrentCat] = $oCat;
            $sCurrentCat = $oCat->oxcategories__oxparentid->value;
        }

        $this->_aPath = array_reverse($aPath);
    }

    /**
     * Category list postprocessing routine, responsible adding of content categories
     * @deprecated underscore prefix violates PSR12, will be renamed to "ppAddContentCategories" in next major
     */
    protected function _ppAddContentCategories() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // load content pages for adding them into menu tree
        $oContentList = oxNew(\OxidEsales\Eshop\Application\Model\ContentList::class);
        $oContentList->loadCatMenues();

        foreach ($oContentList as $sCatId => $aContent) {
            if (array_key_exists($sCatId, $this->_aArray)) {
                $this[$sCatId]->setContentCats($aContent);
            }
        }
    }

    /**
     * Category list postprocessing routine, responsible building an sorting of hierarchical category tree
     * @deprecated underscore prefix violates PSR12, will be renamed to "ppBuildTree" in next major
     */
    protected function _ppBuildTree() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aTree = [];
        foreach ($this->_aArray as $oCat) {
            $sParentId = $oCat->oxcategories__oxparentid->value;
            if ($sParentId != 'oxrootid') {
                if (isset($this->_aArray[$sParentId])) {
                    $this->_aArray[$sParentId]->setSubCat($oCat, $oCat->getId());
                }
            } else {
                $aTree[$oCat->getId()] = $oCat;
            }
        }

        $this->assign($aTree);
    }

    /**
     * Category list postprocessing routine, responsible for making flat category tree and adding depth information.
     * Requires reversed category list!
     * @deprecated underscore prefix violates PSR12, will be renamed to "ppAddDepthInformation" in next major
     */
    protected function _ppAddDepthInformation() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aTree = [];
        foreach ($this->_aArray as $oCat) {
            $aTree[$oCat->getId()] = $oCat;
            $aSubCats = $oCat->getSubCats();
            if (count($aSubCats) > 0) {
                foreach ($aSubCats as $oSubCat) {
                    $aTree = $this->_addDepthInfo($aTree, $oSubCat);
                }
            }
        }
        $this->assign($aTree);
    }

    /**
     * Recursive function to add depth information
     *
     * @param array  $aTree  new category tree
     * @param object $oCat   category object
     * @param string $sDepth string to show category depth
     *
     * @return array $aTree
     * @deprecated underscore prefix violates PSR12, will be renamed to "addDepthInfo" in next major
     */
    protected function _addDepthInfo($aTree, $oCat, $sDepth = "") // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sDepth .= "-";
        $oCat->oxcategories__oxtitle->setValue($sDepth . ' ' . $oCat->oxcategories__oxtitle->value);
        $aTree[$oCat->getId()] = $oCat;
        $aSubCats = $oCat->getSubCats();
        if (count($aSubCats) > 0) {
            foreach ($aSubCats as $oSubCat) {
                $aTree = $this->_addDepthInfo($aTree, $oSubCat, $sDepth);
            }
        }

        return $aTree;
    }

    /**
     * Rebuilds nested sets information by updating oxLeft and oxRight category attributes, from oxParentId
     *
     * @param bool   $blVerbose Set to true for output the update status for user,
     * @param string $sShopID   the shop id
     */
    public function updateCategoryTree($blVerbose = true, $sShopID = null)
    {
        // Only called from admin and admin mode reads from master (see ESDEV-3804 and ESDEV-3822).
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $database->startTransaction();

        try {
            $sWhere = $this->getInitialUpdateCategoryTreeCondition($blVerbose);

            $database->execute("update oxcategories set oxleft = 0, oxright = 0 where $sWhere");
            $database->execute("update oxcategories set oxleft = 1, oxright = 2 where oxparentid = 'oxrootid' and $sWhere");

            // Get all root categories
            $rs = $database->select("select oxid, oxtitle from oxcategories where oxparentid = 'oxrootid' and $sWhere order by oxsort", false);
            if ($rs != false && $rs->count() > 0) {
                while (!$rs->EOF) {
                    $this->_aUpdateInfo[] = "<b>Processing : " . $rs->fields[1] . "</b>(" . $rs->fields[0] . ")<br>";
                    if ($blVerbose) {
                        echo next($this->_aUpdateInfo);
                    }
                    $oxRootId = $rs->fields[0];

                    $this->_updateNodes($oxRootId, true, $oxRootId);
                    $rs->fetchRow();
                }
            }
            $database->commitTransaction();
        } catch (Exception $exception) {
            $database->rollbackTransaction();
            throw $exception;
        }

        $this->onUpdateCategoryTree();
    }

    /**
     * Triggering in the end of updateCategoryTree method
     */
    protected function onUpdateCategoryTree()
    {
    }

    /**
     * Get Initial updateCategoryTree sql condition
     *
     * @param bool $blVerbose
     *
     * @return string
     */
    protected function getInitialUpdateCategoryTreeCondition($blVerbose = false)
    {
        return '1';
    }

    /**
     * Returns update log data array
     *
     * @return array
     */
    public function getUpdateInfo()
    {
        return $this->_aUpdateInfo;
    }

    /**
     * Recursively updates root nodes, this method is used (only) in updateCategoryTree()
     *
     * @param string $oxRootId rootid of tree
     * @param bool   $isRoot   is the current node root?
     * @param string $thisRoot the id of the root
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateNodes" in next major
     */
    protected function _updateNodes($oxRootId, $isRoot, $thisRoot) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // Called from inside a transaction so master is picked automatically (see ESDEV-3804 and ESDEV-3822).
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        if ($isRoot) {
            $thisRoot = $oxRootId;
        }

        // Get sub categories of root categories
        $database->execute("update oxcategories set oxrootid = :oxrootid where oxparentid = :oxparentid", [
            ':oxrootid' => $thisRoot,
            ':oxparentid' => $oxRootId
        ]);
        $rs = $database->select("select oxid, oxparentid from oxcategories where oxparentid = :oxparentid order by oxsort", [
            ':oxparentid' => $oxRootId
        ]);
        // If there are sub categories
        if ($rs != false && $rs->count() > 0) {
            while (!$rs->EOF) {
                $parentId = $rs->fields[1];
                $actOxid = $rs->fields[0];

                // Get the data of the parent category to the current Cat
                $rs3 = $database->select("select oxrootid, oxright from oxcategories where oxid = :oxid", [
                    ':oxid' => $parentId
                ]);
                while (!$rs3->EOF) {
                    $parentOxRootId = $rs3->fields[0];
                    $parentRight = (int) $rs3->fields[1];
                    $rs3->fetchRow();
                }

                $query = "update oxcategories set oxleft = oxleft + 2
                          where oxrootid = :oxrootid and
                                oxleft > :parentRight and
                                oxright >= :parentRight and
                                oxid != :oxid";
                $database->execute($query, [
                    ':oxrootid' => $parentOxRootId,
                    ':parentRight' => $parentRight,
                    ':oxid' => $actOxid
                ]);

                $query = "update oxcategories set oxright = oxright + 2
                          where oxrootid = :oxrootid and
                                oxright >= :oxright and
                                oxid != :oxid";
                $database->execute($query, [
                    ':oxrootid' => $parentOxRootId,
                    ':oxright' => $parentRight,
                    ':oxid' => $actOxid
                ]);

                $query = "update oxcategories set oxleft = :parentRight, oxright = (:parentRight + 1) where oxid = :oxid";
                $database->execute($query, [
                    ':parentRight' => $parentRight,
                    ':oxid' => $actOxid
                ]);
                $this->_updateNodes($actOxid, false, $thisRoot);
                $rs->fetchRow();
            }
        }
    }

    /**
     * Extra getter to guarantee compatibility with templates
     *
     * @param string $sName variable name
     *
     * @return string
     */
    public function __get($sName)
    {
        switch ($sName) {
            case 'aPath':
            case 'aFullPath':
                return $this->getPath();
                break;
        }
        return parent::__get($sName);
    }
}
