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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Model\CategoryList;

/**
 * Class for updating category tree structure in DB.
 */
class CategoryUpdate extends AdminController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'category_update.tpl';

    /**
     * Category list object
     *
     * @var CategoryList
     */
    protected $_oCatList = null;

    /**
     * Returns category list object
     *
     * @return CategoryList
     * @throws Exception
     * @deprecated Use getCategoryList() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getCategoryList().
     */
    protected function _getCategoryList() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_oCatList == null) {
            $this->_oCatList = oxNew(CategoryList::class);
            $this->_oCatList->updateCategoryTree(false);
        }

        return $this->_oCatList;
    }

    /**
     * Returns category list object
     *
     * @return CategoryList
     * @throws Exception
     *
     * @internal If your override does not fully replace the behavior, call parent::getCategoryList()
     *           (not the deprecated _getCategoryList()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getCategoryList()
    {
        return $this->_getCategoryList();
    }

    /**
     * Returns category list object
     *
     * @return array
     * @throws Exception
     */
    public function getCatListUpdateInfo()
    {
        return $this->getCategoryList()->getUpdateInfo();
    }
}
