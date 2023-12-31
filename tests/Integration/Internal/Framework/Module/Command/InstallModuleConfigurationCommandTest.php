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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\InstallModuleConfigurationCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class InstallModuleConfigurationCommandTest extends ModuleCommandsTestCase
{
    private $shopId;
    private $testModuleId = 'testmodule';
    private $moduleTargetPath = 'testmodule';
    private $workingDirectoryBackup;
    private $workingDirectory;

    public function setup(): void
    {
        $context = $this->get(ContextInterface::class);
        $this->shopId = $context->getCurrentShopId();
        $this->workingDirectoryBackup = getcwd();
        $this->setWorkingDirectoryForConsole(__DIR__);

        parent::setUp();
    }

    public function tearDown(): void
    {
        $this->setWorkingDirectoryForConsole($this->workingDirectoryBackup);
        parent::tearDown();
    }

    public function testInstallFromModulesDirectoryWithAbsoluteSourcePath()
    {
        $this->installTestModuleFiles();

        $context = $this->get(ContextInterface::class);
        $consoleOutput = $this->executeModuleInstallCommand($context->getModulesPath() . '/' . $this->moduleTargetPath);

        $this->assertStringContainsString(InstallModuleConfigurationCommand::MESSAGE_INSTALLATION_WAS_SUCCESSFUL, $consoleOutput);

        $moduleConfiguration = $this->get(ModuleConfigurationDaoInterface::class)->get($this->testModuleId, $this->shopId);
        $this->assertSame(
            $this->testModuleId,
            $moduleConfiguration->getId()
        );
    }

    public function testInstallFromModulesDirectoryWithRelativeSourcePath()
    {
        $this->installTestModuleFiles();

        $context = $this->get(ContextInterface::class);

        $relativeModulePath = Path::makeRelative(
            $context->getModulesPath() . '/' . $this->moduleTargetPath,
            $this->workingDirectory
        );

        $this->assertStringContainsString(
            InstallModuleConfigurationCommand::MESSAGE_INSTALLATION_WAS_SUCCESSFUL,
            $this->executeModuleInstallCommand($relativeModulePath)
        );

        $moduleConfiguration = $this->get(ModuleConfigurationDaoInterface::class)->get($this->testModuleId, $this->shopId);
        $this->assertSame(
            $this->testModuleId,
            $moduleConfiguration->getId()
        );
    }

    public function testInstallFromNotModulesDirectoryWithProvidedAbsoluteTargetPath()
    {
        $context = $this->get(ContextInterface::class);

        $consoleOutput = $this->executeModuleInstallCommand(
            $this->getTestModuleSourcePath(),
            $context->getModulesPath() . '/' . $this->moduleTargetPath
        );

        $this->assertStringContainsString(InstallModuleConfigurationCommand::MESSAGE_INSTALLATION_WAS_SUCCESSFUL, $consoleOutput);

        $moduleConfiguration = $this->get(ModuleConfigurationDaoInterface::class)->get($this->testModuleId, $this->shopId);
        $this->assertSame(
            $this->moduleTargetPath,
            $moduleConfiguration->getPath()
        );
    }

    public function testInstallFromNotModulesDirectoryWithProvidedRelativeTargetPath()
    {
        $context = $this->get(ContextInterface::class);

        $relativeModulePath = Path::makeRelative(
            $context->getModulesPath() . '/' . $this->moduleTargetPath,
            $this->workingDirectory
        );

        $consoleOutput = $this->executeModuleInstallCommand(
            $this->getTestModuleSourcePath(),
            $relativeModulePath
        );

        $this->assertStringContainsString(InstallModuleConfigurationCommand::MESSAGE_INSTALLATION_WAS_SUCCESSFUL, $consoleOutput);

        $moduleConfiguration = $this->get(ModuleConfigurationDaoInterface::class)->get($this->testModuleId, $this->shopId);
        $this->assertSame(
            $this->moduleTargetPath,
            $moduleConfiguration->getPath()
        );
    }

    public function testInstallFromNotModulesDirectoryWithoutProvidedTargetPath()
    {
        $consoleOutput = $this->executeModuleInstallCommand($this->getTestModuleSourcePath());

        $this->assertStringContainsString(InstallModuleConfigurationCommand::MESSAGE_TARGET_PATH_IS_REQUIRED, $consoleOutput);
    }

    public function testInstallWithWrongModuleSourcePath()
    {
        $consoleOutput = $this->executeModuleInstallCommand('fakePath');

        $this->assertStringContainsString(InstallModuleConfigurationCommand::MESSAGE_INSTALLATION_FAILED, $consoleOutput);
    }

    public function testInstallWithWrongModuleTargetPath()
    {
        $consoleOutput = $this->executeModuleInstallCommand($this->getTestModuleSourcePath(), 'fakePath');

        $this->assertStringContainsString(InstallModuleConfigurationCommand::MESSAGE_INSTALLATION_FAILED, $consoleOutput);
    }

    private function executeModuleInstallCommand(string $moduleSourcePath, string $moduleTargetPath = null): string
    {
        $input = [
            'command' => 'oe:module:install-configuration',
            'module-source-path' => $moduleSourcePath,
        ];

        if ($moduleTargetPath) {
            $input['module-target-path'] = $moduleTargetPath;
        }

        $app = $this->getApplication();

        return $this->execute(
            $app,
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput($input)
        );
    }

    private function installTestModuleFiles()
    {
        $this->get(ModuleFilesInstallerInterface::class)->install(
            new OxidEshopPackage($this->testModuleId, $this->getTestModuleSourcePath())
        );
    }

    private function getTestModuleSourcePath(): string
    {
        return __DIR__ . '/Fixtures/modules/testmodule';
    }

    /**
     * @param string $workingDirectory
     */
    private function setWorkingDirectoryForConsole(string $workingDirectory)
    {
        chdir($workingDirectory);
        $this->workingDirectory = $workingDirectory;
    }
}
