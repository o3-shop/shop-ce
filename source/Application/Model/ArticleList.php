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

use Exception;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Article list manager.
 * Collects list of article according to collection rules (categories, etc.).
 *
 */
class ArticleList extends ListModel
{
    /**
     * @var string SQL addon for sorting
     */
    protected $_sCustomSorting;

    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = 'oxarticle';

    /**
     * Set to true if Select Lists should be loaded
     *
     * @var bool
     */
    protected $_blLoadSelectLists = false;

    /**
     * Set Custom Sorting, simply an order by....
     *
     * @param string $sSorting Custom sorting
     */
    public function setCustomSorting($sSorting)
    {
        $this->_sCustomSorting = $sSorting;
    }

    /**
     * Call enableSelectLists() for loading select lists in lst articles
     */
    public function enableSelectLists()
    {
        $this->_blLoadSelectLists = true;
    }

    /**
     * @inheritdoc
     * In addition to the parent method, this method includes profiling.
     *
     * @param string $sql        SQL select statement or prepared statement
     * @param array  $parameters Parameters to be used in a prepared statement
     */
    public function selectString($sql, array $parameters = [])
    {
        startProfile("loadinglists");
        parent::selectString($sql, $parameters);
        stopProfile("loadinglists");
    }

    /**
     * Get history article id's from session or cookie.
     *
     * @return array|void
     */
    public function getHistoryArticles()
    {
        if ($aArticlesIds = Registry::getSession()->getVariable('aHistoryArticles')) {
            return $aArticlesIds;
        } elseif ($sArticlesIds = Registry::getUtilsServer()->getOxCookie('aHistoryArticles')) {
            return explode('|', $sArticlesIds);
        }
    }

    /**
     * Set history article id's to session or cookie
     *
     * @param array $aArticlesIds array history article ids
     */
    public function setHistoryArticles($aArticlesIds)
    {
        if (Registry::getSession()->getId()) {
            Registry::getSession()->setVariable('aHistoryArticles', $aArticlesIds);
            // clean cookie, if session started
            Registry::getUtilsServer()->setOxCookie('aHistoryArticles', '');
        } else {
            Registry::getUtilsServer()->setOxCookie('aHistoryArticles', implode('|', $aArticlesIds));
        }
    }

    /**
     * Loads up to 4 history (normally recently seen) articles from session, and adds $sArtId to history.
     * Returns article id array.
     *
     * @param string $sArtId Article ID
     * @param int $iCnt product count
     * @throws DatabaseConnectionException
     */
    public function loadHistoryArticles($sArtId, $iCnt = 4)
    {
        $aHistoryArticles = $this->getHistoryArticles();
        $aHistoryArticles[] = $sArtId;

        // removing duplicates
        $aHistoryArticles = array_unique($aHistoryArticles);
        if (count($aHistoryArticles) > ($iCnt + 1)) {
            array_shift($aHistoryArticles);
        }

        $this->setHistoryArticles($aHistoryArticles);

        // remove current article and return array
        // assignment =, not ==
        if (($iCurrentArt = array_search($sArtId, $aHistoryArticles)) !== false) {
            unset($aHistoryArticles[$iCurrentArt]);
        }

        $aHistoryArticles = array_values($aHistoryArticles);
        $this->loadIds($aHistoryArticles);
        $this->sortByIds($aHistoryArticles);
    }

    /**
     * sort this list by given order.
     *
     * @param array $aIds ordered ids
     */
    public function sortByIds($aIds)
    {
        $this->_aOrderMap = array_flip($aIds);
        uksort($this->_aArray, [$this, '_sortByOrderMapCallback']);
    }

