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

use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterAdminAjaxRequestProcessedEvent;

/**
 * AJAX call processor class
 */
class ListComponentAjax extends Base
{
    /**
     * Possible sort keys
     *
     * @var array
     */
    protected $_aPosDir = ['asc', 'desc'];

    /**
     * Array of DB table columns which are loaded from DB
     *
     * @var array
     */
    protected $_aColumns = [];

    /**
     * Default limit of DB entries to load from DB
     *
     * @var int
     */
    protected $_iSqlLimit = 2500;

    /**
     * Ajax container name
     *
     * @var string
     */
    protected $_sContainer = null;

    /**
     * If true extended column selection will be build
     * (currently checks if variants must be shown in lists and column name is "oxtitle")
     *
     * @var bool
     */
    protected $_blAllowExtColumns = false;

    /**
     * Gets columns array.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->_aColumns;
    }

    /**
     * Sets columns array.
     *
     * @param array $aColumns columns array
     */
    public function setColumns($aColumns)
    {
        $this->_aColumns = $aColumns;
    }

    /**
     * Required data fields are returned by indexes/position in _aColumns array. This method
     * translates "table_name.col_name" into index definition and fetches request data according
     * to it. This is useful while using AJAX across versions.
     *
     * @param string $sId "table_name.col_name"
     *
     * @return array|null
     * @deprecated Transitional during #107. Modules SHOULD override _getActionIds()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getActionIds() to the canonical override
      *             target and retires _getActionIds(); until then, _getActionIds() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getActionIds($sId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aColumns = $this->_getColNames();
        foreach ($aColumns as $iPos => $aCol) {
            if (isset($aCol[4]) && $aCol[4] == 1 && $sId == $aCol[1] . '.' . $aCol[0]) {
                return Registry::getRequest()->getRequestEscapedParameter('_' . $iPos);
            }
        }
    }

    /**
     * Required data fields are returned by indexes/position in _aColumns array. This method
     * translates "table_name.col_name" into index definition and fetches request data according
     * to it. This is useful while using AJAX across versions.
     *
     * @param string $sId "table_name.col_name"
     *
     * @return array|void
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getActionIds(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getActionIds() the canonical override target.
     */
    protected function getActionIds($sId)
    {
        return $this->_getActionIds($sId);
    }

    /**
     * AJAX container name setter
     *
     * @param string $sName name of container
     */
    public function setName($sName)
    {
        $this->_sContainer = $sName;
    }

    /**
     * Empty function, developer should override this method according requirements
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getQuery()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getQuery() to the canonical override
      *             target and retires _getQuery(); until then, _getQuery() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '';
    }

    /**
     * Empty function, developer should override this method according requirements
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getQuery(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getQuery() the canonical override target.
     */
    protected function getQuery()
    {
        return $this->_getQuery();
    }

    /**
     * Return fully formatted query for data loading
     *
     * @param string $sQ part of initial query
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getDataQuery()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getDataQuery() to the canonical override
      *             target and retires _getDataQuery(); until then, _getDataQuery() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getDataQuery($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'select ' . $this->_getQueryCols() . $sQ;
    }

    /**
     * Return fully formatted query for data loading
     *
     * @param string $sQ part of initial query
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getDataQuery(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getDataQuery() the canonical override target.
     */
    protected function getDataQuery($sQ)
    {
        return $this->_getDataQuery($sQ);
    }

    /**
     * Return fully formatted query for data records count
     *
     * @param string $sQ part of initial query
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getCountQuery()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getCountQuery() to the canonical override
      *             target and retires _getCountQuery(); until then, _getCountQuery() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getCountQuery($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'select count( * ) ' . $sQ;
    }

    /**
     * Return fully formatted query for data records count
     *
     * @param string $sQ part of initial query
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getCountQuery(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getCountQuery() the canonical override target.
     */
    protected function getCountQuery($sQ)
    {
        return $this->_getCountQuery($sQ);
    }

