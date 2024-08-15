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
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages manufacturer assignment to articles
 */
class ManufacturerMainAjax extends ListComponentAjax
{
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_blAllowExtColumns = true;

    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        // field , table, visible, multilanguage, id
        'container1' => [
            ['oxartnum', 'oxarticles', 1, 0, 0],
            ['oxtitle', 'oxarticles', 1, 1, 0],
            ['oxean', 'oxarticles', 1, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxarticles', 0, 0, 1],
        ],
        'container2' => [
            ['oxartnum', 'oxarticles', 1, 0, 0],
            ['oxtitle', 'oxarticles', 1, 1, 0],
            ['oxean', 'oxarticles', 1, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxarticles', 0, 0, 1],
        ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $config = Registry::getConfig();
        $oRequest = Registry::getRequest();

        // looking for table/view
        $articlesViewName = $this->_getViewName('oxarticles');
        $objectToCategoryViewName = $this->_getViewName('oxobject2category');
        $database = DatabaseProvider::getDb();

        $manufacturerId = $oRequest->getRequestEscapedParameter('oxid');
        $syncedManufacturerId = $oRequest->getRequestEscapedParameter('synchoxid');

        // Manufacturer selected or not ?
        if (!$manufacturerId) {
            // performance
            $query = ' from ' . $articlesViewName . ' where ' . $articlesViewName . '.oxshopid="' . $config->getShopId() . '" and 1 ';
            $query .= $config->getConfigParam('blVariantsSelection') ? '' : " and $articlesViewName.oxparentid = '' and $articlesViewName.oxmanufacturerid != " . $database->quote($syncedManufacturerId);
        } elseif ($syncedManufacturerId && $syncedManufacturerId != $manufacturerId) {
            // selected category ?
            $query = " from $objectToCategoryViewName left join $articlesViewName on ";
            $query .= $config->getConfigParam('blVariantsSelection') ? " ( $articlesViewName.oxid = $objectToCategoryViewName.oxobjectid or $articlesViewName.oxparentid = $objectToCategoryViewName.oxobjectid )" : " $articlesViewName.oxid = $objectToCategoryViewName.oxobjectid ";
            $query .= 'where ' . $articlesViewName . '.oxshopid="' . $config->getShopId() . '" and ' . $objectToCategoryViewName . '.oxcatnid = ' . $database->quote($manufacturerId) . ' and ' . $articlesViewName . '.oxmanufacturerid != ' . $database->quote($syncedManufacturerId);
            $query .= $config->getConfigParam('blVariantsSelection') ? '' : " and $articlesViewName.oxparentid = '' ";
        } else {
            $query = " from $articlesViewName where $articlesViewName.oxmanufacturerid = " . $database->quote($manufacturerId);
            $query .= $config->getConfigParam('blVariantsSelection') ? '' : " and $articlesViewName.oxparentid = '' ";
        }

        return $query;
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $query query to add filter condition
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "addFilter" in next major
     */
    protected function _addFilter($query) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $config = Registry::getConfig();
        $articleViewName = $this->_getViewName('oxarticles');
        $query = parent::_addFilter($query);

        // display variants or not ?
        $query .= $config->getConfigParam('blVariantsSelection') ? ' group by ' . $articleViewName . '.oxid ' : '';

        return $query;
    }

    /**
     * Removes article from Manufacturer config
     */
    public function removeManufacturer()
    {
        $articleIds = $this->_getActionIds('oxarticles.oxid');
        $manufacturerId = Registry::getRequest()->getRequestEscapedParameter('oxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $articleViewTable = $this->_getViewName('oxarticles');
            $articleIds = $this->_getAll($this->_addFilter("select $articleViewTable.oxid " . $this->_getQuery()));
        }

        if (is_array($articleIds) && !empty($articleIds)) {
            $query = $this->formManufacturerRemovalQuery($articleIds);
            DatabaseProvider::getDb()->execute($query);

            $this->resetCounter("manufacturerArticle", $manufacturerId);
        }
    }

    /**
     * Forms and returns query for manufacturers removal.
     *
     * @param array $articlesToRemove Ids of manufacturers which should be removed.
     *
     * @return string
     */
    protected function formManufacturerRemovalQuery($articlesToRemove)
    {
        return "
          UPDATE oxarticles
          SET oxmanufacturerid = null
          WHERE oxid IN ( " . implode(", ", DatabaseProvider::getDb()->quoteArray($articlesToRemove)) . ") ";
    }

    /**
     * Adds article to Manufacturer config
     */
    public function addManufacturer()
    {
        $oRequest = Registry::getRequest();

        $articleIds = $this->_getActionIds('oxarticles.oxid');
        $manufacturerId = $oRequest->getRequestEscapedParameter('synchoxid');

        if ($oRequest->getRequestEscapedParameter('all')) {
            $articleViewName = $this->_getViewName('oxarticles');
            $articleIds = $this->_getAll($this->_addFilter("select $articleViewName.oxid " . $this->_getQuery()));
        }

        if ($manufacturerId && $manufacturerId != "-1" && is_array($articleIds)) {
            $database = DatabaseProvider::getDb();

            $query = $this->formArticleToManufacturerAdditionQuery($manufacturerId, $articleIds);
            $database->execute($query);
            $this->resetCounter("manufacturerArticle", $manufacturerId);
        }
    }

    /**
     * Forms and returns query for articles addition to manufacturer.
     *
     * @param string $manufacturerId Manufacturer id.
     * @param array  $articlesToAdd  Array of article ids to be added to manufacturer.
     *
     * @return string
     */
    protected function formArticleToManufacturerAdditionQuery($manufacturerId, $articlesToAdd)
    {
        $database = DatabaseProvider::getDb();

        return "
            UPDATE oxarticles
            SET oxmanufacturerid = " . $database->quote($manufacturerId) . "
            WHERE oxid IN ( " . implode(", ", $database->quoteArray($articlesToAdd)) . " )";
    }
}
