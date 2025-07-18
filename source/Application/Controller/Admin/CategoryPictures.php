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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;

/**
 * Admin article categories thumbnail manager.
 * Category thumbnail manager (Previews assigned pictures).
 * Admin Menu: Manage Products -> Categories -> Thumbnail.
 */
class CategoryPictures extends AdminDetailsController
{
    /**
     * Loads category object, passes it to Smarty engine and returns name
     * of template file "category_pictures.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        $this->_aViewData['edit'] = $oCategory = oxNew(Category::class);

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != '-1') {
            // load object
            $oCategory->load($soxId);
        }

        return "category_pictures.tpl";
    }
}
