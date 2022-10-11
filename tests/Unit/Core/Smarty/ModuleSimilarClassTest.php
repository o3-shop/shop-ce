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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Smarty;

use OxidEsales\Eshop\Core\Registry;
use Psr\Log\Test\TestLogger;

/**
 * @group module
 * @package Unit\Core\Smarty
 */
class ModuleSimilarClassTest extends \OxidTestCase
{
    
    protected function tearDown(): void
    {
        Registry::set('logger', getLogger());

        parent::tearDown();
    }
    /**
     * test when overloading class in module with similar name as other module
     */
    public function testModuleSimilarName()
    {
        $wrapper = $this->getVfsStreamWrapper();
        Registry::get("oxConfigFile")->setVar("sShopDir", $wrapper->getRootPath());
        $wrapper->createStructure(array(
            'modules' => array(
                'testmodulesimilarname.php' => "<?php
                    class testModuleSimilarName extends testModuleSimilarName_parent {
                        public function sayHi() {
                            return \"Hi!\";
                        }
                    }"
            )
        ));

        $extensions = array('oxbasketitem' => 'testmodulesimilarnameitem', 'oxbasket' => 'testmodulesimilarname');
        \OxidEsales\Eshop\Core\Registry::getUtilsObject()->setModuleVar('aModules', $extensions);

        $oTestMod = oxNew('oxBasket');
        $this->assertEquals("Hi!", $oTestMod->sayHi());
    }

    /**
     * test catching exception when calling not existent similar module
     */
    public function testModuleSimilarName_ClassNotExist()
    {
        $wrapper = $this->getVfsStreamWrapper();
        Registry::get("oxConfigFile")->setVar("sShopDir", $wrapper->getRootPath());
        $wrapper->createStructure(array(
            'modules' => array(
                'testmodulesimilarname.php' => "<?php
                    class testModuleSimilarName extends testModuleSimilarName_parent {
                        public function sayHi() {
                            return \"Hi!\";
                        }
                    }"
            )
        ));
        $logger = new TestLogger();
        Registry::set('logger', $logger);

        /**
         * Real error handling on missing files is disabled for the tests, but when the shop tries to include that not
         * existing file we expect an error to be thrown
         */
        $extensions = array('oxbasketitem' => 'testmodulesimilar', 'oxbasket' => 'testmodulesimilarname');
        \OxidEsales\Eshop\Core\Registry::getUtilsObject()->setModuleVar('aModules', $extensions);

        $this->expectException(\OxidEsales\Eshop\Core\Exception\SystemComponentException::class);
        oxNew('testmodulesimilar');
    }
}
