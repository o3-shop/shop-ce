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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model\Module;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\EshopCommunity\Core\Module\ModuleExtensionsCleaner;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\TestingLibrary\UnitTestCase;

class ModuleExtensionsCleanerTest extends UnitTestCase
{
    /**
     * Test case for bug #6342
     */
    private $testModuleId = 'with_class_extensions_cleaner';

    public function tearDown(): void
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $fileSystem = $container->get('oxid_esales.symfony.file_system');
        $fileSystem->remove($container->get(ContextInterface::class)->getModulesPath() . '/' . $this->testModuleId);
        parent::tearDown();
    }

    public function testChecksIfModuleIdDoesNotDependOnDirectory()
    {
        $this->installTestModule();

        $installedExtensions = [
            Article::class => ['with_class_extensions_cleaner/ModuleArticle'],
            'otherEshopClass' => ['with_class_extensions_cleaner/testModuleDirectory/class/which/is/garbage'],
            'yetAnotherEshopClass' => ['anyModule/testModuleDirectory/class/which/is/not/garbage'],
        ];

        $cleanedExtensionsData = [
            Article::class => ['with_class_extensions_cleaner/ModuleArticle'],
            'yetAnotherEshopClass' => ['anyModule/testModuleDirectory/class/which/is/not/garbage'],
        ];

        /** @var ModuleExtensionsCleaner $extensionsCleaner */
        $extensionsCleaner = oxNew(ModuleExtensionsCleaner::class);
        $module = oxNew(Module::class);
        $module->load($this->testModuleId);

        $this->assertSame($cleanedExtensionsData, $extensionsCleaner->cleanExtensions($installedExtensions, $module));
    }

    private function installTestModule()
    {
        $container = ContainerFactory::getInstance()->getContainer();

        $container
            ->get(ModuleInstallerInterface::class)
            ->install(
                new OxidEshopPackage(
                    $this->testModuleId,
                    __DIR__ . '/Fixtures/' . $this->testModuleId
                )
            );
    }
}
