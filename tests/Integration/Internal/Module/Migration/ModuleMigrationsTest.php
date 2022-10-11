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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Module\Migration;

use OxidEsales\DoctrineMigrationWrapper\Migrations;
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\TestingLibrary\Services\Library\DatabaseRestorer\DatabaseRestorer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * Class ModuleMigrationsTest
 */
class ModuleMigrationsTest extends TestCase
{
    use ContainerTrait;

    /**
     * @var DatabaseRestorer
     */
    private $databaseRestorer;

    private $moduleIdWithMigrations= 'myTestModuleWithMigrations';
    private $moduleIdWithoutMigrations = 'myTestModuleWithoutMigrations';

    public function setUp(): void
    {
        $this->databaseRestorer = new DatabaseRestorer();
        $this->databaseRestorer->dumpDB(__CLASS__);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->databaseRestorer->restoreDB(__CLASS__);

        parent::tearDown();
    }

    public function testMigrationsExecutionWithSpecificModule(): void
    {
        $this->installModule($this->moduleIdWithMigrations);

        $migrations = $this->getMigrations();
        $migrations->execute(Migrations::MIGRATE_COMMAND, $this->moduleIdWithMigrations);

        $this->assertIfMigrationExistsInDatabase();

        $this->removeTestModule($this->moduleIdWithMigrations);
    }

    public function testNoErrorWhenModuleHasNoMigrations(): void
    {
        $this->installModule($this->moduleIdWithoutMigrations);

        $migrations = $this->getMigrations();
        $migrations->execute(Migrations::MIGRATE_COMMAND, 'myTestModule');

        $this->removeTestModule($this->moduleIdWithoutMigrations);
    }

    public function testAllMigrationsExecuteHasModuleMigrationInside(): void
    {
        $this->installModule($this->moduleIdWithMigrations);

        $migrations = $this->getMigrations();
        $migrations->execute(Migrations::MIGRATE_COMMAND);

        $this->assertIfMigrationExistsInDatabase();

        $this->removeTestModule($this->moduleIdWithMigrations);
    }

    /**
     * @param string $moduleId
     */
    private function installModule(string $moduleId): void
    {
        $package = new OxidEshopPackage($moduleId, __DIR__ . '/Fixtures/' . $moduleId);
        $package->setTargetDirectory('oeTest/'. $moduleId);
        $this->getModuleInstaller()->install($package);
    }

    /**
     * @param string $moduleId
     */
    private function removeTestModule(string $moduleId): void
    {
        $package = new OxidEshopPackage($moduleId, __DIR__ . '/Fixtures/' . $moduleId);
        $package->setTargetDirectory('oeTest/'. $moduleId);
        $this->getModuleInstaller()->uninstall($package);
    }

    private function assertIfMigrationExistsInDatabase(): void
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder
            ->select('*')
            ->from('test_module_with_migrations');

        $this->assertEquals(1, $queryBuilder->execute()->rowCount());
    }

    private function getMigrations(): Migrations
    {
        $migrations = (new MigrationsBuilder())->build();

        $output = new ConsoleOutput();
        $output->setVerbosity(ConsoleOutputInterface::VERBOSITY_QUIET);
        $migrations->setOutput($output);

        return $migrations;
    }

    private function getModuleInstaller(): ModuleInstallerInterface
    {
        $container = ContainerFactory::getInstance()->getContainer();
        return $container->get(ModuleInstallerInterface::class);
    }
}