    /**
     * AJAX call processor function
     *
     * @param null $function name of action to execute (optional)
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function processRequest($function = null)
    {
        if ($function) {
            $this->$function();
            $this->dispatchEvent(new AfterAdminAjaxRequestProcessedEvent());
        } else {
            // Restored to the baseline form from ebe86dc (initial commit
            // line 154: `$sQAdd = $this->_getQuery();`). #107 (commit
            // 45e71ae) had moved the body INTO _getQuery() and made
            // getQuery() a delegate-down — that inversion broke subclass
            // overrides and test mocks of getQuery(). Per the BC-shim
            // contract, _getQuery() is the deprecated wrapper that
            // delegates UP to getQuery(); internal call sites must use
            // _getQuery() so module overrides of either form continue to
            // win. Broader audit of similar #107 inversions tracked in
            // the reopened o3-shop/o3-shop#107.
            $sQAdd = $this->_getQuery();

            // formatting SQL queries
            $sQ = $this->_getDataQuery($sQAdd);
            $sCountQ = $this->_getCountQuery($sQAdd);

            $this->_outputResponse($this->getData($sCountQ, $sQ));
        }
    }

    /**
     * Returns column id to sort
     *
     * @return int
     * @deprecated Transitional during #107. Modules SHOULD override _getSortCol()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getSortCol() to the canonical override
      *             target and retires _getSortCol(); until then, _getSortCol() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getSortCol() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aVisibleNames = $this->_getVisibleColNames();
        $iCol = Registry::getRequest()->getRequestEscapedParameter('sort');
        $iCol = $iCol ? ((int) str_replace('_', '', $iCol)) : 0;
        $iCol = (!isset($aVisibleNames[$iCol])) ? 0 : $iCol;

        return $iCol;
    }

    /**
     * Returns column id to sort
     *
     * @return int
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getSortCol(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getSortCol() the canonical override target.
     */
    protected function getSortCol()
    {
        return $this->_getSortCol();
    }

    /**
     * Returns array of container DB cols which must be loaded. If id is not
     * passed - all possible containers cols will be returned
     *
     * @param string $sId container id (optional)
     *
     * @return array
     * @deprecated Transitional during #107. Modules SHOULD override _getColNames()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getColNames() to the canonical override
      *             target and retires _getColNames(); until then, _getColNames() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getColNames($sId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($sId === null) {
            $sId = Registry::getRequest()->getRequestEscapedParameter('cmpid');
        }

        if ($sId && isset($this->_aColumns[$sId])) {
            return $this->_aColumns[$sId];
        }

        return $this->_aColumns;
    }

    /**
     * Returns array of container DB cols which must be loaded. If id is not
     * passed - all possible containers cols will be returned
     *
     * @param string $sId container id (optional)
     *
     * @return array
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getColNames(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getColNames() the canonical override target.
     */
    protected function getColNames($sId = null)
    {
        return $this->_getColNames($sId);
    }

    /**
     * Returns array of identifiers which are used as identifiers for specific actions
     * in AJAX and further in this processor class
     *
     * @return array
     * @deprecated Transitional during #107. Modules SHOULD override _getIdentColNames()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getIdentColNames() to the canonical override
      *             target and retires _getIdentColNames(); until then, _getIdentColNames() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getIdentColNames() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aColNames = $this->_getColNames();
        $aCols = [];
        foreach ($aColNames as $iKey => $aCol) {
            // ident ?
            if ($aCol[4]) {
                $aCols[$iKey] = $aCol;
            }
        }

        return $aCols;
    }

    /**
     * Returns array of identifiers which are used as identifiers for specific actions
     * in AJAX and further in this processor class
     *
     * @return array
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getIdentColNames(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getIdentColNames() the canonical override target.
     */
    protected function getIdentColNames()
    {
        return $this->_getIdentColNames();
    }

