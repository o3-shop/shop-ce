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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\TemplateExtension;

use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension;
use PHPUnit\Framework\TestCase;

class TemplateBlockExtensionTest extends TestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::setName
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getName
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::setFilePath
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getFilePath
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::setExtendedBlockTemplatePath
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getExtendedBlockTemplatePath
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::setPosition
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getPosition
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::setModuleId
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getModuleId
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::setShopId
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getShopId
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::setThemeId
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getThemeId
     */
    public function testFluentSettersStoreValuesAndReturnSelf(): void
    {
        $extension = new TemplateBlockExtension();
        $result = $extension
            ->setName('product_main')
            ->setFilePath('mymod/views/blocks/product_main.tpl')
            ->setExtendedBlockTemplatePath('page/details/inc/productmain.tpl')
            ->setPosition(3)
            ->setModuleId('mymod')
            ->setShopId(5)
            ->setThemeId('o3-theme');

        $this->assertSame($extension, $result);
        $this->assertSame('product_main', $extension->getName());
        $this->assertSame('mymod/views/blocks/product_main.tpl', $extension->getFilePath());
        $this->assertSame('page/details/inc/productmain.tpl', $extension->getExtendedBlockTemplatePath());
        $this->assertSame(3, $extension->getPosition());
        $this->assertSame('mymod', $extension->getModuleId());
        $this->assertSame(5, $extension->getShopId());
        $this->assertSame('o3-theme', $extension->getThemeId());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getPosition
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension::getThemeId
     */
    public function testDefaultsForPositionAndThemeId(): void
    {
        $extension = new TemplateBlockExtension();
        $this->assertSame(1, $extension->getPosition());
        $this->assertSame('', $extension->getThemeId());
    }
}
