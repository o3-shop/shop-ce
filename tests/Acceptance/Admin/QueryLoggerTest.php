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

namespace OxidEsales\EshopCommunity\Tests\Acceptance\Admin;

use OxidEsales\EshopCommunity\Tests\Acceptance\AdminTestCase;
use OxidEsales\TestingLibrary\helpers\ExceptionLogFileHelper;
use Webmozart\PathUtil\Path;
use OxidEsales\Facts\Config\ConfigFile as FactsConfigFile;

/**
 * Class QueryLoggerTest
 *
 * @package OxidEsales\EshopCommunity\Tests\Acceptance\Admin
 */
class QueryLoggerTest extends AdminTestCase
{

    /**
     * @var ExceptionLogFileHelper
     */
    private $adminLogHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestForDisabledAdminQueryLog();

        $this->adminLogHelper = new ExceptionLogFileHelper(Path::join(OX_BASE_PATH, 'log', 'oxadmin.log'));
        $this->adminLogHelper->clearExceptionLogFile();
    }

    /**
     * Verify that shop frontend is ok with enabled admin log.
     *
     * @group adminquerylog
     */
    public function testShopFrontendWithAdminLogEnabled()
    {
        $this->openShop();
        $this->checkForErrors();

        $this->assertEmpty($this->adminLogHelper->getExceptionLogFileContent());
    }

    /**
     * Verify that shop admin is ok with enabled admin log.
     *
     * @group adminquerylog
     */
    public function testShopAdminWithAdminLogEnabled()
    {
        $this->loginAdmin('Master Settings', 'Core Settings');
        $this->adminLogHelper->clearExceptionLogFile();

        $this->openTab("Settings");
        $this->click("link=Other settings");
        $this->assertTextPresent('Mandatory fields in User Registration Form');
        $this->clickAndWait("//form[@id='myedit']/input[@name='save']");

        $logged = $this->adminLogHelper->getExceptionLogFileContent();

        $this->assertStringContainsString('query:', strtolower($logged));
        $this->assertStringContainsString('function: saveshopconfvar', strtolower($logged));
    }

    /**
     * Tests here can only work if blLogChangesInAdmin is set in config.inc.php.
     * Setting the flag in Config during test setup will not work.
     */
    public function skipTestForDisabledAdminQueryLog()
    {
        $factsConfigFile = new FactsConfigFile();
        if (!$factsConfigFile->getVar('blLogChangesInAdmin')) {
            $this->markTestSkipped('Test needs blLogChangesInAdmin flag in config.inc.php set as true');
        }
    }
}