    /**
     * Returns array of col names which are requested by AJAX call and will be fetched from DB
     *
     * @return array
     * @deprecated Transitional during #107. Modules SHOULD override _getVisibleColNames()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getVisibleColNames() to the canonical override
      *             target and retires _getVisibleColNames(); until then, _getVisibleColNames() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getVisibleColNames() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aColNames = $this->_getColNames();
        $aUserCols = Registry::getRequest()->getRequestEscapedParameter('aCols');
        $aVisibleCols = [];

        // user defined some cols to load ?
        if (is_array($aUserCols)) {
            foreach ($aUserCols as $sCol) {
                $iCol = (int) str_replace('_', '', $sCol);
                if (isset($aColNames[$iCol]) && !$aColNames[$iCol][4]) {
                    $aVisibleCols[$iCol] = $aColNames[$iCol];
                }
            }
        }

        // no user defined valid cols ? setting defaults ..
        if (!count($aVisibleCols)) {
            foreach ($aColNames as $sName => $aCol) {
                // visible ?
                if ($aCol[1] && !$aCol[4]) {
                    $aVisibleCols[$sName] = $aCol;
                }
            }
        }

        return $aVisibleCols;
    }

    /**
     * Returns array of col names which are requested by AJAX call and will be fetched from DB
     *
     * @return array
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getVisibleColNames(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getVisibleColNames() the canonical override target.
     */
    protected function getVisibleColNames()
    {
        return $this->_getVisibleColNames();
    }

    /**
     * Formats and returns chunk of SQL query string with definition of
     * fields to load from DB
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getQueryCols()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getQueryCols() to the canonical override
      *             target and retires _getQueryCols(); until then, _getQueryCols() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getQueryCols() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sQ = $this->_buildColsQuery($this->_getVisibleColNames(), false) . ', ';
        $sQ .= $this->_buildColsQuery($this->_getIdentColNames());

        return " $sQ ";
    }

    /**
     * Formats and returns chunk of SQL query string with definition of
     * fields to load from DB
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getQueryCols(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getQueryCols() the canonical override target.
     */
    protected function getQueryCols()
    {
        return $this->_getQueryCols();
    }

    /**
     * Builds column selection query
     *
     * @param array $aIdentCols  columns
     * @param bool  $blIdentCols if true, means ident columns part is build
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _buildColsQuery()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes buildColsQuery() to the canonical override
      *             target and retires _buildColsQuery(); until then, _buildColsQuery() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _buildColsQuery($aIdentCols, $blIdentCols = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sQ = '';
        foreach ($aIdentCols as $iCnt => $aCol) {
            if ($sQ) {
                $sQ .= ', ';
            }

            $sViewTable = $this->getViewName($aCol[1]);
            if (!$blIdentCols && $this->_isExtendedColumn($aCol[0])) {
                $sQ .= $this->_getExtendedColQuery($sViewTable, $aCol[0], $iCnt);
            } else {
                $sQ .= $sViewTable . '.' . $aCol[0] . ' as _' . $iCnt;
            }
        }

        return $sQ;
    }

    /**
     * Builds column selection query
     *
     * @param array $aIdentCols  columns
     * @param bool  $blIdentCols if true, means ident columns part is build
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _buildColsQuery(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make buildColsQuery() the canonical override target.
     */
    protected function buildColsQuery($aIdentCols, $blIdentCols = true)
    {
        return $this->_buildColsQuery($aIdentCols, $blIdentCols);
    }

    /**
     * Checks if current column is extended
     * (currently checks if variants must be shown in lists and column name is "oxtitle")
     *
     * @param string $sColumn column name
     *
     * @return bool
     * @deprecated Transitional during #107. Modules SHOULD override _isExtendedColumn()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes isExtendedColumn() to the canonical override
      *             target and retires _isExtendedColumn(); until then, _isExtendedColumn() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _isExtendedColumn($sColumn) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blVariantsSelectionParameter = Registry::getConfig()->getConfigParam('blVariantsSelection');

        return $this->_blAllowExtColumns && $blVariantsSelectionParameter && $sColumn == 'oxtitle';
    }

    /**
     * Checks if current column is extended
     * (currently checks if variants must be shown in lists and column name is "oxtitle")
     *
     * @param string $sColumn column name
     *
     * @return bool
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _isExtendedColumn(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make isExtendedColumn() the canonical override target.
     */
    protected function isExtendedColumn($sColumn)
    {
        return $this->_isExtendedColumn($sColumn);
    }

