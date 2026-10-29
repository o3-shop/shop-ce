<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Command;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Template;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\TestingLibrary\Services\Library\DatabaseRestorer\DatabaseRestorer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Covers oe:module:refresh-settings: the shop must follow the module configuration it is
 * running on, including changes made after the module was activated, without losing the
 * settings configured by the shop owner.
 *
 * @internal
 */
final class RefreshModuleSettingsCommandTest extends ModuleCommandsTestCase
{
    /**
     * @var DatabaseRestorer
     */
    private $databaseRestorer;

    public function setup(): void
    {
        $this->databaseRestorer = new DatabaseRestorer();
        $this->databaseRestorer->dumpDB(__CLASS__);

        $this->installTestModule();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->databaseRestorer->restoreDB(__CLASS__);

        $this->cleanupTestData();

        parent::tearDown();
    }

    public function testRefreshReDerivesSettingsAndKeepsConfiguredValues(): void
    {
        $this->configureTestModule(['testmodule/tpl/a.tpl' => 'testmodule/tpl/a.tpl']);
        $this->get(ModuleActivationBridgeInterface::class)->activate($this->moduleId, 1);
        $this->saveConfiguredSetting('configured-by-shop-owner');

        // the module configuration changed after activation: one template replaced by another
        $this->configureTestModule(['testmodule/tpl/b.tpl' => 'testmodule/tpl/b.tpl']);

        $output = $this->executeCommand([
            'command' => 'oe:module:refresh-settings',
            'module-id' => $this->moduleId,
        ]);

        $this->assertStringContainsString('Refreshed settings of testmodule in shop 1.', $output);
        $this->assertSame(
            ['testmodule/tpl/b.tpl' => 'testmodule/tpl/b.tpl'],
            $this->getRegisteredTemplates(1)[$this->moduleId]
        );
        $this->assertSame('configured-by-shop-owner', $this->getConfiguredSettingValue());
        $this->assertTrue($this->get(ModuleStateServiceInterface::class)->isActive($this->moduleId, 1));
    }

    public function testRefreshReadsMetadataFromDiskWhenRequested(): void
    {
        $this->configureTestModule(['testmodule/tpl/a.tpl' => 'testmodule/tpl/a.tpl']);
        $this->get(ModuleActivationBridgeInterface::class)->activate($this->moduleId, 1);

        // the module's metadata.php on disk is newer than the installed configuration
        $this->writeInstalledModuleMetadata(['testmodule/tpl/from-metadata.tpl' => 'testmodule/tpl/from-metadata.tpl']);

        $this->executeCommand([
            'command' => 'oe:module:refresh-settings',
            'module-id' => $this->moduleId,
            '--from-metadata' => true,
        ]);

        $this->assertSame(
            ['testmodule/tpl/from-metadata.tpl' => 'testmodule/tpl/from-metadata.tpl'],
            $this->getRegisteredTemplates(1)[$this->moduleId]
        );
    }

    public function testRefreshRemovesTheLastTemplateWhenConfigurationDropsIt(): void
    {
        $this->configureTestModule(['testmodule/tpl/a.tpl' => 'testmodule/tpl/a.tpl']);
        $this->get(ModuleActivationBridgeInterface::class)->activate($this->moduleId, 1);

        // the module configuration no longer defines any templates
        $this->configureTestModule([]);

        $this->executeCommand([
            'command' => 'oe:module:refresh-settings',
            'module-id' => $this->moduleId,
        ]);

        $this->assertArrayNotHasKey($this->moduleId, $this->getRegisteredTemplates(1));
    }

    public function testRefreshFromMetadataBacksUpTheShopConfigurationOutsideTheShopsDirectory(): void
    {
        $this->configureTestModule(['testmodule/tpl/a.tpl' => 'testmodule/tpl/a.tpl']);
        $this->get(ModuleActivationBridgeInterface::class)->activate($this->moduleId, 1);
        $this->writeInstalledModuleMetadata(['testmodule/tpl/from-metadata.tpl' => 'testmodule/tpl/from-metadata.tpl']);

        $configurationDirectory = $this->get(BasicContextInterface::class)->getProjectConfigurationDirectory();
        $shopConfigurationPath = $configurationDirectory . 'shops/1.yaml';
        $before = file_get_contents($shopConfigurationPath);

        // leftovers from previous runs would make the backup count non-deterministic; glob()
        // cannot be used here because the test configuration lives on the vfs:// stream
        $fileSystem = new Filesystem();
        foreach ($this->findBackupDirectories($configurationDirectory) as $leftover) {
            $fileSystem->remove($configurationDirectory . $leftover);
        }

        $this->executeCommand([
            'command' => 'oe:module:refresh-settings',
            'module-id' => $this->moduleId,
            '--from-metadata' => true,
        ]);

        // save() deletes the whole shops/ directory, so a backup kept inside it would be lost
        $backupDirectories = $this->findBackupDirectories($configurationDirectory);
        $this->assertCount(1, $backupDirectories, 'Expected one restorable backup of the shop configuration');
        $this->assertSame($before, file_get_contents($configurationDirectory . $backupDirectories[0] . '/1.yaml'));
        $this->assertNotSame($before, file_get_contents($shopConfigurationPath));
    }

