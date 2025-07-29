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

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use stdClass;

/**
 * Admin selectlist list manager.
 */
class AdminListController extends AdminController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = null;

    /**
     * Type of list.
     *
     * @var string
     */
    protected $_sListType = 'oxlist';

    /**
     * List of objects (default null).
     *
     * @var ListModel
     */
    protected $_oList = null;

    /**
     * Position in list of objects (default 0).
     *
     * @var int
     */
    protected $_iCurrListPos = 0;

    /**
     * Size of object list (default 0).
     *
     * @var int
     */
    protected $_iListSize = 0;

    /**
     * Array of SQL query conditions (default null).
     *
     * @var array
     */
    protected $_aWhere = null;

    /**
     * Enable/disable sorting by DESC (SQL) (default false - disable).
     *
     * @var bool
     */
    protected $_blDesc = false;

    /**
     * Set to true to enable multilanguage
     *
     * @var bool
     */
    protected $_blEmployMultilanguage = null;

    /**
     * (default null).
     *
     * @var int
     */
    protected $_iOverPos = null;

    /**
     * Viewable list size
     *
     * @var int
     */
    protected $_iViewListSize = 0;

    /**
     * Viewable default list size (used in list_*.php views)
     *
     * @var int
     */
    protected $_iDefViewListSize = 50;

    /**
     * List sorting array
     *
     * @var array
     */
    protected $_aCurrSorting = null;

    /**
     * Default sorting field
     *
     * @var string
     */
    protected $_sDefSortField = null;

    /**
     * List filter array
     *
     * @var array
     */
    protected $_aListFilter = null;

    /**
     * Returns sorting fields array
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getListSorting()
    {
        if ($this->_aCurrSorting === null) {
            $this->_aCurrSorting = Registry::getRequest()->getRequestEscapedParameter('sort');

            if (!$this->_aCurrSorting && $this->_sDefSortField && ($baseObject = $this->getItemListBaseObject())) {
                $this->_aCurrSorting[$baseObject->getCoreTableName()] = [$this->_sDefSortField => 'asc'];
            }
        }

        return $this->_aCurrSorting;
    }

    /**
     * Returns list filter array
     *
     * @return array
     */
    public function getListFilter()
    {
        if ($this->_aListFilter === null) {
            $request = Registry::getRequest();
            $filter = $request->getRequestEscapedParameter('where');
            $request->checkParamSpecialChars($filter);

            $this->_aListFilter = $filter;
        }

        return $this->_aListFilter;
    }

    /**
     * Viewable list size getter
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, use "getViewListSize" instead
     */
    protected function _getViewListSize() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getViewListSize();
    }

    /**
     * Viewable list size getter
     *
     * @return int
     */
    public function getViewListSize()
    {
        if (!$this->_iViewListSize) {
            $config = Registry::getConfig();
            if ($profile = Registry::getSession()->getVariable('profile')) {
                if (isset($profile[1])) {
                    $config->setConfigParam('iAdminListSize', (int)$profile[1]);
                }
            }

            $this->_iViewListSize = (int)$config->getConfigParam('iAdminListSize');
            if (!$this->_iViewListSize) {
                $this->_iViewListSize = 10;
                $config->setConfigParam('iAdminListSize', $this->_iViewListSize);
            }
        }

        return $this->_iViewListSize;
    }

    /**
     * Viewable list size getter (used in list_*.php views)
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getUserDefListSize" in next major
     */
    protected function _getUserDefListSize() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getUserDefListSize();
    }

    /**
     * Viewable list size getter (used in list_*.php views)
     *
     * @return int
     */
    protected function getUserDefListSize()
    {
        if (!$this->_iViewListSize) {
            if (!($viewListSize = (int)Registry::getRequest()->getRequestEscapedParameter('viewListSize'))) {
                $viewListSize = $this->_iDefViewListSize;
            }
            $this->_iViewListSize = $viewListSize;
        }

        return $this->_iViewListSize;
    }

    /**
     * Executes parent::render(), sets back search keys to view, sets navigation params
     *
     * @return null
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        $return = parent::render();

        // assign our list
        $this->_aViewData['mylist'] = $this->getItemList();

        // set navigation parameters
        $this->setListNavigationParams();

        return $return;
    }

    /**
     * Deletes this entry from the database
     *
     * @return void
     */
    public function deleteEntry()
    {
        $delete = oxNew($this->_sListClass);

        //disabling deletion for derived items
        if ($delete->isDerived()) {
            return;
        }

        $blDelete = $delete->delete($this->getEditObjectId());

        // #A - we must reset object ID
        if ($blDelete && isset($_POST['oxid'])) {
            $_POST['oxid'] = -1;
        }

        $this->resetContentCache();

        $this->init();
    }

    /**
     * Calculates list items count
     *
     * @param string $sql SQL query used co select list items
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcListItemsCount" in next major
     */
    protected function _calcListItemsCount($sql) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->calcListItemsCount($sql);
    }

    /**
     * Calculates list items count
     *
     * @param string $sql SQL query used co select list items
     * @throws DatabaseConnectionException
     */
    protected function calcListItemsCount($sql)
    {
        $stringModifier = Str::getStr();

        // count SQL
        $sql = $stringModifier->preg_replace('/select .* from/i', 'select count(*) from ', $sql);

        // removing order by
        $sql = $stringModifier->preg_replace('/order by .*$/i', '', $sql);

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        // con of list items which fits current search conditions
        $this->_iListSize = DatabaseProvider::getMaster()->getOne($sql);

        // set it into session that other frames know about size of DB
        Registry::getSession()->setVariable('iArtCnt', $this->_iListSize);
    }

    /**
     * Set current list position
     *
     * @param string $page jump page string
     * @deprecated underscore prefix violates PSR12, will be renamed to "setCurrentListPosition" in next major
     */
    protected function _setCurrentListPosition($page = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setCurrentListPosition($page);
    }

    /**
     * Set current list position
     *
     * @param string $page jump page string
     */
    protected function setCurrentListPosition($page = null)
    {
        $adminListSize = $this->getViewListSize();

        $jumpToPage = (int)($page ? $page : (((int)Registry::getRequest()->getRequestEscapedParameter('lstrt')) / $adminListSize));
        $jumpToPage = ($page && $jumpToPage) ? ($jumpToPage - 1) : $jumpToPage;

        $jumpToPage = $jumpToPage * $adminListSize;
        if ($jumpToPage < 1) {
            $jumpToPage = 0;
        } elseif ($jumpToPage >= $this->_iListSize) {
            $jumpToPage = floor($this->_iListSize / $adminListSize - 1) * $adminListSize;
        }

        $this->_iCurrListPos = $this->_iOverPos = (int)$jumpToPage;
    }

    /**
     * Adds order by to SQL query string.
     *
     * @param null $query sql string
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareOrderByQuery" in next major
     */
    protected function _prepareOrderByQuery($query = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->prepareOrderByQuery($query);
    }

    /**
     * Adds order by to SQL query string.
     *
     * @param null $query sql string
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareOrderByQuery($query = null)
    {
        // sorting
        $sortFields = $this->getListSorting();

        if (is_array($sortFields) && count($sortFields)) {
            // only add order by at full sql not for count(*)
            $query .= ' order by ';
            $addSeparator = false;

            $listItem = $this->getItemListBaseObject();
            $languageId = $listItem->isMultilang() ? $listItem->getLanguage() : Registry::getLang()->getBaseLanguage();

            $descending = Registry::getRequest()->getRequestEscapedParameter('adminorder');
            $descending = $descending !== null ? (bool)$descending : $this->_blDesc;

            foreach ($sortFields as $table => $fieldData) {
                $table = $table ? (Registry::get(TableViewNameGenerator::class)->getViewName($table, $languageId) . '.') : '';
                foreach ($fieldData as $column => $sortDirectory) {
                    $field = $table . $column;

                    //add table name to column name if no table name found attached to column name
                    $query .= ((($addSeparator) ? ', ' : '')) . DatabaseProvider::getDb()->quoteIdentifier($field);

                    //V oxActive field search always DESC
                    if ($descending || $column == 'oxactive' || strcasecmp($sortDirectory, 'desc') == 0) {
                        $query .= ' desc ';
                    }

                    $addSeparator = true;
                }
            }
        }

        return $query;
    }

    /**
     * Builds and returns SQL query string.
     *
     * @param object $listObject list main object
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "buildSelectString" in next major
     */
    protected function _buildSelectString($listObject = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->buildSelectString($listObject);
    }

    /**
     * Builds and returns SQL query string.
     *
     * @param object $listObject list main object
     *
     * @return string
     */
    protected function buildSelectString($listObject = null)
    {
        return $listObject !== null ? $listObject->buildSelectString(null) : '';
    }

    /**
     * Prepares SQL where query according SQL condition array and attaches it to SQL end.
     * For each search value if german umlauts exist, adds them
     * and replaced by spec. char to query
     *
     * @param string $fieldValue Filters
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "processFilter" in next major
     */
    protected function _processFilter($fieldValue) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->processFilter($fieldValue);
    }

    /**
     * Prepares SQL where query according SQL condition array and attaches it to SQL end.
     * For each search value if german umlauts exist, adds them
     * and replaced by spec. char to query
     *
     * @param string $fieldValue Filters
     *
     * @return string
     */
    protected function processFilter($fieldValue)
    {
        $stringModifier = Str::getStr();

        //removing % symbols
        $fieldValue = $stringModifier->preg_replace('/^%|%$/', '', trim($fieldValue));

        return $stringModifier->preg_replace("/\s+/", ' ', $fieldValue);
    }

    /**
     * Builds part of SQL query
     *
     * @param string $value filter value
     * @param bool $isSearchValue filter value type, true means surround search key with '%'
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "buildFilter" in next major
     */
    protected function _buildFilter($value, $isSearchValue) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->buildFilter($value, $isSearchValue);
    }

    /**
     * Builds part of SQL query
     *
     * @param string $value filter value
     * @param bool $isSearchValue filter value type, true means surround search key with '%'
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function buildFilter($value, $isSearchValue)
    {
        if ($isSearchValue) {
            //is search string, using LIKE
            $query = ' like ' . DatabaseProvider::getDb()->quote('%' . $value . '%') . ' ';
        } else {
            //not search string, values must be equal
            $query = ' = ' . DatabaseProvider::getDb()->quote($value) . ' ';
        }

        return $query;
    }

    /**
     * Checks if filter contains wildcards like %
     *
     * @param string $fieldValue filter value
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isSearchValue" in next major
     */
    protected function _isSearchValue($fieldValue) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isSearchValue($fieldValue);
    }

    /**
     * Checks if filter contains wildcards like %
     *
     * @param string $fieldValue filter value
     *
     * @return bool
     */
    protected function isSearchValue($fieldValue)
    {
        return (Str::getStr()->preg_match('/^%/', $fieldValue) && Str::getStr()->preg_match('/%$/', $fieldValue));
    }

    /**
     * Prepares SQL where query according SQL condition array and attaches it to SQL end.
     * For each search value if german umlauts exist, adds them
     * and replaced by spec. char to query
     *
     * @param array $whereQuery SQL condition array
     * @param string $fullQuery SQL query string
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareWhereQuery" in next major
     */
    protected function _prepareWhereQuery($whereQuery, $fullQuery) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->prepareWhereQuery($whereQuery, $fullQuery);
    }

    /**
     * Prepares SQL where query according SQL condition array and attaches it to SQL end.
     * For each search value if german umlauts exist, adds them
     * and replaced by spec. char to query
     *
     * @param array $whereQuery SQL condition array
     * @param string $fullQuery SQL query string
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareWhereQuery($whereQuery, $fullQuery)
    {
        if (is_array($whereQuery) && count($whereQuery)) {
            $myUtilsString = Registry::getUtilsString();
            foreach ($whereQuery as $identifierName => $fieldValue) {
                $fieldValue = trim($fieldValue);

                //check if this is search string (contains % sign at beginning and end of string)
                $isSearchValue = $this->isSearchValue($fieldValue);

                //removing % symbols
                $fieldValue = $this->processFilter($fieldValue);

                if (strlen($fieldValue)) {
                    $values = explode(' ', $fieldValue);

                    //for each search field using AND action
                    $queryBoolAction = ' and (';

                    foreach ($values as $value) {
                        // trying to search spec chars in search value
                        // if found, add cleaned search value to search sql
                        $uml = $myUtilsString->prepareStrForSearch($value);
                        if ($uml) {
                            $queryBoolAction .= '(';
                        }

                        $quotedIdentifierName = DatabaseProvider::getDb()->quoteIdentifier($identifierName);
                        $fullQuery .= " {$queryBoolAction} {$quotedIdentifierName} ";

                        //for search in same field for different values using AND
                        $queryBoolAction = ' and ';

                        $fullQuery .= $this->buildFilter($value, $isSearchValue);

                        if ($uml) {
                            $fullQuery .= " or {$quotedIdentifierName} ";

                            $fullQuery .= $this->buildFilter($uml, $isSearchValue);
                            $fullQuery .= ')'; // end of OR section
                        }
                    }

                    // end for AND action
                    $fullQuery .= ' ) ';
                }
            }
        }

        return $fullQuery;
    }

    /**
     * Override this for individual search in admin.
     *
     * @param string $query SQL select to change
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "changeselect" in next major
     */
    protected function _changeselect($query) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->changeselect($query);
    }

    /**
     * Override this for individual search in admin.
     *
     * @param string $query SQL select to change
     *
     * @return string
     */
    protected function changeselect($query)
    {
        return $query;
    }

    /**
     * Builds and returns array of SQL WHERE conditions.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function buildWhere()
    {
        if ($this->_aWhere === null && ($this->getItemList())) {
            $this->_aWhere = [];
            $filter = $this->getListFilter();
            if (is_array($filter)) {
                $listItem = $this->getItemListBaseObject();
                $languageId = $listItem->isMultilang() ? $listItem->getLanguage() : Registry::getLang()->getBaseLanguage();
                $localDateFormat = Registry::getConfig()->getConfigParam('sLocalDateFormat');

                foreach ($filter as $table => $filterData) {
                    foreach ($filterData as $name => $value) {
                        if ($value || '0' === (string)$value) {
                            $field = "{$table}__{$name}";

                            // if no table name attached to field name, add it
                            $name = $table ? Registry::get(TableViewNameGenerator::class)->getViewName($table, $languageId) . ".{$name}" : $name;

                            // #M1260: if field is date
                            if ($localDateFormat && $localDateFormat != 'ISO' && isset($listItem->$field)) {
                                $fieldType = $listItem->{$field}->fldtype;
                                if ('datetime' == $fieldType || 'date' == $fieldType) {
                                    $value = $this->convertToDBDate($value, $fieldType);
                                }
                            }

                            $this->_aWhere[$name] = "%{$value}%";
                        }
                    }
                }
            }
        }

        return $this->_aWhere;
    }

    /**
     * Converts date/datetime values to DB scheme (#M1260)
     *
     * @param string $value     Field value
     * @param string $fieldType Field type
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "convertToDBDate" in next major
     */
    protected function _convertToDBDate($value, $fieldType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->convertToDBDate($value, $fieldType);
    }

    /**
     * Converts date/datetime values to DB scheme (#M1260)
     *
     * @param string $value     Field value
     * @param string $fieldType Field type
     *
     * @return string
     */
    protected function convertToDBDate($value, $fieldType)
    {
        $convertedObject = new Field();
        $convertedObject->setValue($value);
        if ($fieldType == 'datetime') {
            if (strlen($value) == 10 || strlen($value) == 22 || (strlen($value) == 19 && !stripos($value, 'm'))) {
                Registry::getUtilsDate()->convertDBDateTime($convertedObject, true);
            } else {
                if (strlen($value) > 10) {
                    return $this->convertTime($value);
                } else {
                    return $this->convertDate($value);
                }
            }
        } elseif ($fieldType == 'date') {
            if (strlen($value) == 10) {
                Registry::getUtilsDate()->convertDBDate($convertedObject, true);
            } else {
                return $this->convertDate($value);
            }
        }

        return $convertedObject->value;
    }

    /**
     * Converter for date field search. If not full date will be searched.
     *
     * @param string $date searched date
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "convertDate" in next major
     */
    protected function _convertDate($date) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->convertDate($date);
    }

    /**
     * Converter for date field search. If not full date will be searched.
     *
     * @param string $date searched date
     *
     * @return string
     */
    protected function convertDate($date)
    {
        // regexps to validate input
        $datePatterns = [
            "/^([0-9]{2})\.([0-9]{4})/" => 'EUR2', // MM.YYYY
            "/^([0-9]{2})\.([0-9]{2})/" => 'EUR1', // DD.MM
            "/^([0-9]{2})\/([0-9]{4})/" => 'USA2', // MM.YYYY
            "/^([0-9]{2})\/([0-9]{2})/" => 'USA1', // DD.MM
        ];

        // date/time formatting rules
        $dateFormats = [
            'EUR1' => [2, 1],
            'EUR2' => [2, 1],
            'USA1' => [1, 2],
            'USA2' => [2, 1],
        ];

        // looking for date field
        $dateMatches = [];
        $stringModifier = Str::getStr();
        foreach ($datePatterns as $pattern => $type) {
            if ($stringModifier->preg_match($pattern, $date, $dateMatches)) {
                $date = $dateMatches[$dateFormats[$type][0]] . '-' . $dateMatches[$dateFormats[$type][1]];
                break;
            }
        }

        return $date;
    }

    /**
     * Converter for datetime field search. If not full time will be searched.
     *
     * @param string $fullDate searched date
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "convertTime" in next major
     */
    protected function _convertTime($fullDate) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->convertTime($fullDate);
    }

    /**
     * Converter for datetime field search. If not full time will be searched.
     *
     * @param string $fullDate searched date
     *
     * @return string
     */
    protected function convertTime($fullDate)
    {
        $date = substr($fullDate, 0, 10);
        $convertedObject = new Field();
        $convertedObject->setValue($date);
        Registry::getUtilsDate()->convertDBDate($convertedObject, true);
        $stringModifier = Str::getStr();

        // looking for time field
        $time = substr($fullDate, 11);
        if ($stringModifier->preg_match('/([0-9]{2}):([0-9]{2}) ([AP]{1}[M]{1})$/', $time, $timeMatches)) {
            if ($timeMatches[3] == 'PM') {
                $intVal = (int)$timeMatches[1];
                if ($intVal < 13) {
                    $time = ($intVal + 12) . ':' . $timeMatches[2];
                }
            } else {
                $time = $timeMatches[1] . ':' . $timeMatches[2];
            }
        } elseif ($stringModifier->preg_match('/([0-9]{2}) ([AP]{1}[M]{1})$/', $time, $timeMatches)) {
            if ($timeMatches[2] == 'PM') {
                $intVal = (int)$timeMatches[1];
                if ($intVal < 13) {
                    $time = ($intVal + 12);
                }
            } else {
                $time = $timeMatches[1];
            }
        } else {
            $time = str_replace('.', ':', $time);
        }

        return $convertedObject->value . ' ' . $time;
    }

    /**
     * Set parameters needed for list navigation
     * @deprecated underscore prefix violates PSR12, will be renamed to "setListNavigationParams" in next major
     */
    protected function _setListNavigationParams() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setListNavigationParams();
    }

    /**
     * Set parameters needed for list navigation
     */
    protected function setListNavigationParams()
    {
        // list navigation
        $showNavigation = false;
        $adminListSize = $this->getViewListSize();
        if ($this->_iListSize > $adminListSize) {
            // yes, we need to build the navigation object
            $pageNavigation = new stdClass();
            $pageNavigation->pages = round((($this->_iListSize - 1) / $adminListSize) + 0.5, 0);
            $pageNavigation->actpage = ($pageNavigation->actpage > $pageNavigation->pages) ? $pageNavigation->pages : round(
                ($this->_iCurrListPos / $adminListSize) + 0.5,
                0
            );
            $pageNavigation->lastlink = ($pageNavigation->pages - 1) * $adminListSize;
            $pageNavigation->nextlink = null;
            $pageNavigation->backlink = null;

            $position = $this->_iCurrListPos + $adminListSize;
            if ($position < $this->_iListSize) {
                $pageNavigation->nextlink = $this->_iCurrListPos + $adminListSize;
            }

            if (($this->_iCurrListPos - $adminListSize) >= 0) {
                $pageNavigation->backlink = $this->_iCurrListPos - $adminListSize;
            }

            // calculating list start position
            $start = $pageNavigation->actpage - 5;
            $start = ($start <= 0) ? 1 : $start;

            // calculating list end position
            $end = $pageNavigation->actpage + 5;
            $end = ($end < $start + 10) ? $start + 10 : $end;
            $end = ($end > $pageNavigation->pages) ? $pageNavigation->pages : $end;

            // once again adjusting start pos ...
            $start = ($end - 10 > 0) ? $end - 10 : $start;
            $start = ($pageNavigation->pages <= 11) ? 1 : $start;

            // navigation urls
            for ($i = $start; $i <= $end; $i++) {
                $page = new stdclass();
                $page->selected = 0;
                if ($i == $pageNavigation->actpage) {
                    $page->selected = 1;
                }
                $pageNavigation->changePage[$i] = $page;
            }

            $this->_aViewData['pagenavi'] = $pageNavigation;

            if (isset($this->_iOverPos)) {
                $position = $this->_iOverPos;
                $this->_iOverPos = null;
            } else {
                $position = Registry::getRequest()->getRequestEscapedParameter('lstrt');
            }

            if (!$position) {
                $position = 0;
            }

            $this->_aViewData['lstrt'] = $position;
            $this->_aViewData['listsize'] = $this->_iListSize;
            $showNavigation = true;
        }

        // determine not used space in List
        $listSizeToShow = $this->_iListSize - $this->_iCurrListPos;
        $adminListSize = $this->getViewListSize();
        $notUsed = $adminListSize - min($listSizeToShow, $adminListSize);
        $space = $notUsed * 15;

        if (!$showNavigation) {
            $space += 20;
        }

        $this->_aViewData['iListFillsize'] = $space;
    }

    /**
     * Sets-up navigation parameters
     *
     * @param string $sNode active view id
     * @deprecated underscore prefix violates PSR12, will be renamed to "setupNavigation" in next major
     */
    protected function _setupNavigation($sNode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setupNavigation($sNode);
    }

    /**
     * Sets-up navigation parameters
     *
     * @param string $sNode active view id
     */
    protected function setupNavigation($sNode)
    {
        // navigation according to class
        if ($sNode) {
            $adminNavigation = $this->getNavigation();

            $objectId = $this->getEditObjectId();

            if ($objectId == -1) {
                //on first call or when pressed creating new item button, resetting active tab
                $activeTab = $this->_iDefEdit;
            } else {
                // active tab
                $activeTab = Registry::getRequest()->getRequestEscapedParameter('actedit');
                $activeTab = $activeTab ? $activeTab : $this->_iDefEdit;
            }

            // tabs
            $this->_aViewData['editnavi'] = $adminNavigation->getTabs($sNode, $activeTab);

            // active tab
            $this->_aViewData['actlocation'] = $adminNavigation->getActiveTab($sNode, $activeTab);

            // default tab
            $this->_aViewData['default_edit'] = $adminNavigation->getActiveTab($sNode, $this->_iDefEdit);

            // assign active tab number
            $this->_aViewData['actedit'] = $activeTab;
        }
    }

    /**
     * Returns items list
     *
     * @return ListModel
     * @throws DatabaseConnectionException
     */
    public function getItemList()
    {
        if ($this->_oList === null && $this->_sListClass) {
            $this->_oList = oxNew($this->_sListType);
            $this->_oList->clear();
            $this->_oList->init($this->_sListClass);

            $where = $this->buildWhere();

            $listObject = $this->_oList->getBaseObject();

            Registry::getSession()->setVariable('tabelle', $this->_sListClass);
            $this->_aViewData['listTable'] = Registry::get(TableViewNameGenerator::class)->getViewName($listObject->getCoreTableName());
            Registry::getConfig()->setGlobalParameter('ListCoreTable', $listObject->getCoreTableName());

            if ($listObject->isMultilang()) {
                // is the object multilingual?
                /** @var MultiLanguageModel $listObject */
                $listObject->setLanguage(Registry::getLang()->getBaseLanguage());

                if (isset($this->_blEmployMultilanguage)) {
                    $listObject->setEnableMultilang($this->_blEmployMultilanguage);
                }
            }

            $query = $this->buildSelectString($listObject);
            $query = $this->prepareWhereQuery($where, $query);
            $query = $this->prepareOrderByQuery($query);
            $query = $this->changeselect($query);

            // calculates count of list items
            $this->calcListItemsCount($query);

            // setting current list position (page)
            $this->setCurrentListPosition(Registry::getRequest()->getRequestEscapedParameter('jumppage'));

            // setting addition params for list: current list size
            $this->_oList->setSqlLimit($this->_iCurrListPos, $this->getViewListSize());

            $this->_oList->selectString($query);
        }

        return $this->_oList;
    }

    /**
     * Clear items list
     */
    public function clearItemList()
    {
        $this->_oList = null;
    }

    /**
     * Returns item list base object
     *
     * @return Base|null
     * @throws DatabaseConnectionException
     */
    public function getItemListBaseObject()
    {
        $baseObject = null;
        if (($itemsList = $this->getItemList())) {
            $baseObject = $itemsList->getBaseObject();
        }

        return $baseObject;
    }
}
