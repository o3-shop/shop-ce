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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Handler;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\TemplateBlock;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\TemplateBlockModuleSettingHandler;
use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDaoInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TemplateBlockModuleSettingHandlerTest extends TestCase
{
    public function testHandlingOnModuleActivation()
    {
        $templateBlock = new TemplateBlock(
            'extendedTemplatePath',
            'testBlock',
            'filePath'
        );
        $templateBlock->setTheme('flow_theme');
        $templateBlock->setPosition(3);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId('testModule')
            ->addTemplateBlock($templateBlock);

        $templateBlockExtension = new TemplateBlockExtension();
        $templateBlockExtension
            ->setShopId(1)
            ->setModuleId('testModule')
            ->setName('testBlock')
            ->setThemeId('flow_theme')
            ->setPosition(3)
            ->setExtendedBlockTemplatePath('extendedTemplatePath')
            ->setFilePath('filePath');

        $templateBlockDao = $this->getTemplateBlockDaoMock();
        $templateBlockDao
            ->expects($this->once())
            ->method('add')
            ->with($templateBlockExtension);

        $settingHandler = new TemplateBlockModuleSettingHandler($templateBlockDao);
        $settingHandler->handleOnModuleActivation(
            $moduleConfiguration,
            1
        );
    }

    public function testHandlingOnModuleDeactivation()
    {
        $templateBlock = new TemplateBlock('', '', '');

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId('testModule')
            ->addTemplateBlock($templateBlock);

        $templateBlockDao = $this->getTemplateBlockDaoMock();
        $templateBlockDao
            ->expects($this->once())
            ->method('deleteExtensions')
            ->with('testModule', 1);

        $settingHandler = new TemplateBlockModuleSettingHandler($templateBlockDao);
        $settingHandler->handleOnModuleDeactivation(
            $moduleConfiguration,
            1
        );
    }

    private function getTemplateBlockDaoMock(): TemplateBlockExtensionDaoInterface
    {
        return $this
            ->getMockBuilder(TemplateBlockExtensionDaoInterface::class)
            ->getMock();
    }
}