    public function testRefreshFromMetadataUpdatesTheClassExtensionChain(): void
    {
        $shopClass = 'OxidEsales\\Eshop\\Application\\Model\\Article';
        $installedClass = 'testmodule\\ModuleArticleInstalled';
        $metadataClass = 'testmodule\\ModuleArticleFromMetadata';

        $this->configureTestModule([], [$shopClass => $installedClass]);
        $this->get(ModuleActivationBridgeInterface::class)->activate($this->moduleId, 1);

        $this->writeInstalledModuleMetadata([], [$shopClass => $metadataClass]);

        $this->executeCommand([
            'command' => 'oe:module:refresh-settings',
            'module-id' => $this->moduleId,
            '--from-metadata' => true,
        ]);

        $this->assertSame([$metadataClass], $this->getClassExtensions(1)[$this->moduleId]);
        $this->assertSame($metadataClass, $this->getClassExtensionChain(1)[$shopClass]);
    }

    public function testRefreshSkipsInactiveModules(): void
    {
        $this->configureTestModule(['testmodule/tpl/a.tpl' => 'testmodule/tpl/a.tpl']);

        $output = $this->executeCommand([
            'command' => 'oe:module:refresh-settings',
            'module-id' => $this->moduleId,
        ]);

        $this->assertStringContainsString('module is not active', $output);
        $this->assertArrayNotHasKey($this->moduleId, $this->getRegisteredTemplates(1));
    }

    /**
     * @param array $templates  template key => template path
     * @param array $extensions shop class => module class
     */
    private function configureTestModule(array $templates, array $extensions = []): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId($this->moduleId)
            ->setPath($this->moduleId)
            ->setVersion('1.0')
            ->setConfigured(true);

        foreach ($templates as $templateKey => $templatePath) {
            $moduleConfiguration->addTemplate(new Template($templateKey, $templatePath));
        }

        foreach ($extensions as $shopClass => $moduleClass) {
            $moduleConfiguration->addClassExtension(new ClassExtension($shopClass, $moduleClass));
        }

        $setting = new Setting();
        $setting
            ->setName('testSetting')
            ->setType('str')
            ->setValue('metadata-default');
        $moduleConfiguration->addModuleSetting($setting);

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->addModuleConfiguration($moduleConfiguration);
        $shopConfiguration->getClassExtensionsChain()->addExtensions($moduleConfiguration->getClassExtensions());

        $this->get(ShopConfigurationDaoInterface::class)->save($shopConfiguration, 1);
    }

    /**
     * @param array $templates  template key => template path
     * @param array $extensions shop class => module class
     */
    private function writeInstalledModuleMetadata(array $templates, array $extensions = []): void
    {
        $metadata = "<?php\n\n\$sMetadataVersion = '2.1';\n\n\$aModule = " . var_export(
            [
                'id' => $this->moduleId,
                'version' => '2.0',
                'author' => 'O3-Shop',
                'templates' => $templates,
                'extend' => $extensions,
            ],
            true
        ) . ";\n";

        file_put_contents(
            Path::join(Registry::getConfig()->getModulesDir(), $this->moduleId, 'metadata.php'),
            $metadata
        );
    }

    private function saveConfiguredSetting(string $value): void
    {
        $setting = new Setting();
        $setting
            ->setName('testSetting')
            ->setType('str')
            ->setValue($value);

        $this->get(SettingDaoInterface::class)->save($setting, $this->moduleId, 1);
    }

    private function getConfiguredSettingValue(): string
    {
        return $this->get(SettingDaoInterface::class)->get('testSetting', $this->moduleId, 1)->getValue();
    }

    /**
     * @return array
     */
    private function getRegisteredTemplates(int $shopId): array
    {
        try {
            $setting = $this->get(ShopConfigurationSettingDaoInterface::class)->get(
                ShopConfigurationSetting::MODULE_TEMPLATES,
                $shopId
            );
        } catch (EntryDoesNotExistDaoException $exception) {
            return [];
        }

        return (array)$setting->getValue();
    }

    private function getClassExtensions(int $shopId): array
    {
        return $this->getShopConfigurationSettingValue(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS, $shopId);
    }

    private function getClassExtensionChain(int $shopId): array
    {
        return $this->getShopConfigurationSettingValue(
            ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS_CHAIN,
            $shopId
        );
    }

    private function getShopConfigurationSettingValue(string $name, int $shopId): array
    {
        try {
            $setting = $this->get(ShopConfigurationSettingDaoInterface::class)->get($name, $shopId);
        } catch (EntryDoesNotExistDaoException $exception) {
            return [];
        }

        return (array)$setting->getValue();
    }

    /**
     * @return string[]
     */
    private function findBackupDirectories(string $configurationDirectory): array
    {
        return array_values(array_filter(
            scandir($configurationDirectory),
            static fn (string $entry): bool => str_starts_with($entry, 'shops.bak.')
        ));
    }

    private function executeCommand(array $input): string
    {
        $application = $this->getApplication();
        $application->setAutoExit(false);

        return $this->execute(
            $application,
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput($input)
        );
    }
}
