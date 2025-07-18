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
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Admin actions' manager.
 * Sets list template, list object class ('oxactions') and default sorting
 * field ('oxactions.oxtitle').
 * Admin Menu: Manage Products -> Actions.
 */
class ActionsList extends AdminListController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'actions_list.tpl';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxactions';

    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_sDefSortField = 'oxtitle';

    /**
     * Calls parent::render() and returns name of template to render
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        // passing display type back to view
        $this->_aViewData["displaytype"] = Registry::getRequest()->getRequestEscapedParameter('displaytype');

        return $this->_sThisTemplate;
    }

    /**
     * Adds active promotion check
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
     * Adds active promotion check
     *
     * @param array $whereQuery SQL condition array
     * @param string $fullQuery SQL query string
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareWhereQuery($whereQuery, $fullQuery)
    {
        $sQ = parent::prepareWhereQuery($whereQuery, $fullQuery);
        $sDisplayType = (int) Registry::getRequest()->getRequestEscapedParameter('displaytype');
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName("oxactions");

        // searching for empty oxfolder fields
        if ($sDisplayType) {
            $sNow = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());

            switch ($sDisplayType) {
                case 1: // active
                    $sQ .= " and {$sTable}.oxactivefrom < '{$sNow}' and {$sTable}.oxactiveto > '{$sNow}' ";
                    break;
                case 2: // upcoming
                    $sQ .= " and {$sTable}.oxactivefrom > '{$sNow}' ";
                    break;
                case 3: // expired
                    $sQ .= " and {$sTable}.oxactiveto < '{$sNow}' and {$sTable}.oxactiveto != '0000-00-00 00:00:00' ";
                    break;
            }
        }

        return $sQ;
    }
}
