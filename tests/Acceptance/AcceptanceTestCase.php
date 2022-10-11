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

namespace OxidEsales\EshopCommunity\Tests\Acceptance;

use \OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Edition\EditionSelector;
use OxidEsales\TestingLibrary\TestSqlPathProvider;

abstract class AcceptanceTestCase extends \OxidEsales\TestingLibrary\AcceptanceTestCase
{
    protected $preventModuleVersionNotify = true;

    /**
     * Sets up default environment for tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->activateTheme('azure');

        //Suppress check for new module versions on every admin login
        if ($this->preventModuleVersionNotify) {
            $aParams = array("type" => "bool", "value" => true);
            $this->callShopSC("oxConfig", null, null, array('preventModuleVersionNotify' => $aParams));
        }

        $this->activateTheme('azure');
        $this->clearCache();
    }

    /**
     * @inheritdoc
     */
    public function setUpTestsSuite($testSuitePath)
    {
        parent::setUpTestsSuite($testSuitePath);
    }

    /**
     * Adds tests sql data to database.
     *
     * @param string $sTestSuitePath
     */
    public function addTestData($sTestSuitePath)
    {
        parent::addTestData($sTestSuitePath);

        $this->resetConfig();
    }

    /**
     * Reset config to have newest information from database.
     * SQL files might contain configuration changes.
     * Base object has static cache for Config object.
     * Config object has cache for database configuration.
     *
     */
    private function resetConfig()
    {
        $config = Registry::getConfig();
        $config->reinitialize();
        /** Reset static variable in oxSuperCfg class, which is base class for every class. */
        $config->setConfig($config);
    }
}