    /**
     * callback function only used from sortByIds
     *
     * @param string $key1 1st key
     * @param string $key2 2nd key
     *
     * @see oxArticleList::sortByIds
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "sortByOrderMapCallback" in next major
     */
    protected function _sortByOrderMapCallback($key1, $key2) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (isset($this->_aOrderMap[$key1])) {
            if (isset($this->_aOrderMap[$key2])) {
                $iDiff = $this->_aOrderMap[$key2] - $this->_aOrderMap[$key1];
                if ($iDiff > 0) {
                    return -1;
                } elseif ($iDiff < 0) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                // first is here, but 2nd is not - 1st gets more priority
                return -1;
            }
        } elseif (isset($this->_aOrderMap[$key2])) {
            // first is not here, but 2nd is - 2nd gets more priority
            return 1;
        } else {
            // both unset, equal
            return 0;
        }
    }

    /**
     * Loads newest shops articles from DB.
     *
     * @param int $iLimit Select limit
     */
    public function loadNewestArticles($iLimit = null)
    {
        //has module?
        $myConfig = Registry::getConfig();

        if (!$myConfig->getConfigParam('bl_perfLoadPriceForAddList')) {
            $this->getBaseObject()->disablePriceLoad();
        }

        $this->_aArray = [];
        switch ($myConfig->getConfigParam('iNewestArticlesMode')) {
            case 0:
                // switched off, do nothing
                break;
            case 1:
                // manually entered
                $this->loadActionArticles('oxnewest', $iLimit);
                break;
            case 2:
                $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
                if ($myConfig->getConfigParam('blNewArtByInsert')) {
                    $sType = 'oxinsert';
                } else {
                    $sType = 'oxtimestamp';
                }
                $sSelect = "select * from $sArticleTable ";
                $sSelect .= "where oxparentid = '' and " . $this->getBaseObject()->getSqlActiveSnippet() . " and oxissearch = 1 order by $sType desc ";
                if (!($iLimit = (int) $iLimit)) {
                    $iLimit = $myConfig->getConfigParam('iNrofNewcomerArticles');
                }
                $sSelect .= "limit " . $iLimit;

                $this->selectString($sSelect);
                break;
        }
    }

    /**
     * Load top 5 articles
     *
     * @param int $iLimit Select limit
     */
    public function loadTop5Articles($iLimit = null)
    {
        //has module?
        $myConfig = Registry::getConfig();

        if (!$myConfig->getConfigParam('bl_perfLoadPriceForAddList')) {
            $this->getBaseObject()->disablePriceLoad();
        }

        switch ($myConfig->getConfigParam('iTop5Mode')) {
            case 0:
                // switched off, do nothing
                break;
            case 1:
                // manually entered
                $this->loadActionArticles('oxtop5', $iLimit);
                break;
            case 2:
                $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');

                //by default limit 5
                $sLimit = ($iLimit > 0) ? "limit " . $iLimit : 'limit 5';

                $sSelect = "select * from $sArticleTable ";
                $sSelect .= "where " . $this->getBaseObject()->getSqlActiveSnippet() . " and $sArticleTable.oxissearch = 1 ";
                $sSelect .= "and $sArticleTable.oxparentid = '' and $sArticleTable.oxsoldamount > 0 ";
                $sSelect .= "order by $sArticleTable.oxsoldamount desc $sLimit";

                $this->selectString($sSelect);
                break;
        }
    }

    /**
     * Loads shop AktionArticles.
     *
     * @param string $sActionID Action id
     * @param int    $iLimit    Select limit
     *
     * @return void
     */
    public function loadActionArticles($sActionID, $iLimit = null)
    {
        // Performance
        if (!trim($sActionID)) {
            return;
        }

        $sShopID = Registry::getConfig()->getShopId();
        $sActionID = strtolower($sActionID);

        //echo $sSelect;
        $oBaseObject = $this->getBaseObject();
        $sArticleTable = $oBaseObject->getViewName();
        $sArticleFields = $oBaseObject->getSelectFields();

        $oBase = oxNew(Actions::class);
        $sActiveSql = $oBase->getSqlActiveSnippet();
        $sViewName = $oBase->getViewName();

        $sLimit = ($iLimit > 0) ? "limit " . $iLimit : '';

        $sSelect = "select $sArticleFields from oxactions2article
                              left join $sArticleTable on $sArticleTable.oxid = oxactions2article.oxartid
                              left join $sViewName on $sViewName.oxid = oxactions2article.oxactionid
                              where oxactions2article.oxshopid = :oxshopid  
                                  and oxactions2article.oxactionid = :oxactionid 
                                  and $sActiveSql
                                  and $sArticleTable.oxid is not null and " . $oBaseObject->getSqlActiveSnippet() . "
                              order by oxactions2article.oxsort $sLimit";

        $this->selectString($sSelect, [
            ':oxshopid' => $sShopID,
            ':oxactionid' => $sActionID
        ]);
    }

    /**
     * Loads article cross-selling
     *
     * @param string $sArticleId Article id
     *
     * @return void
     */
    public function loadArticleCrossSell($sArticleId)
    {
        $myConfig = Registry::getConfig();

        // Performance
        if (!$myConfig->getConfigParam('bl_perfLoadCrossselling')) {
            return null;
        }

        $oBaseObject = $this->getBaseObject();
        $sArticleTable = $oBaseObject->getViewName();

        $sSelect = "SELECT $sArticleTable.*
            FROM $sArticleTable INNER JOIN oxobject2article ON oxobject2article.oxobjectid=$sArticleTable.oxid 
            WHERE oxobject2article.oxarticlenid = :oxarticlenid
              AND {$oBaseObject->getSqlActiveSnippet()} 
            ORDER BY oxobject2article.oxsort";

        // #525 bidirectional cross-selling
        if ($myConfig->getConfigParam('blBidirectCross')) {
            $sSelect = "
                (
                    SELECT $sArticleTable.*, O2A1.OXSORT as sorting FROM $sArticleTable
                        INNER JOIN oxobject2article AS O2A1 on
                            ( O2A1.oxobjectid = $sArticleTable.oxid AND O2A1.oxarticlenid = :oxarticlenid )
                    WHERE 1
                    AND " . $oBaseObject->getSqlActiveSnippet() . "
                    AND ($sArticleTable.oxid != :oxarticlenid)
                )
                UNION
                (
                    SELECT $sArticleTable.*, O2A2.OXSORT as sorting FROM $sArticleTable
                        INNER JOIN oxobject2article AS O2A2 ON
                            ( O2A2.oxarticlenid = $sArticleTable.oxid AND O2A2.oxobjectid = :oxarticlenid )
                    WHERE 1
                    AND " . $oBaseObject->getSqlActiveSnippet() . "
                    AND ($sArticleTable.oxid != :oxarticlenid)
                )
                ORDER BY sorting";
        }

        $this->setSqlLimit(0, $myConfig->getConfigParam('iNrofCrossellArticles'));
        $this->selectString($sSelect, [
            ':oxarticlenid' => $sArticleId
        ]);
    }

    /**
     * Loads article accessories
     *
     * @param string $sArticleId Article id
     *
     * @return void
     */
    public function loadArticleAccessoires($sArticleId)
    {
        $myConfig = Registry::getConfig();

        // Performance
        if (!$myConfig->getConfigParam('bl_perfLoadAccessoires')) {
            return;
        }

        $oBaseObject = $this->getBaseObject();
        $sArticleTable = $oBaseObject->getViewName();

        $sSelect = "select $sArticleTable.* from oxaccessoire2article 
            left join $sArticleTable on oxaccessoire2article.oxobjectid=$sArticleTable.oxid ";
        $sSelect .= "where oxaccessoire2article.oxarticlenid = :oxarticlenid ";
        $sSelect .= " and $sArticleTable.oxid is not null and " . $oBaseObject->getSqlActiveSnippet();
        //sorting articles
        $sSelect .= " order by oxaccessoire2article.oxsort";

        $this->selectString($sSelect, [
            ':oxarticlenid' => $sArticleId
        ]);
    }

    /**
     * Loads only ID's and create Fake objects for cmp_categories.
     *
     * @param string $sCatId Category tree ID
     * @param array|null $aSessionFilter Like array ( catid => array( attrid => value,...))
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadCategoryIds($sCatId, $aSessionFilter)
    {
        $sArticleTable = $this->getBaseObject()->getViewName();
        $sSelect = $this->_getCategorySelect($sArticleTable . '.oxid as oxid', $sCatId, $aSessionFilter);

        $this->_createIdListFromSql($sSelect);
    }

    /**
     * Loads articles for the give Category
     *
     * @param string $sCatId Category tree ID
     * @param array|null $aSessionFilter Like array ( catid => array( attrid => value,...))
     * @param int|null $iLimit Limit
     *
     * @return integer total Count of Articles in this Category
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadCategoryArticles($sCatId, $aSessionFilter, $iLimit = null)
    {
        $sArticleFields = $this->getBaseObject()->getSelectFields();

        $sSelect = $this->_getCategorySelect($sArticleFields, $sCatId, $aSessionFilter);

        // calc count - we can not use count($this) here as we might have paging enabled
        // #1970C - if any filters are used, we can not use cached category article count
        $iArticleCount = null;
        if ($aSessionFilter) {
            $iArticleCount = DatabaseProvider::getDb()->getOne($this->_getCategoryCountSelect($sCatId, $aSessionFilter));
        }

        if ($iLimit = (int) $iLimit) {
            $sSelect .= " LIMIT $iLimit";
        }

        $this->selectString($sSelect);

        if ($iArticleCount !== null) {
            return $iArticleCount;
        }

        // this select is FAST so no need to hassle here with getNrOfArticles()
        return Registry::getUtilsCount()->getCatArticleCount($sCatId);
    }

    /**
     * Loads articles for the recommlist
     *
     * @param string $sRecommId Recommlist ID
     * @param null $sArticlesFilter Additional filter for recommlists items
     * @throws DatabaseConnectionException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     */
    public function loadRecommArticles($sRecommId, $sArticlesFilter = null)
    {
        $sSelect = $this->_getArticleSelect($sRecommId, $sArticlesFilter);
        $this->selectString($sSelect);
    }

    /**
     * Loads only ID's and create Fake objects.
     *
     * @param string $sRecommId Recommlist ID
     * @param string $sArticlesFilter Additional filter for recommlists items
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     */
    public function loadRecommArticleIds($sRecommId, $sArticlesFilter)
    {
        $sSelect = $this->_getArticleSelect($sRecommId, $sArticlesFilter);

        $sArtView = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $sPartial = substr($sSelect, strpos($sSelect, ' from '));
        $sSelect = "select distinct $sArtView.oxid $sPartial ";

        $this->_createIdListFromSql($sSelect);
    }

    /**
     * Returns the appropriate SQL select
     *
     * @param string $sRecommId Recommlist ID
     * @param null $sArticlesFilter Additional filter for recommlists items
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     */
    protected function _getArticleSelect($sRecommId, $sArticlesFilter = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sRecommId = DatabaseProvider::getDb()->quote($sRecommId);

        $sArtView = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $sSelect = "select distinct $sArtView.*, oxobject2list.oxdesc from oxobject2list ";
        $sSelect .= "left join $sArtView on oxobject2list.oxobjectid = $sArtView.oxid ";
        $sSelect .= "where (oxobject2list.oxlistid = $sRecommId) " . $sArticlesFilter;

        return $sSelect;
    }

    /**
     * Loads only ID's and create Fake objects for cmp_categories.
     *
     * @param string $sSearchStr Search string
     * @param string $sSearchCat Search within category
     * @param string $sSearchVendor Search within vendor
     * @param string $sSearchManufacturer Search within manufacturer
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadSearchIds($sSearchStr = '', $sSearchCat = '', $sSearchVendor = '', $sSearchManufacturer = '')
    {
        $oDb = DatabaseProvider::getDb();
        $sSearchCat = $sSearchCat ? $sSearchCat : null;
        $sSearchVendor = $sSearchVendor ? $sSearchVendor : null;
        $sSearchManufacturer = $sSearchManufacturer ? $sSearchManufacturer : null;

        $sWhere = null;

        if ($sSearchStr) {
            $sWhere = $this->_getSearchSelect($sSearchStr);
        }

        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');

        // longdesc field now is kept on different table
        $sDescJoin = $this->getDescriptionJoin();

        // load the articles
        $sSelect = "select $sArticleTable.oxid, $sArticleTable.oxtimestamp from $sArticleTable $sDescJoin where ";

        // must be additional conditions in select if searching in category
        if ($sSearchCat) {
            $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');
            $sSelect = "select $sArticleTable.oxid from $sO2CView as oxobject2category, $sArticleTable $sDescJoin ";
            $sSelect .= "where oxobject2category.oxcatnid=" . $oDb->quote($sSearchCat) . " and oxobject2category.oxobjectid=$sArticleTable.oxid and ";
        }
        $sSelect .= $this->getBaseObject()->getSqlActiveSnippet();
        $sSelect .= " and $sArticleTable.oxparentid = '' and $sArticleTable.oxissearch = 1 ";

        // #671
        if ($sSearchVendor) {
            $sSelect .= " and $sArticleTable.oxvendorid = " . $oDb->quote($sSearchVendor) . " ";
        }

        if ($sSearchManufacturer) {
            $sSelect .= " and $sArticleTable.oxmanufacturerid = " . $oDb->quote($sSearchManufacturer) . " ";
        }
        $sSelect .= $sWhere;

        if ($this->_sCustomSorting) {
            $sSelect .= " order by {$this->_sCustomSorting} ";
        }

        $this->_createIdListFromSql($sSelect);
    }

    /**
     * Loads ID list of appropriate price products
     *
     * @param float $dPriceFrom Starting price
     * @param float $dPriceTo Max price
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadPriceIds($dPriceFrom, $dPriceTo)
    {
        $sSelect = $this->_getPriceSelect($dPriceFrom, $dPriceTo);
        $this->_createIdListFromSql($sSelect);
    }

    /**
     * Loads articles, that price is bigger than passed $dPriceFrom and smaller
     * than passed $dPriceTo. Returns count of selected articles.
     *
     * @param double $dPriceFrom Price from
     * @param double $dPriceTo   Price to
     * @param object $oCategory  Active category object
     *
     * @return integer
     */
    public function loadPriceArticles($dPriceFrom, $dPriceTo, $oCategory = null)
    {
        $sSelect = $this->_getPriceSelect($dPriceFrom, $dPriceTo);

        startProfile("loadPriceArticles");
        $this->selectString($sSelect);
        stopProfile("loadPriceArticles");

        if (!$oCategory) {
            return $this->count();
        }

        return Registry::getUtilsCount()->getPriceCatArticleCount($oCategory->getId(), $dPriceFrom, $dPriceTo);
    }

    /**
     * Loads Products for specified vendor
     *
     * @param string $sVendorId Vendor id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadVendorIDs($sVendorId)
    {
        $sSelect = $this->_getVendorSelect($sVendorId);
        $this->_createIdListFromSql($sSelect);
    }

    /**
     * Loads Products for specified Manufacturer
     *
     * @param string $sManufacturerId Manufacturer id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadManufacturerIDs($sManufacturerId)
    {
        $sSelect = $this->_getManufacturerSelect($sManufacturerId);
        $this->_createIdListFromSql($sSelect);
    }

    /**
     * Loads articles that belongs to vendor, passed by parameter $sVendorId.
     * Returns count of selected articles.
     *
     * @param string $sVendorId Vendor ID
     * @param null $oVendor Active vendor object
     *
     * @return integer
     * @throws DatabaseConnectionException
     */
    public function loadVendorArticles($sVendorId, $oVendor = null)
    {
        $sSelect = $this->_getVendorSelect($sVendorId);
        $this->selectString($sSelect);

        return Registry::getUtilsCount()->getVendorArticleCount($sVendorId);
    }

    /**
     * Loads articles that belongs to Manufacturer, passed by parameter $sManufacturerId.
     * Returns count of selected articles.
     *
     * @param string $sManufacturerId Manufacturer ID
     * @param null $oManufacturer Active Manufacturer object
     *
     * @return integer
     * @throws DatabaseConnectionException
     */
    public function loadManufacturerArticles($sManufacturerId, $oManufacturer = null)
    {
        $sSelect = $this->_getManufacturerSelect($sManufacturerId);
        $this->selectString($sSelect);

        return Registry::getUtilsCount()->getManufacturerArticleCount($sManufacturerId);
    }

    /**
     * Load the list by article ids
     *
     * @param array $aIds Article ID array
     *
     * @return void
     * @throws DatabaseConnectionException
     */
    public function loadIds($aIds)
    {
        if (!count($aIds)) {
            $this->clear();

            return;
        }

        $oBaseObject = $this->getBaseObject();
        $sArticleTable = $oBaseObject->getViewName();
        $sArticleFields = $oBaseObject->getSelectFields();

        $oxIdsSql = implode(',', DatabaseProvider::getDb()->quoteArray($aIds));

        $sSelect = "select $sArticleFields from $sArticleTable ";
        $sSelect .= "where $sArticleTable.oxid in ( " . $oxIdsSql . " ) and ";
        $sSelect .= $oBaseObject->getSqlActiveSnippet();

        $this->selectString($sSelect);
    }

    /**
     * Loads the article list by orders ids
     *
     * @param array $aOrders user orders array
     *
     * @return void
     */
    public function loadOrderArticles($aOrders)
    {
        if (!count($aOrders)) {
            $this->clear();

            return;
        }

        foreach ($aOrders as $iKey => $oOrder) {
            $aOrdersIds[] = $oOrder->getId();
        }

        $oBaseObject = $this->getBaseObject();
        $sArticleTable = $oBaseObject->getViewName();
        $sArticleFields = $oBaseObject->getSelectFields();
        $sArticleFields = str_replace("`$sArticleTable`.`oxid`", "`oxorderarticles`.`oxartid` AS `oxid`", $sArticleFields);

        $sSelect = "SELECT $sArticleFields FROM oxorderarticles ";
        $sSelect .= "left join $sArticleTable on oxorderarticles.oxartid = $sArticleTable.oxid ";
        $sSelect .= "WHERE oxorderarticles.oxorderid IN ( '" . implode("','", $aOrdersIds) . "' ) ";
        $sSelect .= "order by $sArticleTable.oxid ";

        $this->selectString($sSelect);

        // not active or not available products must not have button "tobasket"
        $sNow = date('Y-m-d H:i:s');
        foreach ($this as $oArticle) {
            if (
                !$oArticle->oxarticles__oxactive->value &&
                (
                    $oArticle->oxarticles__oxactivefrom->value > $sNow ||
                 $oArticle->oxarticles__oxactiveto->value < $sNow
                )
            ) {
                $oArticle->setBuyableState(false);
            }
        }
    }

    /**
     * Loads list of low stock state products
     *
     * @param array $aBasketContents product ids array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadStockRemindProducts($aBasketContents)
    {
        if (is_array($aBasketContents) && count($aBasketContents)) {
            $oDb = DatabaseProvider::getDb();
            foreach ($aBasketContents as $oBasketItem) {
                $aArtIds[] = $oDb->quote($oBasketItem->getProductId());
            }

            $oBaseObject = $this->getBaseObject();

            $sFieldNames = $oBaseObject->getSelectFields();
            $sTable = $oBaseObject->getViewName();

            // fetching actual db stock state and reminder status
            $sQ = "select {$sFieldNames} from {$sTable} where {$sTable}.oxid in ( " . implode(",", $aArtIds) . " ) and
                          oxremindactive = '1' and oxstock <= oxremindamount";
            $this->selectString($sQ);

            // updating stock reminder state
            if ($this->count()) {
                $sQ = "update {$sTable} set oxremindactive = '2' where :tableName in ( " . implode(",", $aArtIds) . " ) and 
                              oxremindactive = '1' and oxstock <= oxremindamount";
                $oDb->execute($sQ, [':tableName' => $sTable . '.oxid']);
            }
        }
    }

    /**
     * Calculates, updates and returns next price renew time
     *
     * @return int
     * @throws DatabaseConnectionException
     */
    public function renewPriceUpdateTime()
    {
        $iTimeToUpdate = $this->fetchNextUpdateTime();

        // next day?
        $iCurrUpdateTime = Registry::getUtilsDate()->getTime();
        $iNextUpdateTime = $iCurrUpdateTime + 3600 * 24;

        // renew next update time
        if (!$iTimeToUpdate || $iTimeToUpdate > $iNextUpdateTime) {
            $iTimeToUpdate = $iNextUpdateTime;
        }

        Registry::getConfig()->saveShopConfVar("num", "iTimeToUpdatePrices", $iTimeToUpdate);

        return $iTimeToUpdate;
    }

    /**
     * Updates prices where new price > 0, update time != '0000-00-00 00:00:00'
     * and <= CURRENT_TIMESTAMP. Returns update execution state (result of DatabaseProvider::execute())
     *
     * @param bool $blForceUpdate if true, forces price update without timeout check, default value is FALSE
     *
     * @return false|int
     *@throws Exception
     *
     */
    public function updateUpcomingPrices($blForceUpdate = false)
    {
        $blUpdated = false;

        if ($blForceUpdate || $this->_canUpdatePrices()) {
            // Transaction picks master automatically (see ESDEV-3804 and ESDEV-3822).
            $database = DatabaseProvider::getDb();

            $database->startTransaction();
            try {
                $sCurrUpdateTime = date("Y-m-d H:i:s", Registry::getUtilsDate()->getTime());

                // Collect article id's for later recalculation.
                $sQ = "SELECT `oxid` FROM `oxarticles`
                   WHERE `oxupdatepricetime` > 0 AND `oxupdatepricetime` <= :oxupdatepricetime";

                $aUpdatedArticleIds = $database->getCol($sQ, [
                    ':oxupdatepricetime' => $sCurrUpdateTime
                ]);

                // updating oxarticles
                $blUpdated = $this->updateOxArticles($sCurrUpdateTime, $database);

                // renew update time in case update is not forced
                if (!$blForceUpdate) {
                    $this->renewPriceUpdateTime();
                }

                $database->commitTransaction();
            } catch (Exception $exception) {
                $database->rollbackTransaction();
                throw $exception;
            }

            // recalculate oxvarminprice and oxvarmaxprice for parent
            if (is_array($aUpdatedArticleIds)) {
                foreach ($aUpdatedArticleIds as $sArticleId) {
                    $oArticle = oxNew(Article::class);
                    $oArticle->load($sArticleId);
                    $oArticle->onChange();
                }
            }

            $this->updateArticles($aUpdatedArticleIds);
        }

        return $blUpdated;
    }

    /**
     * fills the list simply with keys of the oxid and the position as value for the given sql
     *
     * @param string $sSql SQL select
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "createIdListFromSql" in next major
     */
    protected function _createIdListFromSql($sSql) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $rs = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->select($sSql);
        if ($rs && $rs->count() > 0) {
            while (!$rs->EOF) {
                $rs->fields = array_change_key_case($rs->fields, CASE_LOWER);
                $this[$rs->fields['oxid']] = $rs->fields['oxid']; //only the oxid
                $rs->fetchRow();
            }
        }
    }

    /**
     * Returns sql to fetch ids of articles fitting current filter
     *
     * @param string $sCatId category id
     * @param array $aFilter filters for this category
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFilterIdsSql" in next major
     */
    protected function _getFilterIdsSql($sCatId, $aFilter) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');
        $sO2AView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2attribute');

        $sFilter = '';
        $iCnt = 0;

        $oDb = DatabaseProvider::getDb();
        foreach ($aFilter as $sAttrId => $sValue) {
            if ($sValue) {
                if ($sFilter) {
                    $sFilter .= ' or ';
                }
                $sValue = $oDb->quote($sValue);
                $sAttrId = $oDb->quote($sAttrId);

                $sFilter .= "( oa.oxattrid = {$sAttrId} and oa.oxvalue = {$sValue} )";
                $iCnt++;
            }
        }
        if ($sFilter) {
            $sFilter = "WHERE $sFilter ";
        }

        $sFilterSelect = "select oc.oxobjectid as oxobjectid, count(*) as cnt from ";
        $sFilterSelect .= "(SELECT * FROM $sO2CView WHERE $sO2CView.oxcatnid = '$sCatId' GROUP BY $sO2CView.oxobjectid, $sO2CView.oxcatnid) as oc ";
        $sFilterSelect .= "INNER JOIN $sO2AView as oa ON ( oa.oxobjectid = oc.oxobjectid ) ";

        return $sFilterSelect . "{$sFilter} GROUP BY oa.oxobjectid HAVING cnt = $iCnt ";
    }

    /**
     * Returns filtered articles sql "oxid in (filtered ids)" part
     *
     * @param string $sCatId category id
     * @param array $aFilter filters for this category
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFilterSql" in next major
     */
    protected function _getFilterSql($sCatId, $aFilter) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $aIds = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll($this->_getFilterIdsSql($sCatId, $aFilter));
        $sIds = '';

        if ($aIds) {
            foreach ($aIds as $aArt) {
                if ($sIds) {
                    $sIds .= ', ';
                }
                $sIds .= DatabaseProvider::getDb()->quote(current($aArt));
            }

            if ($sIds) {
                $sFilterSql = " and $sArticleTable.oxid in ( $sIds ) ";
            }
            // bug fix #0001695: if no articles found return false
        } elseif (!(current($aFilter) == '' && count(array_unique($aFilter)) == 1)) {
            $sFilterSql = " and false ";
        }

        return $sFilterSql;
    }

    /**
     * Creates SQL Statement to load Articles, etc.
     *
     * @param string $sFields Fields which are loaded e.g. "oxid" or "*" etc.
     * @param string $sCatId Category tree ID
     * @param array $aSessionFilter Like array ( catid => array( attrid => value,...))
     *
     * @return string SQL
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategorySelect" in next major
     */
    protected function _getCategorySelect($sFields, $sCatId, $aSessionFilter) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');

        // ----------------------------------
        // sorting
        $sSorting = '';
        if ($this->_sCustomSorting) {
            $sSorting = " {$this->_sCustomSorting} , ";
        }

        // ----------------------------------
        // filtering ?
        $sFilterSql = '';
        $iLang = Registry::getLang()->getBaseLanguage();
        if ($aSessionFilter && isset($aSessionFilter[$sCatId][$iLang])) {
            $sFilterSql = $this->_getFilterSql($sCatId, $aSessionFilter[$sCatId][$iLang]);
        }

        $oDb = DatabaseProvider::getDb();

        $sSelect = "SELECT $sFields, $sArticleTable.oxtimestamp FROM $sO2CView as oc left join $sArticleTable
                    ON $sArticleTable.oxid = oc.oxobjectid
                    WHERE " . $this->getBaseObject()->getSqlActiveSnippet() . " and $sArticleTable.oxparentid = ''
                    and oc.oxcatnid = " . $oDb->quote($sCatId) . " $sFilterSql ORDER BY $sSorting oc.oxpos, oc.oxobjectid ";

        return $sSelect;
    }

    /**
     * Creates SQL Statement to load Articles Count, etc.
     *
     * @param string $sCatId Category tree ID
     * @param array $aSessionFilter Like array ( catid => array( attrid => value,...))
     *
     * @return string SQL
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategoryCountSelect" in next major
     */
    protected function _getCategoryCountSelect($sCatId, $aSessionFilter) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');


        // ----------------------------------
        // filtering ?
        $sFilterSql = '';
        $iLang = Registry::getLang()->getBaseLanguage();
        if ($aSessionFilter && isset($aSessionFilter[$sCatId][$iLang])) {
            $sFilterSql = $this->_getFilterSql($sCatId, $aSessionFilter[$sCatId][$iLang]);
        }

        $oDb = DatabaseProvider::getDb();

        $sSelect = "SELECT COUNT(*) FROM $sO2CView as oc left join $sArticleTable
                    ON $sArticleTable.oxid = oc.oxobjectid
                    WHERE " . $this->getBaseObject()->getSqlActiveSnippet() . " and $sArticleTable.oxparentid = ''
                    and oc.oxcatnid = " . $oDb->quote($sCatId) . " $sFilterSql ";

        return $sSelect;
    }

    /**
     * Forms and returns SQL query string for search in DB.
     *
     * @param string $sSearchString searching string
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSearchSelect" in next major
     */
    protected function _getSearchSelect($sSearchString) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // check if it has string at all
        if (!$sSearchString || !str_replace(' ', '', $sSearchString)) {
            return '';
        }

        $oDb = DatabaseProvider::getDb();
        $myConfig = Registry::getConfig();
        $sArticleTable = $this->getBaseObject()->getViewName();

        $aSearch = explode(' ', $sSearchString);

        $sSearch = ' and ( ';
        $blSep = false;

        // #723
        if ($myConfig->getConfigParam('blSearchUseAND')) {
            $sSearchSep = ' and ';
        } else {
            $sSearchSep = ' or ';
        }

        $aSearchCols = $myConfig->getConfigParam('aSearchCols');
        $myUtilsString = Registry::getUtilsString();
        foreach ($aSearch as $sSearchString) {
            if (!strlen($sSearchString)) {
                continue;
            }

            if ($blSep) {
                $sSearch .= $sSearchSep;
            }
            $blSep2 = false;
            $sSearch .= '( ';

            $sUml = $myUtilsString->prepareStrForSearch($sSearchString);
            foreach ($aSearchCols as $sField) {
                if ($blSep2) {
                    $sSearch .= ' or ';
                }

                // as long description now is on different table must differ
                $sSearchTable = $this->getSearchTableName($sArticleTable, $sField);

                $sSearch .= $sSearchTable . '.' . $sField . ' like ' . $oDb->quote('%' . $sSearchString . '%') . ' ';
                if ($sUml) {
                    $sSearch .= ' or ' . $sSearchTable . '.' . $sField . ' like ' . $oDb->quote('%' . $sUml . '%');
                }
                $blSep2 = true;
            }
            $sSearch .= ' ) ';
            $blSep = true;
        }
        $sSearch .= ' ) ';

        return $sSearch;
    }

    /**
     * Builds SQL for selecting articles by price
     *
     * @param double $dPriceFrom Starting price
     * @param double $dPriceTo   Max price
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getPriceSelect" in next major
     */
    protected function _getPriceSelect($dPriceFrom, $dPriceTo) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oBaseObject = $this->getBaseObject();
        $sArticleTable = $oBaseObject->getViewName();
        $sSelectFields = $oBaseObject->getSelectFields();

        $sSelect = "select {$sSelectFields} from {$sArticleTable} where oxvarminprice >= 0 ";
        $sSelect .= $dPriceTo ? "and oxvarminprice <= " . (double) $dPriceTo . " " : " ";
        $sSelect .= $dPriceFrom ? "and oxvarminprice  >= " . (double) $dPriceFrom . " " : " ";

        $sSelect .= " and " . $oBaseObject->getSqlActiveSnippet() . " and {$sArticleTable}.oxissearch = 1";

        if (!$this->_sCustomSorting) {
            $sSelect .= " order by {$sArticleTable}.oxvarminprice asc , {$sArticleTable}.oxid";
        } else {
            $sSelect .= " order by {$this->_sCustomSorting}, {$sArticleTable}.oxid ";
        }

        return $sSelect;
    }

    /**
     * Builds vendor select SQL statement
     *
     * @param string $sVendorId Vendor ID
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVendorSelect" in next major
     */
    protected function _getVendorSelect($sVendorId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $oBaseObject = $this->getBaseObject();
        $sFieldNames = $oBaseObject->getSelectFields();
        $sSelect = "select $sFieldNames from $sArticleTable ";
        $sSelect .= "where $sArticleTable.oxvendorid = " . DatabaseProvider::getDb()->quote($sVendorId) . " ";
        $sSelect .= " and " . $oBaseObject->getSqlActiveSnippet() . " and $sArticleTable.oxparentid = ''  ";

        if ($this->_sCustomSorting) {
            $sSelect .= " ORDER BY {$this->_sCustomSorting} ";
        }

        return $sSelect;
    }

    /**
     * Builds Manufacturer select SQL statement
     *
     * @param string $sManufacturerId Manufacturer ID
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getManufacturerSelect" in next major
     */
    protected function _getManufacturerSelect($sManufacturerId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $oBaseObject = $this->getBaseObject();
        $sFieldNames = $oBaseObject->getSelectFields();
        $sSelect = "select $sFieldNames from $sArticleTable ";
        $sSelect .= "where $sArticleTable.oxmanufacturerid = " . DatabaseProvider::getDb()->quote($sManufacturerId) . " ";
        $sSelect .= " and " . $oBaseObject->getSqlActiveSnippet() . " and $sArticleTable.oxparentid = ''  ";

        if ($this->_sCustomSorting) {
            $sSelect .= " ORDER BY {$this->_sCustomSorting} ";
        }

        return $sSelect;
    }

    /**
     * Checks if price update can be executed - current time > next price update time
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "canUpdatePrices" in next major
     */
    protected function _canUpdatePrices() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oConfig = Registry::getConfig();
        $blCan = false;

        // crontab is off?
        if (!$oConfig->getConfigParam("blUseCron")) {
            $iTimeToUpdate = $oConfig->getConfigParam("iTimeToUpdatePrices");
            if (!$iTimeToUpdate || $iTimeToUpdate <= Registry::getUtilsDate()->getTime()) {
                $blCan = true;
            }
        }

        return $blCan;
    }

    /**
     * Method fetches next update time for renewing price update time.
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function fetchNextUpdateTime()
    {
        // Function is called inside a transaction or from admin backend which uses master connection only.
        // Transaction picks master automatically (see ESDEV-3804 and ESDEV-3822).
        $database = DatabaseProvider::getDb();

        // fetching next update time
        $sQ = $this->getQueryToFetchNextUpdateTime();

        $iTimeToUpdate = $database->getOne(sprintf($sQ, "`oxarticles`"));

        return $iTimeToUpdate;
    }

    /**
     * Returns query to fetch next update time.
     *
     * @return string
     */
    protected function getQueryToFetchNextUpdateTime()
    {
        return "select unix_timestamp( oxupdatepricetime ) from %s where oxupdatepricetime > 0 order by oxupdatepricetime asc";
    }

    /**
     * Updates article.
     *
     * @param string $sCurrUpdateTime
     * @param DatabaseInterface $oDb
     *
     * @return int
     * @throws DatabaseErrorException
     */
    protected function updateOxArticles($sCurrUpdateTime, $oDb)
    {
        $sQ = $this->getQueryToUpdateOxArticle($sCurrUpdateTime);
        $blUpdated = $oDb->execute(sprintf($sQ, "`oxarticles`"));

        return $blUpdated;
    }

    /**
     * Method returns query to update article.
     *
     * @param string $sCurrUpdateTime
     *
     * @return string
     */
    protected function getQueryToUpdateOxArticle($sCurrUpdateTime)
    {
        $sQ = "UPDATE %s SET
                       `oxprice`  = IF( `oxupdateprice` > 0, `oxupdateprice`, `oxprice` ),
                       `oxpricea` = IF( `oxupdatepricea` > 0, `oxupdatepricea`, `oxpricea` ),
                       `oxpriceb` = IF( `oxupdatepriceb` > 0, `oxupdatepriceb`, `oxpriceb` ),
                       `oxpricec` = IF( `oxupdatepricec` > 0, `oxupdatepricec`, `oxpricec` ),
                       `oxupdatepricetime` = 0,
                       `oxupdateprice`     = 0,
                       `oxupdatepricea`    = 0,
                       `oxupdatepriceb`    = 0,
                       `oxupdatepricec`    = 0
                   WHERE
                       `oxupdatepricetime` > 0 AND
                       `oxupdatepricetime` <= '{$sCurrUpdateTime}'";
        return $sQ;
    }

    /**
     * Method is used for overloading.
     *
     * @param array $aUpdatedArticleIds
     */
    protected function updateArticles($aUpdatedArticleIds)
    {
    }

    /**
     * Get description join. Needed in case of searching for data in table oxartextends or its views.
     *
     * @return string
     */
    protected function getDescriptionJoin()
    {
        $table = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
        $descriptionJoin = '';
        $searchColumns = Registry::getConfig()->getConfigParam('aSearchCols');

        if (is_array($searchColumns) && in_array('oxlongdesc', $searchColumns)) {
            $viewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxartextends');
            $descriptionJoin = " LEFT JOIN $viewName ON {$viewName}.oxid={$table}.oxid ";
        }
        return $descriptionJoin;
    }

    /**
     * Get search table name.
     * Needed in case of searching for data in table oxartextends or its views.
     *
     * @param string $table
     * @param string $field Chose table depending on field.
     *
     * @return string
     */
    protected function getSearchTableName($table, $field)
    {
        $searchTable = $table;

        if ($field == 'oxlongdesc') {
            $searchTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxartextends');
        }

        return $searchTable;
    }
}
