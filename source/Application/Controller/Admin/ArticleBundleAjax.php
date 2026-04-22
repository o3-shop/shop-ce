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
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class controls article assignment to attributes
 */
class ArticleBundleAjax extends ListComponentAjax
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
        'container1' => [
            // field , table, visible, multilanguage, ident
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
     * @throws DatabaseConnectionException
     * @deprecated Use getQuery() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getQuery().
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        $oDb = DatabaseProvider::getDb();
        $sArticleTable = $this->getViewName('oxarticles');
        $sView = $this->getViewName('oxobject2category');

        $sSelId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchSelId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');
        $sQAdd = '';

        // category selected or not ?
        if (!$sSelId) {
            $sQAdd .= " from $sArticleTable where 1 ";
            $sQAdd .= $myConfig->getConfigParam('blVariantsSelection') ? '' : " and $sArticleTable.oxparentid = '' ";
        } else {
            // selected category ?
            if ($sSynchSelId) {
                $blVariantsSelectionParameter = $myConfig->getConfigParam('blVariantsSelection');
                $sSqlIfTrue = " ({$sArticleTable}.oxid=oxobject2category.oxobjectid " .
                              "or {$sArticleTable}.oxparentid=oxobject2category.oxobjectid)";
                $sSqlIfFalse = " $sArticleTable.oxid=oxobject2category.oxobjectid ";
                $sVariantsSqlSnippet = $blVariantsSelectionParameter ? $sSqlIfTrue : $sSqlIfFalse;

                $sQAdd = " from {$sView} as oxobject2category left join {$sArticleTable} on {$sVariantsSqlSnippet}" .
                         ' where oxobject2category.oxcatnid = ' . $oDb->quote($sSelId) . ' ';
            }
        }
        // #1513C/#1826C - skip references, to not existing articles
        $sQAdd .= " and $sArticleTable.oxid IS NOT NULL ";

        // skipping self from list
        $sQAdd .= " and $sArticleTable.oxid != " . $oDb->quote($sSynchSelId) . ' ';

        return $sQAdd;
    }

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal If your override does not fully replace the behavior, call parent::getQuery()
     *           (not the deprecated _getQuery()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getQuery()
    {
        return $this->_getQuery();
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated Use addFilter() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _addFilter().
     */
    protected function _addFilter($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArtTable = $this->getViewName('oxarticles');
        $sQ = parent::_addFilter($sQ);

        // display variants or not ?
        $sQ .= Registry::getConfig()->getConfigParam('blVariantsSelection') ? ' group by ' . $sArtTable . '.oxid ' : '';

        return $sQ;
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal If your override does not fully replace the behavior, call parent::addFilter()
     *           (not the deprecated _addFilter()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function addFilter($sQ)
    {
        return $this->_addFilter($sQ);
    }

    /**
     * Removing article from cross-selling list
     */
    public function removeArticleBundle()
    {
        $oDb = DatabaseProvider::getDb();

        $sQ = "update oxarticles set oxarticles.oxbundleid = '' where oxarticles.oxid = :oxid ";
        $oDb->Execute(
            $sQ,
            [':oxid' => Registry::getRequest()->getRequestEscapedParameter('oxid')]
        );
    }

    /**
     * Adding article to cross-selling list
     */
    public function addArticleBundle()
    {
        $oDb = DatabaseProvider::getDb();

        $sQ = 'update oxarticles set oxarticles.oxbundleid = :oxbundleid ' .
              'where oxarticles.oxid  = :oxid ';
        $oDb->Execute(
            $sQ,
            [
                ':oxbundleid' => Registry::getRequest()->getRequestEscapedParameter('oxbundleid'),
                ':oxid' => Registry::getRequest()->getRequestEscapedParameter('oxid'),
            ]
        );
    }
}
