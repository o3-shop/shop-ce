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

namespace OxidEsales\EshopCommunity\Tests\CodeceptionAdmin;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceAdminTester;

final class ModuleSortListCest
{
    private $module;
    private $testModule1Id = 'codeception/test-module-1';
    private $testModule1Path = __DIR__ . '/../_data/modules/test-module-1';
    private $testModuleWithProblemsId = 'codeception/test-module-problems';
    private $testModuleWithProblemsPath = __DIR__ . '/../_data/modules/test-module-problems';

    /** @param AcceptanceAdminTester $I */
    public function moduleSortList(AcceptanceAdminTester $I): void
    {
        $I->wantToTest('module sort list functionality with valid module');
        $I->installModule($this->testModule1Path);
        $I->deactivateModule($this->testModule1Id);
        $this->selectModule($I, 'Codeception test module #1');
        $this->module->openModuleTab('Installed Shop Modules');

        $I->seeElement('li#OxidEsales---Eshop---Application---Controller---ContentController');
        $I->seeElement('li#test-module-1\/Controller\/ContentController .disabled');

        $this->activateSelectedModule($I);
        $this->module->openModuleTab('Installed Shop Modules');

        $I->seeElement('li#OxidEsales---Eshop---Application---Controller---ContentController');
        $I->seeElement('li#test-module-1\/Controller\/ContentController');
        $I->dontSeeElement('li#test-module-1\/Controller\/ContentController .disabled');

        $I->uninstallModule($this->testModule1Path, $this->testModule1Id);
    }

    /** @param AcceptanceAdminTester $I */
    public function moduleWithProblemsSortList(AcceptanceAdminTester $I): void
    {
        $I->wantToTest('module sort list functionality with problematic module');
        $I->installModule($this->testModuleWithProblemsPath);
        $this->selectModule($I, 'Module with problems (Namespaced)');

        $this->activateSelectedModule($I);
        $this->module->openModuleTab('Installed Shop Modules');
        /** info about existing problems is displayed */
        $I->see(Translator::translate('MODULE_EXTENSIONISDELETED'));
        $I->see(Translator::translate('MODULE_PROBLEMATIC_FILES'));
        $I->see('OxidEsales\Eshop\Application\Model\Article');
        $I->see('NonExistentFile');

        /** click remove problematic configs */
        $I->click(['name' => 'yesButton']);

        $I->dontSee(Translator::translate('MODULE_EXTENSIONISDELETED'));
        /** check module's not active */
        $this->module->openModuleTab('Overview');
        $I->seeElement('#module_activate');
        $I->dontSeeElement('#module_deactivate');

        $I->uninstallModule($this->testModuleWithProblemsPath, $this->testModuleWithProblemsId);
    }

    /**
     * @param AcceptanceAdminTester $I
     * @param string $moduleId
     */
    private function selectModule(AcceptanceAdminTester $I, string $moduleId): void
    {
        $userData = Fixtures::get('adminUser');
        $loginPage = $I->openAdmin();
        $loginPage->login($userData['userLoginName'], $userData['userPassword']);
        $moduleList = $loginPage->openModules();
        $this->module = $moduleList->selectModule($moduleId);
    }

    /** @param AcceptanceAdminTester $I */
    private function activateSelectedModule(AcceptanceAdminTester $I): void
    {
        $this->module->openModuleTab('Overview');
        $I->click('#module_activate');
    }
}
