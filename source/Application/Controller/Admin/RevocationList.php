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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;

/**
 * §356a BGB electronic revocation feature — admin list view.
 *
 * Renders the list of submissions stored in `o3revocation`. Each row
 * links to {@see RevocationMain} for the detail view + resend / delete
 * actions.
 *
 * Surfaced under "Customer Info → Revocations" via menu.xml.
 */
class RevocationList extends AdminListController
{
    /** @var string */
    protected $_sListClass = 'oxidesales\\eshopcommunity\\application\\model\\o3revocation';

    /** @var string */
    protected $_sListType = 'oxlist';

    /** @var string */
    protected $_sThisTemplate = 'revocation_list.tpl';
}
