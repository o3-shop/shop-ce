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
namespace OxidEsales\EshopCommunity\Tests\Integration\Modules;

use oxException;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Core\Module\ModuleTemplateBlockPathFormatter;
use OxidEsales\Eshop\Core\Module\ModuleTemplateBlockContentReader;

/**
 * @group module
 * @package Integration\Modules
 */
class ModuleTemplateBlockTest extends UnitTestCase
{
    public function testGetContentForModuleTemplateBlock()
    {
        $shopPath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'TestData', 'shop']);
        $moduleId = 'oeTestTemplateBlockModuleId';

        $this->setConfigParam(
            'aModulePaths',
            [$moduleId => 'oe/testTemplateBlockModuleId']
        );

        $pathFormatter = oxNew(ModuleTemplateBlockPathFormatter::class);
        $pathFormatter->setModulesPath($shopPath . DIRECTORY_SEPARATOR . 'modules');
        $pathFormatter->setModuleId($moduleId);
        $pathFormatter->setFileName('blocks/blocktemplate.tpl');

        $blockContentGetter = oxNew(ModuleTemplateBlockContentReader::class);
        $actualContent = $blockContentGetter->getContent($pathFormatter);

        $expectedContent = 'block template content';
        $this->assertSame($expectedContent, $actualContent);
    }

    public function testThrowExcpetionWhenModuleTemplateBlockFileDoesNotExist()
    {
        $this->expectException(oxException::class);

        $shopPath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'TestData', 'shop']);
        $moduleId = 'oeTestTemplateBlockModuleId';

        $this->setConfigParam(
            'aModulePaths',
            [$moduleId => 'oe/testTemplateBlockModuleId']
        );

        $pathFormatter = oxNew(ModuleTemplateBlockPathFormatter::class);
        $pathFormatter->setModulesPath($shopPath . DIRECTORY_SEPARATOR . 'modules');
        $pathFormatter->setModuleId($moduleId);
        $pathFormatter->setFileName('blocks/blocktemplate_notExist.tpl');

        $blockContentReader = oxNew(ModuleTemplateBlockContentReader::class);
        $blockContentReader->getContent($pathFormatter);
    }
}
