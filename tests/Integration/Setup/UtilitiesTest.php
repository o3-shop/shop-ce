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
namespace OxidEsales\EshopCommunity\Tests\Integration\Setup;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Setup\Utilities;
use OxidEsales\Facts\Facts;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use oxDatabaseHelper;

require_once TEST_LIBRARY_HELPERS_PATH . 'oxDatabaseHelper.php';

class UtilitiesTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    public function setup(): void
    {
        parent::setUp();

        $databaseHelper = new oxDatabaseHelper(DatabaseProvider::getDb());
        $databaseHelper->adjustTemplateBlocksOxModuleColumn();
    }

    public function testGetLicenseContent()
    {
        $utilities = new Utilities();
        $this->assertNotNull($utilities->getLicenseContent('en'));
    }

    public function testExecuteExternalRegenerateViewsCommand()
    {
        $this->assertViewExists('oxdiscount');

        $this->dropOxDiscountView();
        $this->assertViewNotExists('oxdiscount');

        $utilities = new Utilities();
        $utilities->executeExternalRegenerateViewsCommand();

        $this->assertViewExists('oxdiscount');
    }

    public function testExecuteExternalDatabaseMigrationCommand()
    {
        $output = new ConsoleOutput();
        $output->setVerbosity(ConsoleOutputInterface::VERBOSITY_QUIET);

        $factsMock = $this->createFactsMock();

        $this->assertMigrationCreatedTablesDontExist();

        $utilities = new Utilities();
        $utilities->executeExternalDatabaseMigrationCommand($output, $factsMock);

        $this->assertMigrationCreatedTablesExist();
    }

    public function testExecuteExternalDemodataAssetsInstallCommand()
    {
        $utilities = new Utilities();
        $packagePath = $utilities->getActiveEditionDemodataPackagePath();
        $demodataShouldBeInstalled = file_exists($packagePath);

        if ($demodataShouldBeInstalled) {
            $errorCode = $utilities->executeExternalDemodataAssetsInstallCommand();

            $this->assertEquals(0, $errorCode);

            $database = DatabaseProvider::getDb();

            $sql = "SELECT `OXTITLE` FROM `oxdelivery` WHERE oxid = ?";
            $oxtitle = $database->getOne($sql, ['1b842e734b62a4775.45738618']);

            $this->assertEquals('Versandkosten für Standard: Versandkostenfrei ab 80,-', $oxtitle);

            $facts = new Facts();
            $expectedFile = $facts->getOutPath() . '/pictures/media/dir.txt';

            $this->assertFileExists($expectedFile);
        }
    }

    private function tableExists($tableName)
    {
        $databaseHelper = new oxDatabaseHelper(DatabaseProvider::getDb());

        return $databaseHelper->existsTable($tableName);
    }

    protected function assertMigrationCreatedTablesDontExist()
    {
        $editions = ['ce', 'pe', 'ee'];

        foreach ($editions as $edition) {
            $tableName = 'migrations_test_' . $edition;
            $this->assertFalse($this->tableExists($tableName), "Expected that the table '$tableName' does not exist! But it exists!");
        }
    }

    protected function assertMigrationCreatedTablesExist()
    {
        $editions = ['ce', 'pe', 'ee'];
        $editionSelector = oxNew(\OxidEsales\Facts\Edition\EditionSelector::class);
        $currentlySelectedEdition = strtolower($editionSelector->getEdition());

        foreach ($editions as $edition) {
            $tableName = 'migrations_test_' . $edition;
            $this->assertTrue($this->tableExists($tableName), "Expected that the table '$tableName' exists! But it does not!");

            if ($edition === $currentlySelectedEdition) {
                break;
            }
        }
    }

    protected function dropOxDiscountView()
    {
        $databaseHelper = new oxDatabaseHelper(DatabaseProvider::getDb());

        $databaseHelper->dropView('oxdiscount');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFactsMock()
    {
        $ceMigrationPath = realpath(dirname(__FILE__) . '/migration/ce/migrations.yml');
        $peMigrationPath = realpath(dirname(__FILE__) . '/migration/pe/migrations.yml');
        $eeMigrationPath = realpath(dirname(__FILE__) . '/migration/ee/migrations.yml');

        $factsMock = $this->getMock(Facts::class, ['getMigrationPaths']);
        $factsMock->expects($this->any())
            ->method('getMigrationPaths')
            ->will($this->returnValue([
                'ce' => $ceMigrationPath,
                'pe' => $peMigrationPath,
                'ee' => $eeMigrationPath
            ]));

        return $factsMock;
    }
}
