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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\GuaranteeConfigController;
use OxidEsales\TestingLibrary\UnitTestCase;

class GuaranteeConfigControllerTest extends UnitTestCase
{
    public function testRenderExposesPersistedConfigValues(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', false);

        $controller = oxNew(GuaranteeConfigController::class);
        $template = $controller->render();

        $viewData = $controller->getViewData();
        $this->assertSame('guarantee_config.tpl', $template);
        $this->assertTrue($viewData['guarantee']['blShowLegalGuaranteeNotice']);
        $this->assertFalse($viewData['guarantee']['blShowDurabilityGuaranteeLabel']);
    }

    public function testSavePersistsBothSwitches(): void
    {
        $this->setRequestParameter('blShowLegalGuaranteeNotice', '1');
        $this->setRequestParameter('blShowDurabilityGuaranteeLabel', '1');

        $controller = oxNew(GuaranteeConfigController::class);
        $controller->save();

        $config = Registry::getConfig();
        $this->assertTrue((bool) $config->getConfigParam('blShowLegalGuaranteeNotice'));
        $this->assertTrue((bool) $config->getConfigParam('blShowDurabilityGuaranteeLabel'));
    }

    public function testSaveUncheckedCheckboxesPersistsOff(): void
    {
        Registry::getConfig()->saveShopConfVar('bool', 'blShowLegalGuaranteeNotice', '1');
        // unchecked checkboxes are absent from the POST
        $controller = oxNew(GuaranteeConfigController::class);
        $controller->save();

        $this->assertFalse((bool) Registry::getConfig()->getConfigParam('blShowLegalGuaranteeNotice'));
    }

    public function testAdminRouteResolves(): void
    {
        $map = oxNew(\OxidEsales\Eshop\Core\Routing\ShopControllerMapProvider::class)->getControllerMap();
        $this->assertSame(
            \OxidEsales\Eshop\Application\Controller\Admin\GuaranteeConfigController::class,
            $map['guarantee_config']
        );
    }
}
