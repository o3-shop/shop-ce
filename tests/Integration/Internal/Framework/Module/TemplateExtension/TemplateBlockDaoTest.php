<?php
declare(strict_types=1);

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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\TemplateExtension;

use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDaoInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TemplateBlockDaoTest extends TestCase
{
    use ContainerTrait;

    public function testAddTemplateBlock()
    {
        $templateBlock = new TemplateBlockExtension();
        $templateBlock
            ->setName('testTemplateBlock')
            ->setFilePath('blockFilePath')
            ->setExtendedBlockTemplatePath('shopTemplatePath')
            ->setPosition(1)
            ->setModuleId('testModuleId')
            ->setShopId(1)
            ->setThemeId('testThemeId');

        $templateBlockDao = $this->getTemplateBlockDao();
        $templateBlockDao->add($templateBlock);

        $this->assertEquals(
            [$templateBlock],
            $templateBlockDao->getExtensions('testTemplateBlock', 1)
        );
    }

    public function testDeleteAllModuleTemplateBlocks()
    {
        $templateBlock = new TemplateBlockExtension();
        $templateBlock
            ->setName('testTemplateBlock')
            ->setFilePath('blockFilePath')
            ->setExtendedBlockTemplatePath('shopTemplatePath')
            ->setPosition(1)
            ->setModuleId('testModuleId')
            ->setShopId(1);

        $templateBlock2 = new TemplateBlockExtension();
        $templateBlock2
            ->setName('testTemplateBlock2')
            ->setFilePath('blockFilePath')
            ->setExtendedBlockTemplatePath('shopTemplatePath')
            ->setPosition(1)
            ->setModuleId('testModuleId')
            ->setShopId(1);

        $templateBlockDao = $this->getTemplateBlockDao();
        $templateBlockDao->add($templateBlock);
        $templateBlockDao->add($templateBlock2);

        $templateBlockDao->deleteExtensions('testModuleId', 1);

        $this->assertEquals(
            [],
            $templateBlockDao->getExtensions('testTemplateBlock', 1)
        );
    }

    private function getTemplateBlockDao(): TemplateBlockExtensionDaoInterface
    {
        return $this->get(TemplateBlockExtensionDaoInterface::class);
    }
}
