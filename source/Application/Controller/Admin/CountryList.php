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

/**
 * Admin selectlist list manager.
 */
class CountryList extends AdminListController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxcountry';

    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_sDefSortField = 'oxactive';

    /**
     * Default second SQL sorting parameter.
     *
     * @var string
     */
    protected $sSecondDefSortField = 'oxtitle';

    /**
     * Enable/disable sorting by DESC (SQL) (default false - disable).
     *
     * @var bool
     */
    protected $_blDesc = false;

    /**
     * Executes parent method parent::render() and returns name of template
     * file "selectlist_list.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        return 'country_list.tpl';
    }

    /**
     * Returns sorting fields array. We extend this method for getting a second order by, which will give us not the
     * undefined order behind the "active" countries.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getListSorting()
    {
        $aListSorting = parent::getListSorting();

        if (array_keys($aListSorting['oxcountry']) === ['oxactive']) {
            $aListSorting['oxcountry'][$this->_getSecondSortFieldName()] = 'asc';
        }

        return $aListSorting;
    }

    /**
     * Getter for the second sort field name (for getting the expected order out of the database).
     *
     * @return string The name of the field we want to be the second order by argument.
     * @deprecated Transitional during #107. Modules SHOULD override _getSecondSortFieldName()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getSecondSortFieldName() to the canonical override
      *             target and retires _getSecondSortFieldName(); until then, _getSecondSortFieldName() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getSecondSortFieldName() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->sSecondDefSortField;
    }

    /**
     * Getter for the second sort field name (for getting the expected order out of the database).
     *
     * @return string The name of the field we want to be the second order by argument.
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getSecondSortFieldName(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getSecondSortFieldName() the canonical override target.
     */
    protected function getSecondSortFieldName()
    {
        return $this->_getSecondSortFieldName();
    }
}
