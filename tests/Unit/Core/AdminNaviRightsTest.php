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

use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElement;
use OxidEsales\EshopCommunity\Core\AdminNaviRights;
use OxidEsales\EshopCommunity\Core\Exception\AccessDeniedException;

class AdminNaviRightsTest extends \OxidTestCase
{
    public function testIntersectRightListsKeepsMinimumPerKey(): void
    {
        $rights = new AdminNaviRights();
        $list1 = [
            'menu_a' => RightsRolesElement::TYPE_EDITABLE,
            'menu_b' => RightsRolesElement::TYPE_READONLY,
        ];
        $list2 = [
            'menu_a' => RightsRolesElement::TYPE_READONLY,
            'menu_c' => RightsRolesElement::TYPE_HIDDEN,
        ];
        $merged = $rights->intersectRightLists($list1, $list2);

        // menu_a: min(EDITABLE, READONLY) → READONLY
        $this->assertSame(RightsRolesElement::TYPE_READONLY, $merged['menu_a']);
        // menu_b: only in list1 → unchanged
        $this->assertSame(RightsRolesElement::TYPE_READONLY, $merged['menu_b']);
        // menu_c: from list2 → propagated
        $this->assertSame(RightsRolesElement::TYPE_HIDDEN, $merged['menu_c']);
    }

    public function testLoadFlipsTheLoadFlag(): void
    {
        $rights = new AdminNaviRights();
        $ref = new \ReflectionProperty(AdminNaviRights::class, 'doLoad');
        $ref->setAccessible(true);
        $this->assertFalse($ref->getValue($rights));

        $rights->load();
        $this->assertTrue($ref->getValue($rights));
    }

    public function testCleanTreeRemovesNodesMatchingHiddenRights(): void
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<root><menu id="hidden_one"/><menu id="visible_one"/></root>');

        // Subclass to inject a steered list of "hidden" rights without going to DB.
        $rights = new class () extends AdminNaviRights {
            protected function getMenuItemRights(\DOMXPath $xPath = null)
            {
                return [
                    'hidden_one'  => RightsRolesElement::TYPE_HIDDEN,
                    'visible_one' => RightsRolesElement::TYPE_EDITABLE,
                ];
            }
        };

        $rights->cleanTree($doc);
        $remaining = [];
        foreach ($doc->getElementsByTagName('menu') as $menu) {
            $remaining[] = $menu->getAttribute('id');
        }
        $this->assertSame(['visible_one'], $remaining);
    }

    public function testCleanTreeIsNoopWhenNoHiddenRights(): void
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<root><menu id="a"/><menu id="b"/></root>');

        $rights = new class () extends AdminNaviRights {
            protected function getMenuItemRights(\DOMXPath $xPath = null)
            {
                return [
                    'a' => RightsRolesElement::TYPE_EDITABLE,
                    'b' => RightsRolesElement::TYPE_READONLY,
                ];
            }
        };

        $rights->cleanTree($doc);
        $this->assertSame(2, $doc->getElementsByTagName('menu')->length);
    }

    public function testApplyRightsThrowsAccessDeniedWhenViewIsHidden(): void
    {
        $rights = new class () extends AdminNaviRights {
            public function getUser()
            {
                return new \stdClass();
            }
            protected function getMenuItemRights(\DOMXPath $xPath = null)
            {
                return ['blocked_view' => RightsRolesElement::TYPE_HIDDEN];
            }
        };

        $view = $this->createMock(BaseController::class);
        $view->method('getViewId')->willReturn('blocked_view');

        $this->expectException(AccessDeniedException::class);
        $rights->applyRights($view);
    }

    public function testApplyRightsAllowsWhenViewIsEditable(): void
    {
        $rights = new class () extends AdminNaviRights {
            public function getUser()
            {
                return new \stdClass();
            }
            protected function getMenuItemRights(\DOMXPath $xPath = null)
            {
                return ['allowed_view' => RightsRolesElement::TYPE_EDITABLE];
            }
        };

        $view = $this->createMock(BaseController::class);
        $view->method('getViewId')->willReturn('allowed_view');

        // Must not throw.
        $rights->applyRights($view);
        $this->assertTrue(true);
    }

    public function testApplyRightsReturnsEarlyWhenNoUser(): void
    {
        $rights = new class () extends AdminNaviRights {
            public function getUser()
            {
                return null; // not logged in
            }
            protected function getMenuItemRights(\DOMXPath $xPath = null)
            {
                throw new \LogicException('must not be reached when user is missing');
            }
        };

        $view = $this->createMock(BaseController::class);
        $view->method('getViewId')->willReturn('any_view');

        $rights->applyRights($view);
        $this->assertTrue(true);
    }

    public function testApplyRightsReturnsEarlyWhenViewIdIsEmpty(): void
    {
        $rights = new class () extends AdminNaviRights {
            public function getUser()
            {
                return new \stdClass();
            }
            protected function getMenuItemRights(\DOMXPath $xPath = null)
            {
                throw new \LogicException('must not be reached when view id is missing');
            }
        };

        $view = $this->createMock(BaseController::class);
        $view->method('getViewId')->willReturn(null);

        $rights->applyRights($view);
        $this->assertTrue(true);
    }

    public function testGetMenuItemRightsCombinesRoleAndViewRights(): void
    {
        // Exercise the real getMenuItemRights() body — mocking only the two
        // helper methods that touch DB / Registry.
        $rights = new class () extends AdminNaviRights {
            public bool $loadEnabled = true;
            public function load()
            {
                $this->doLoad = $this->loadEnabled;
            }
            protected function getRoleRights()
            {
                return ['MENU_A' => RightsRolesElement::TYPE_READONLY];
            }
            protected function getRestrictedViewRights(bool $showAll, array $roleRights, ?\DOMXPath $xPath = null)
            {
                return ['MENU_A' => RightsRolesElement::TYPE_EDITABLE];
            }
        };
        $rights->load();

        $method = new \ReflectionMethod($rights, 'getMenuItemRights');
        $method->setAccessible(true);
        $combined = $method->invoke($rights);

        // Keys must be lower-cased; intersection picks the lower (more
        // restrictive) right.
        $this->assertArrayHasKey('menu_a', $combined);
        $this->assertSame(RightsRolesElement::TYPE_READONLY, $combined['menu_a']);
    }

    public function testGetMenuItemRightsFallsBackToRoleRightsAloneWhenNoViewRights(): void
    {
        $rights = new class () extends AdminNaviRights {
            public function load()
            {
                $this->doLoad = true;
            }
            protected function getRoleRights()
            {
                return ['MENU_X' => RightsRolesElement::TYPE_READONLY];
            }
            protected function getRestrictedViewRights(bool $showAll, array $roleRights, ?\DOMXPath $xPath = null)
            {
                return [];
            }
        };
        $rights->load();

        $method = new \ReflectionMethod($rights, 'getMenuItemRights');
        $method->setAccessible(true);
        $result = $method->invoke($rights);
        $this->assertSame(['menu_x' => RightsRolesElement::TYPE_READONLY], $result);
    }
}
