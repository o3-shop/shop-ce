<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\ComposerPlugin;

use Composer\IO\NullIO;
use Composer\Package\Package;
use OxidEsales\ComposerPlugin\Installer\Package\ComponentInstaller;
use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDao;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\Facts\Facts;
use PHPUnit\Framework\TestCase;

class ComponentInstallerTest extends TestCase
{
    use ContainerTrait;

    private $servicesFilePath = 'Fixtures/services.yaml';

    public function tearDown(): void
    {
        parent::tearDown();

        $this->removeGeneratedLineFromProjectFile();
    }

    public function testInstall()
    {
        $installer = $this->createInstaller();
        $installer->install(__DIR__ . '/Fixtures');

        $this->assertTrue($this->doesServiceLineExists());
    }

    public function testUpdate()
    {
        $installer = $this->createInstaller();
        $installer->update(__DIR__ . '/Fixtures');

        $this->assertTrue($this->doesServiceLineExists());
    }

    /**
     * @return ComponentInstaller
     */
    private function createInstaller(): ComponentInstaller
    {
        $packageStub = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $installer = new ComponentInstaller(
            new NullIO(),
            (new Facts)->getShopRootPath(),
            $packageStub
        );
        return $installer;
    }

    private function doesServiceLineExists()
    {
        $context = BootstrapContainerFactory::getBootstrapContainer()->get(BasicContextInterface::class);
        $contentsOfProjectFile = file_get_contents(
            $context->getGeneratedServicesFilePath()
        );

        return (bool)strpos($contentsOfProjectFile, $this->servicesFilePath);
    }

    private function removeGeneratedLineFromProjectFile()
    {
        /** @var ProjectYamlDao $projectYamlDao */
        $projectYamlDao = $this->get(ProjectYamlDaoInterface::class);
        $DIconfig = $projectYamlDao->loadProjectConfigFile();
        $DIconfig->removeImport($this->servicesFilePath);
        $projectYamlDao->saveProjectConfigFile($DIconfig);
    }
}
