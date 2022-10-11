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
namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Component\Widget;

use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Tests for OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree class
 */
class CategoryTreeTest extends UnitTestCase
{
    /**
     * Testing OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree::render()
     *
     * @return null
     */
    public function testRender()
    {
        $categoryTree = oxNew('OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree');
        $this->assertEquals('widget/sidebar/categorytree.tpl', $categoryTree->render());
    }

    /**
     * Testing OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree::render()
     *
     * @return null
     */
    public function testRenderDifferentTemplate()
    {
        $this->setConfigParam('sTheme', 'azure');
        \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()->resetContainer();

        $categoryTree = oxNew('OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree');
        $categoryTree->setViewParameters(array("sWidgetType" => "header"));
        $this->assertEquals('widget/header/categorylist.tpl', $categoryTree->render());
    }
}
