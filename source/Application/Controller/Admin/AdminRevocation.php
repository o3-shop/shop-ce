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

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;

/**
 * §356a BGB electronic revocation feature — admin frameset wrapper.
 *
 * Renders `admin_revocation.tpl` which delegates to the canonical
 * `include/frameset.tpl`, splitting the work area into:
 *   - top (40%):  list view  (cl=revocation_list)
 *   - bottom:     detail view (cl=revocation_main)
 *
 * The wrapper controller is required by the OXID admin master/detail
 * navigation contract: clicking a row in the list calls
 * `top.oxid.admin.editThis(oxid)` which expects an `edit` frame to load
 * the detail view into. Without this wrapper, `cl=revocation_list`
 * renders the list alone and the row click has nowhere to land.
 *
 * Sibling pattern: AdminNews, AdminPriceAlarm, AdminNewsletter.
 */
class AdminRevocation extends AdminController
{
    /**
     * Default active tab — 0-indexed into the SUBMENU's <TAB> list in
     * menu.xml. We have a single TAB (`tbclrevocation_main`), so 0 picks
     * it. Setting this to 1 (as AdminPriceAlarm does for its 2-tab setup)
     * would cause NavigationTree::getEditUrl to call $nodeList->item(1) on
     * a 1-element list, return null, leave $editurl empty in the frameset
     * and recursively load admin_revocation in the edit frame.
     *
     * @var int
     */
    protected $_iDefEdit = 0;

    /** @var string */
    protected $_sThisTemplate = 'admin_revocation.tpl';
}
