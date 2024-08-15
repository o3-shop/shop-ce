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
use OxidEsales\Eshop\Application\Model\VoucherSerie;

/**
 * Admin voucherserie list manager.
 * Collects voucherserie base information (serie no., discount, valid from, etc.),
 * there is ability to filter them by discount, serie no. or delete them.
 * Admin Menu: Shop Settings -> Vouchers.
 */
class VoucherSerieList extends AdminListController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxvoucherserie';

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'voucherserie_list.tpl';

    /**
     * Deletes selected Voucherserie.
     */
    public function deleteEntry()
    {
        // first we remove vouchers
        $oVoucherSerie = oxNew(VoucherSerie::class);
        $oVoucherSerie->load($this->getEditObjectId());
        $oVoucherSerie->deleteVoucherList();

        parent::deleteEntry();
    }
}
