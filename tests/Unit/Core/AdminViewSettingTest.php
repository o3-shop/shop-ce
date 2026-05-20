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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElement;
use OxidEsales\EshopCommunity\Core\AdminViewSetting;

class AdminViewSettingTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Registry::getSession()->deleteVariable(AdminViewSetting::ALL_MENU_ITEMS);
    }

    public function testCanShowAllMenuItemsIsFalseByDefault(): void
    {
        $this->assertFalse((new AdminViewSetting())->canShowAllMenuItems());
    }

    public function testToggleShowAllMenuItemsOnAndOff(): void
    {
        $setting = new AdminViewSetting();
        $this->assertFalse($setting->canShowAllMenuItems());

        $setting->toggleShowAllMenuItems();
        $this->assertTrue($setting->canShowAllMenuItems());

        $setting->toggleShowAllMenuItems();
        $this->assertFalse($setting->canShowAllMenuItems());
    }

    public function testFilterListByTypesKeepsOnlyMatchingValues(): void
    {
        $setting = new AdminViewSetting();
        $list = [
            'menu_a' => RightsRolesElement::TYPE_HIDDEN,
            'menu_b' => RightsRolesElement::TYPE_READONLY,
            'menu_c' => RightsRolesElement::TYPE_EDITABLE,
            'menu_d' => RightsRolesElement::TYPE_HIDDEN,
        ];

        $filtered = $setting->filterListByTypes(
            $list,
            [RightsRolesElement::TYPE_READONLY, RightsRolesElement::TYPE_EDITABLE]
        );
        $this->assertSame(['menu_b', 'menu_c'], array_keys($filtered));
    }

    public function testCanHaveRestrictedViewWithoutXPathReturnsCountOfIntersection(): void
    {
        $setting = new AdminViewSetting();
        $restrictedView = [
            'menu_a' => RightsRolesElement::TYPE_READONLY,
            'menu_b' => RightsRolesElement::TYPE_EDITABLE,
            'menu_c' => RightsRolesElement::TYPE_READONLY,
        ];
        $rights = [
            'menu_a' => RightsRolesElement::TYPE_READONLY,
            'menu_c' => RightsRolesElement::TYPE_EDITABLE,
        ];
        // Both lists narrow to readonly+editable, then intersect_key on
        // {menu_a, menu_b, menu_c} ∩ {menu_a, menu_c} = 2 elements.
        $this->assertSame(2, $setting->canHaveRestrictedView($restrictedView, $rights));
    }

    public function testCanHaveRestrictedViewFallsBackToRestrictedCountWhenNoRights(): void
    {
        $setting = new AdminViewSetting();
        $restrictedView = [
            'menu_a' => RightsRolesElement::TYPE_READONLY,
            'menu_b' => RightsRolesElement::TYPE_EDITABLE,
        ];
        // Empty rights array → fallback path returns count of restricted view.
        $this->assertSame(2, $setting->canHaveRestrictedView($restrictedView, []));
    }

    public function testCanHaveRestrictedViewIgnoresHiddenEntries(): void
    {
        $setting = new AdminViewSetting();
        // HIDDEN values get filtered out by both inputs.
        $restrictedView = [
            'menu_a' => RightsRolesElement::TYPE_HIDDEN,
            'menu_b' => RightsRolesElement::TYPE_READONLY,
        ];
        $rights = [
            'menu_b' => RightsRolesElement::TYPE_EDITABLE,
        ];
        $this->assertSame(1, $setting->canHaveRestrictedView($restrictedView, $rights));
    }

    public function testGetDepthInTreeWalksParents(): void
    {
        $setting = new AdminViewSetting();
        $doc = new \DOMDocument();
        $doc->loadXML('<menu><MAINMENU><SUBMENU><TAB id="t1"/></SUBMENU></MAINMENU></menu>');
        $tab = $doc->getElementsByTagName('TAB')->item(0);

        // tab → SUBMENU → MAINMENU → menu → DOMDocument → null
        // depth-counter increments on entry per the loop's structure.
        $this->assertSame(4, $setting->getDepthInTree($tab));
    }

    public function testGetDepthInTreeForNullReturnsMinusOne(): void
    {
        $setting = new AdminViewSetting();
        $this->assertSame(-1, $setting->getDepthInTree(null));
    }

    public function testCanHaveRestrictedViewWithXPathFiltersByTabDepth(): void
    {
        $setting = new AdminViewSetting();
        // Build a menu where 'menu_a' lives deep enough to satisfy depth>=TABS
        // (counter starts at -2 and increments while walking parentNode up
        // through DOMDocument), and 'menu_b' is shallower so it gets filtered.
        $doc = new \DOMDocument();
        $doc->loadXML(
            '<menu><MAINMENU><SUBMENU><TAB><inner id="menu_a"/></TAB></SUBMENU>'
            . '<node id="menu_b"/></MAINMENU></menu>'
        );
        $xPath = new \DOMXPath($doc);

        $restrictedView = [
            'menu_a' => RightsRolesElement::TYPE_READONLY,
            'menu_b' => RightsRolesElement::TYPE_EDITABLE,
        ];
        $rights = [
            'menu_a' => RightsRolesElement::TYPE_READONLY,
            'menu_b' => RightsRolesElement::TYPE_EDITABLE,
        ];

        // With xPath, only menu_a (at TAB depth) survives.
        $this->assertSame(1, $setting->canHaveRestrictedView($restrictedView, $rights, $xPath));
    }
}
