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

use OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax;
use OxidEsales\Eshop\Application\Model\Object2Category;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use Exception;

/**
 * Class controls article assignment to category.
 */
class ArticleExtendAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,         visible, multilanguage, ident
            ['oxtitle', 'oxcategories', 1, 1, 0],
            ['oxdesc', 'oxcategories', 1, 1, 0],
            ['oxid', 'oxcategories', 0, 0, 0],
            ['oxid', 'oxcategories', 0, 0, 1]
        ],
        'container2' => [
            ['oxtitle', 'oxcategories', 1, 1, 0],
            ['oxdesc', 'oxcategories', 1, 1, 0],
            ['oxid', 'oxcategories', 0, 0, 0],
            ['oxid', 'oxobject2category', 0, 0, 1],
            ['oxtime', 'oxobject2category', 0, 0, 1],
            ['oxid', 'oxcategories', 0, 0, 1]
        ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $categoriesTable = $this->_getViewName('oxcategories');
        $objectToCategoryView = $this->_getViewName('oxobject2category');
        $database = DatabaseProvider::getDb();

        $oxId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $synchOxid = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        if ($oxId) {
            // all categories article is in
            $query = " from $objectToCategoryView left join $categoriesTable on $categoriesTable.oxid=$objectToCategoryView.oxcatnid ";
            $query .= " where $objectToCategoryView.oxobjectid = " . $database->quote($oxId)
                      . " and $categoriesTable.oxid is not null ";
        } else {
            $query = " from $categoriesTable where $categoriesTable.oxid not in ( ";
            $query .= " select $categoriesTable.oxid from $objectToCategoryView "
                      . "left join $categoriesTable on $categoriesTable.oxid=$objectToCategoryView.oxcatnid ";
            $query .= " where $objectToCategoryView.oxobjectid = " . $database->quote($synchOxid)
                      . " and $categoriesTable.oxid is not null ) and $categoriesTable.oxpriceto = '0'";
        }

        return $query;
    }

    /**
     * Returns array with DB records
     *
     * @param string $sQ SQL query
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDataFields" in next major
     */
    protected function _getDataFields($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $dataFields = parent::_getDataFields($sQ);
        if (Registry::getRequest()->getRequestEscapedParameter('oxid') && is_array($dataFields) && count($dataFields)) {
            // looking for smallest time value to mark record as main category ...
            $minimalPosition = null;
            $minimalValue = null;
            reset($dataFields);
            foreach ($dataFields as $position => $fields) {
                // already set ?
                if ($fields['_3'] == '0') {
                    $minimalPosition = null;
                    break;
                }

                if (!$minimalValue) {
                    $minimalValue = $fields['_3'];
                    $minimalPosition = $position;
                } elseif ($minimalValue > $fields['_3']) {
                    $minimalPosition = $position;
                }
            }

            // setting primary category
            if (isset($minimalPosition)) {
                $dataFields[$minimalPosition]['_3'] = '0';
            }
        }

        return $dataFields;
    }

    /**
     * Removes article from chosen category
     */
    public function removeCat()
    {
        $categoriesToRemove = $this->_getActionIds('oxcategories.oxid');

        $oxId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $dataBase = DatabaseProvider::getDb();

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $categoriesTable = $this->_getViewName('oxcategories');
            $categoriesToRemove = $this->_getAll($this->_addFilter("select {$categoriesTable}.oxid " . $this->_getQuery()));
        }

        // removing all
        if (is_array($categoriesToRemove) && count($categoriesToRemove)) {
            $query = "delete from oxobject2category where oxobject2category.oxobjectid = :oxobjectid and ";
            $query = $this->updateQueryForRemovingArticleFromCategory($query);
            $query .= " oxcatnid in (" . implode(', ', DatabaseProvider::getDb()->quoteArray($categoriesToRemove)) . ')';
            $dataBase->Execute($query, [
                ':oxobjectid' => $oxId
            ]);

            // updating oxtime values
            $this->_updateOxTime($oxId);
        }

        $this->resetArtSeoUrl($oxId, $categoriesToRemove);
        $this->resetContentCache();

        $this->onCategoriesRemoval($categoriesToRemove, $oxId);
    }

    /**
     * Adds article to chosen category
     *
     * @throws Exception
     */
    public function addCat()
    {
        $config = Registry::getConfig();
        $categoriesToAdd = $this->_getActionIds('oxcategories.oxid');
        $oxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');
        $shopId = $config->getShopId();
        $objectToCategoryView = $this->_getViewName('oxobject2category');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $categoriesTable = $this->_getViewName('oxcategories');
            $categoriesToAdd = $this->_getAll($this->_addFilter("select $categoriesTable.oxid " . $this->_getQuery()));
        }

        if (isset($categoriesToAdd) && is_array($categoriesToAdd)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = DatabaseProvider::getMaster();

            $objectToCategory = oxNew(Object2Category::class);

            foreach ($categoriesToAdd as $sAdd) {
                // check, if it's already in, then don't add it again
                $sSelect = "select 1 from " . $objectToCategoryView . " as oxobject2category " .
                    "where oxobject2category.oxcatnid = :oxcatnid " .
                    "and oxobject2category.oxobjectid = :oxobjectid";
                if ($database->getOne($sSelect, [':oxcatnid' => $sAdd, ':oxobjectid' => $oxId])) {
                    continue;
                }

                $objectToCategory->setId(md5($oxId . $sAdd . $shopId));
                $objectToCategory->oxobject2category__oxobjectid = new Field($oxId);
                $objectToCategory->oxobject2category__oxcatnid = new Field($sAdd);
                $objectToCategory->oxobject2category__oxtime = new Field(time());

                $objectToCategory->save();
            }

            $this->_updateOxTime($oxId);

            $this->resetArtSeoUrl($oxId);
            $this->resetContentCache();
            $this->onCategoriesAdd($categoriesToAdd);
        }
    }

    /**
     * Updates oxtime value for product
     *
     * @param string $oxId product id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateOxTime" in next major
     */
    protected function _updateOxTime($oxId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $database = DatabaseProvider::getDb();
        $objectToCategoryView = $this->_getViewName('oxobject2category');
        $queryToEmbed = $this->formQueryToEmbedForUpdatingTime();

        // updating oxtime values
        $query = "update oxobject2category set oxtime = 0 where oxobjectid = :oxobjectid {$queryToEmbed} and oxid = (
                    select oxid from (
                        select oxid from {$objectToCategoryView} where oxobjectid = :oxobjectid {$queryToEmbed}
                        order by oxtime limit 1
                    ) as _tmp
                )";
        $database->execute($query, [':oxobjectid' => $oxId]);
    }

    /**
     * Sets selected category as a default
     */
    public function setAsDefault()
    {
        $defCat = Registry::getRequest()->getRequestEscapedParameter('defcat');
        $oxId = Registry::getRequest()->getRequestEscapedParameter('oxid');

        $queryToEmbed = $this->formQueryToEmbedForSettingCategoryAsDefault();

        // #0003650: increment all product references independent to active shop
        $query = "update oxobject2category set oxtime = oxtime + 10 where oxobjectid = :oxobjectid {$queryToEmbed}";
        DatabaseProvider::getInstance()->getDb()->execute($query, [':oxobjectid' => $oxId]);

        // set main category for active shop
        $query = "update oxobject2category set oxtime = 0
                  where oxobjectid = :oxobjectid and oxcatnid = :oxcatnid {$queryToEmbed}";
        DatabaseProvider::getInstance()->getDb()->execute($query, [
            ':oxobjectid' => $oxId,
            ':oxcatnid' => $defCat
        ]);
        //echo "\n$sQ\n";

        // #0003366: invalidate article SEO for all shops
        Registry::getSeoEncoder()->markAsExpired($oxId, null, 1, null, "oxtype='oxarticle'");
        $this->resetContentCache();
    }

    /**
     * Method used for overloading and embed query.
     *
     * @param string $query
     *
     * @return string
     */
    protected function updateQueryForRemovingArticleFromCategory($query)
    {
        return $query;
    }

    /**
     * Method is used for overloading to do additional actions.
     *
     * @param array  $categoriesToRemove
     * @param string $oxId
     */
    protected function onCategoriesRemoval($categoriesToRemove, $oxId)
    {
    }

    /**
     * Method is used for overloading.
     *
     * @param array $categories
     */
    protected function onCategoriesAdd($categories)
    {
    }

    /**
     * Method is used for overloading to insert additional query condition.
     *
     * @return string
     */
    protected function formQueryToEmbedForUpdatingTime()
    {
        return '';
    }

    /**
     * Method is used for overloading to insert additional query condition.
     *
     * @return string
     */
    protected function formQueryToEmbedForSettingCategoryAsDefault()
    {
        return '';
    }
}