    /**
     * Returns extended query part for given view/column combination
     * (if variants must be shown in lists and column name is "oxtitle")
     *
     * @param string $sViewTable view name
     * @param string $sColumn    column name
     * @param int    $iCnt       column count
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getExtendedColQuery()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getExtendedColQuery() to the canonical override
      *             target and retires _getExtendedColQuery(); until then, _getExtendedColQuery() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getExtendedColQuery($sViewTable, $sColumn, $iCnt) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // multilanguage
        $sVarSelect = "$sViewTable.oxvarselect";

        return " IF( {$sViewTable}.{$sColumn} != '', {$sViewTable}.{$sColumn}, CONCAT((select oxart.{$sColumn} " .
                "from {$sViewTable} as oxart " .
                "where oxart.oxid = {$sViewTable}.oxparentid),', ',{$sVarSelect})) as _{$iCnt}";
    }

    /**
     * Returns extended query part for given view/column combination
     * (if variants must be shown in lists and column name is "oxtitle")
     *
     * @param string $sViewTable view name
     * @param string $sColumn    column name
     * @param int    $iCnt       column count
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getExtendedColQuery(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getExtendedColQuery() the canonical override target.
     */
    protected function getExtendedColQuery($sViewTable, $sColumn, $iCnt)
    {
        return $this->_getExtendedColQuery($sViewTable, $sColumn, $iCnt);
    }

    /**
     * Formats and returns part of SQL query for sorting
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getSorting()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getSorting() to the canonical override
      *             target and retires _getSorting(); until then, _getSorting() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getSorting() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return ' order by _' . $this->_getSortCol() . ' ' . $this->getSortDir() . ' ';
    }

    /**
     * Formats and returns part of SQL query for sorting
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getSorting(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getSorting() the canonical override target.
     */
    protected function getSorting()
    {
        return $this->_getSorting();
    }

    /**
     * Returns part of SQL query for limiting number of entries from DB
     *
     * @param int $iStart start position
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getLimit()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getLimit() to the canonical override
      *             target and retires _getLimit(); until then, _getLimit() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getLimit($iStart) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iLimit = (int) Registry::getRequest()->getRequestEscapedParameter('results');
        $iLimit = $iLimit ? $iLimit : $this->_iSqlLimit;

        return " limit $iStart, $iLimit ";
    }

    /**
     * Returns part of SQL query for limiting number of entries from DB
     *
     * @param int $iStart start position
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getLimit(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getLimit() the canonical override target.
     */
    protected function getLimit($iStart)
    {
        return $this->_getLimit($iStart);
    }

    /**
     * Returns part of SQL query for filtering DB data
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated Transitional during #107. Modules SHOULD override _getFilter()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getFilter() to the canonical override
      *             target and retires _getFilter(); until then, _getFilter() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getFilter() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sQ = '';
        $aFilter = Registry::getRequest()->getRequestEscapedParameter('aFilter');
        if (is_array($aFilter) && count($aFilter)) {
            $aCols = $this->_getVisibleColNames();
            $oDb = DatabaseProvider::getDb();
            $oStr = Str::getStr();

            foreach ($aFilter as $sCol => $sValue) {
                // skipping empty filters
                if ($sValue === '') {
                    continue;
                }

                $iCol = (int) str_replace('_', '', $sCol);
                if (isset($aCols[$iCol])) {
                    if ($sQ) {
                        $sQ .= ' and ';
                    }

                    // escaping special characters
                    $sValue = str_replace(['%', '_'], ['\%', '\_'], $sValue);

                    // possibility to search in the middle ...
                    $sValue = $oStr->preg_replace('/^\*/', '%', $sValue);

