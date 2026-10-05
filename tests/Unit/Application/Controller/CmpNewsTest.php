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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use OxidEsales\EshopCommunity\Application\Model\NewsList;

/**
 * oxcmp_news tests
 */
class CmpNewsTest extends \OxidTestCase
{
    /**
     * Testing oxcmp_news::render()
     *
     * @return null
     */
    public function testRenderDisabledNavBars()
    {
        $this->getConfig()->setConfigParam('bl_perfLoadNews', false);

        $oCmp = oxNew('oxcmp_news');
        $this->assertNull($oCmp->render());
    }

    /**
     * Testing oxcmp_news::render()
     *
     * @return null
     */
    public function testRenderPerfLoadNewsOnlyStart()
    {
        $this->getConfig()->setConfigParam('bl_perfLoadNews', true);
        $this->getConfig()->setConfigParam('blDisableNavBars', false);
        $this->getConfig()->setConfigParam('bl_perfLoadNewsOnlyStart', true);

        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['getClassKey']);
        $oView->expects($this->once())->method('getClassKey')->will($this->returnValue('test'));
        $this->getConfig()->setActiveView($oView);

        $oCmp = oxNew(\OxidEsales\Eshop\Application\Component\NewsComponent::class);
        $this->assertNull($oCmp->render());
    }

    /**
     * Testing oxcmp_news::render()
     *
     * @return null
     */
    public function testRender()
    {
        $this->getConfig()->setConfigParam('bl_perfLoadNews', true);
        $this->getConfig()->setConfigParam('blDisableNavBars', false);
        $this->getConfig()->setConfigParam('bl_perfLoadNewsOnlyStart', false);

        $oCmp = oxNew('oxcmp_news');
        $this->assertTrue($oCmp->render() instanceof newslist);
    }
}
