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

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;

class ModuleInstallerTest extends BaseModuleTestCase
{
    /**
     * @var string The ID of the module we use in this test.
     */
    const MODULE_ID = 'metadata_controllers_feature';

    /**
     * @var array The controllers as defined in the module we are using to check the wished behaviour.
     *
     * Keep this in sync with content of test module metadata_controllers_feature metadata.php!
     */
    const MODULE_CONTROLLERS = [self::MODULE_ID => [
        'metadata_controllers_feature-controllers-id-1' => 'metadata_controllers_feature-controllers-value-1',
        'metadata_controllers_feature-controllers-id-2' => 'metadata_controllers_feature-controllers-value-2'
    ]];

    /**
     * Test, that the module activation adds the controllers of the module to the oxconfig.
     */
    public function testModuleInstallerActivateAddsControllersToOxConfig()
    {
        $this->activate();

        $this->assertEquals(
            self::MODULE_CONTROLLERS,
            $this->fetchOxConfigModuleControllers(),
            'While module activation were not added the expected controllers to the module controller map!');
    }

    /**
     * Test, that the module activation removes the controllers of the module from the oxconfig.
     */
    public function testModuleInstallerActivateDeletesControllersFromConfig()
    {
        $module = $this->activate();

        $beforeDeactivation = $this->fetchOxConfigModuleControllers();

        $this->deactivateModule($module, self::MODULE_ID);

        $afterDeactivation = $this->fetchOxConfigModuleControllers();
        $actualDifference = array_diff($beforeDeactivation, $afterDeactivation);

        $this->assertEquals(self::MODULE_CONTROLLERS, $actualDifference, 'While the module deactivation were not removed the expected controllers from the module controller map!');
    }

    /**
     * Create a module object and activate it.
     *
     * @return \oxModule The now activated module.
     */
    private function activate()
    {
        $module = oxNew(Module::class);

        $this->installAndActivateModule(self::MODULE_ID);

        return $module;
    }

    /**
     * Fetch the actual content of the module controllers array from the oxconfig table.
     *
     * @return array The module controllers from the oxconfig table.
     */
    private function fetchOxConfigModuleControllers()
    {
        $config = Registry::getConfig();

        return is_array($config->getConfigParam('aModuleControllers')) ? $config->getConfigParam('aModuleControllers') : [];
    }
}