                    $sQ .= $this->getViewName($aCols[$iCol][1]) . '.' . $aCols[$iCol][0];
                    $sQ .= ' like ' . $oDb->Quote('%' . $sValue . '%') . ' ';
                }
            }
        }

        return $sQ;
    }

    /**
     * Returns part of SQL query for filtering DB data
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getFilter(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getFilter() the canonical override target.
     */
    protected function getFilter()
    {
        return $this->_getFilter();
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated Transitional during #107. Modules SHOULD override _addFilter()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes addFilter() to the canonical override
      *             target and retires _addFilter(); until then, _addFilter() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _addFilter($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($sQ && ($sFilter = $this->_getFilter())) {
            $sQ .= ((stristr($sQ, 'where') === false) ? 'where' : ' and ') . $sFilter;
        }

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
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _addFilter(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make addFilter() the canonical override target.
     */
    protected function addFilter($sQ)
    {
        return $this->_addFilter($sQ);
    }

    /**
     * Returns DB records as plain indexed array
     *
     * @param string $sQ SQL query
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated Transitional during #107. Modules SHOULD override _getAll()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getAll() to the canonical override
      *             target and retires _getAll(); until then, _getAll() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getAll($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aReturn = [];
        $rs = DatabaseProvider::getDb()->select($sQ);
        if ($rs && $rs->count() > 0) {
            while (!$rs->EOF) {
                $aReturn[] = $rs->fields[0];
                $rs->fetchRow();
            }
        }

        return $aReturn;
    }

    /**
     * Returns DB records as plain indexed array
     *
     * @param string $sQ SQL query
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getAll(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getAll() the canonical override target.
     */
    protected function getAll($sQ)
    {
        return $this->_getAll($sQ);
    }

    /**
     * Checks user input and returns SQL sorting direction key
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSortDir" in next major
     */
    protected function _getSortDir() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getSortDir();
    }

    /**
     * Checks user input and returns SQL sorting direction key
     *
     * @return string
     */
    protected function getSortDir()
    {
        $sDir = Registry::getRequest()->getRequestEscapedParameter('dir');
        if (!in_array($sDir, $this->_aPosDir)) {
            $sDir = $this->_aPosDir[0];
        }

        return $sDir;
    }

    /**
     * Returns position from where data must be loaded
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getStartIndex" in next major
     */
    protected function _getStartIndex() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getStartIndex();
    }

    /**
     * Returns position from where data must be loaded
     *
     * @return int
     */
    protected function getStartIndex()
    {
        return (int) Registry::getRequest()->getRequestEscapedParameter('startIndex');
    }

    /**
     * Returns amount of records which can be found according to passed SQL query
     *
     * @param string $sQ SQL query
     *
     * @return int
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getTotalCount" in next major
     */
    protected function _getTotalCount($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getTotalCount($sQ);
    }

    /**
     * Returns amount of records which can be found according to passed SQL query
     *
     * @param string $sQ SQL query
     *
     * @return int
     * @throws DatabaseConnectionException
     */
    protected function getTotalCount($sQ)
    {
        // TODO: implement caching here

        // we can cache total count ...

        // $sCountCacheKey = md5( $sQ );

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (int) DatabaseProvider::getMaster()->getOne($sQ);
    }

    /**
     * Returns array with DB records
     *
     * @param string $sQ SQL query
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDataFields" in next major
     */
    protected function _getDataFields($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return DatabaseProvider::getMaster(DatabaseProvider::FETCH_MODE_ASSOC)->getAll($sQ, false);
    }

    /**
     * Returns array with DB records
     *
     * @param string $sQ SQL query
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getDataFields(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getDataFields() the canonical override target.
     */
    protected function getDataFields($sQ)
    {
        return $this->_getDataFields($sQ);
    }

    /**
     * Outputs JSON encoded data
     *
     * @param array $aData data to output
     * @deprecated underscore prefix violates PSR12, will be renamed to "outputResponse" in next major
     */
    protected function _outputResponse($aData) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->outputResponse($aData);
    }

    /**
     * Outputs JSON encoded data
     *
     * @param array $aData data to output
     */
    protected function outputResponse($aData)
    {
        $this->_output(json_encode($aData));
    }

    /**
     * Echoes given string
     *
     * @param string $sOut string to echo
     * @deprecated underscore prefix violates PSR12, will be renamed to "output" in next major
     */
    protected function _output($sOut) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->output($sOut);
    }

    /**
     * Echoes given string
     *
     * @param string $sOut string to echo
     */
    protected function output($sOut)
    {
        echo $sOut;
    }

    /**
     * Return the view name of the given table if a view exists, otherwise the table name itself
     *
     * @param string $sTable table name
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getViewName" in next major
     */
    protected function _getViewName($sTable) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getViewName($sTable);
    }

    /**
     * Return the view name of the given table if a view exists, otherwise the table name itself
     *
     * @param string $sTable table name
     *
     * @return string
     */
    protected function getViewName($sTable)
    {
        return Registry::get(TableViewNameGenerator::class)->getViewName($sTable, Registry::getRequest()->getRequestEscapedParameter('editlanguage'));
    }

    /**
     * Formats data array which later will be processed by _outputResponse method
     *
     * @param string $sCountQ count query
     * @param string $sQ data load query
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getData" in next major
     */
    protected function _getData($sCountQ, $sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getData($sCountQ, $sQ);
    }

    /**
     * Formats data array which later will be processed by _outputResponse method
     *
     * @param string $sCountQ count query
     * @param string $sQ data load query
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function getData($sCountQ, $sQ)
    {
        $sQ = $this->_addFilter($sQ);
        $sCountQ = $this->_addFilter($sCountQ);

        $aResponse['startIndex'] = $iStart = $this->getStartIndex();
        $aResponse['sort'] = '_' . $this->_getSortCol();
        $aResponse['dir'] = $this->getSortDir();

        $iDebug = Registry::getConfig()->getConfigParam('iDebug');
        if ($iDebug) {
            $aResponse['countsql'] = $sCountQ;
        }

        $aResponse['records'] = [];

        // skip further execution if no records were found ...
        if (($iTotal = $this->getTotalCount($sCountQ))) {
            $sQ .= $this->_getSorting();
            $sQ .= $this->_getLimit($iStart);

            if ($iDebug) {
                $aResponse['datasql'] = $sQ;
            }

            $aResponse['records'] = $this->_getDataFields($sQ);
        }

        $aResponse['totalRecords'] = $iTotal;

        return $aResponse;
    }

    /**
     * Marks article seo url as expired
     *
     * @param array $aArtIds article id's
     * @param array $aCatIds ids if categories, which must be removed from oxseo
     *
     * @return void
     */
    public function resetArtSeoUrl($aArtIds, $aCatIds = null)
    {
        if (empty($aArtIds)) {
            return;
        }

        if (!is_array($aArtIds)) {
            $aArtIds = [$aArtIds];
        }

        $sShopId = Registry::getConfig()->getShopId();
        foreach ($aArtIds as $sArtId) {
            /** @var SeoEncoder $oSeoEncoder */
            Registry::getSeoEncoder()->markAsExpired($sArtId, $sShopId, 1, null, "oxtype='oxarticle'");
        }
    }

    /**
     * Reset output cache
     */
    public function resetContentCache()
    {
        $blDeleteCacheOnLogout = Registry::getConfig()->getConfigParam('blClearCacheOnLogout');

        if (!$blDeleteCacheOnLogout) {
            $this->_resetCaches();

            Registry::getUtils()->oxResetFileCache();
        }
    }

    /**
     * Resets counters values from cache. Resets price category articles, category articles,
     * vendor articles, manufacturer articles count.
     *
     * @param string $sCounterType counter type
     * @param string $sValue       reset value
     */
    public function resetCounter($sCounterType, $sValue = null)
    {
        $blDeleteCacheOnLogout = Registry::getConfig()->getConfigParam('blClearCacheOnLogout');

        if (!$blDeleteCacheOnLogout) {
            $myUtilsCount = Registry::getUtilsCount();
            switch ($sCounterType) {
                case 'priceCatArticle':
                    $myUtilsCount->resetPriceCatArticleCount($sValue);
                    break;
                case 'catArticle':
                    $myUtilsCount->resetCatArticleCount($sValue);
                    break;
                case 'vendorArticle':
                    $myUtilsCount->resetVendorArticleCount($sValue);
                    break;
                case 'manufacturerArticle':
                    $myUtilsCount->resetManufacturerArticleCount($sValue);
                    break;
            }

            $this->_resetContentCache();
        }
    }

    /**
     * Resets content cache.
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetContentCache" in next major
     */
    protected function _resetContentCache() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Resets output caches
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetCaches" in next major
     */
    protected function _resetCaches() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Resets output caches
     */
    protected function resetCaches()
    {
    }
}
