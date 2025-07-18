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
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin user list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: User Administration -> Users.
 */
class UserList extends AdminListController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxuser';

    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_sDefSortField = "oxusername";

    /**
     * Type of list.
     *
     * @var string
     */
    protected $_sListType = 'oxuserlist';

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'user_list.tpl';

    /**
     * Executes parent::render(), sets blacklist and prevent delete flag
     *
     * @return null
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        foreach ($this->getItemList() as $itemId => $user) {
            /** @var User $user */
            if ($user->inGroup("oxidblacklist") || $user->inGroup("oxidblocked")) {
                $user->blacklist = "1";
            }
            $user->blPreventDelete = false;
            if (!$this->_allowAdminEdit($itemId)) {
                $user->blPreventDelete = true;
            }
        }

        return parent::render();
    }

    /**
     * Admin user is allowed to be deleted only by mall admin
     *
     * @return null|void
     */
    public function deleteEntry()
    {
        if ($this->_allowAdminEdit($this->getEditObjectId())) {
            $this->_oList = null;

            return parent::deleteEntry();
        }
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
     * @deprecated will be renamed to "prepareWhereQuery" in next major
     */
    public function _prepareWhereQuery($whereQuery, $fullQuery) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $nameWhere = null;
        if (isset($whereQuery['oxuser.oxlname']) && ($name = $whereQuery['oxuser.oxlname'])) {
            // check if this is search string (contains % sign at beginning and end of string)
            $isSearchValue = $this->_isSearchValue($name);
            $name = $this->_processFilter($name);
            $nameWhere['oxuser.oxfname'] = $nameWhere['oxuser.oxlname'] = $name;

            unset($whereQuery['oxuser.oxlname']);
        }
        $query = parent::_prepareWhereQuery($whereQuery, $fullQuery);

        if ($nameWhere) {
            $values = explode(' ', $name);
            $query .= ' and (';
            $queryBoolAction = '';
            $utilsString = Registry::getUtilsString();

            foreach ($nameWhere as $fieldName => $fieldValue) {
                //for each search field using AND action
                foreach ($values as $value) {
                    $query .= " {$queryBoolAction} {$fieldName} ";

                    //for search in same field for different values using AND
                    $queryBoolAction = ' or ';

                    $query .= $this->_buildFilter($value, $isSearchValue);

                    // trying to search spec chars in search value
                    // if found, add cleaned search value to search sql
                    $uml = $utilsString->prepareStrForSearch($value);
                    if ($uml) {
                        $query .= " or {$fieldName} ";
                        $query .= $this->_buildFilter($uml, $isSearchValue);
                    }
                }
            }

            // end for AND action
            $query .= ' ) ';
        }

        return $query;
    }
}
