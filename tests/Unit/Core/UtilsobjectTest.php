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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use \oxarticle;

use OxidEsales\Eshop\Core\Exception\SystemComponentException;
use OxidEsales\EshopCommunity\Core\ShopIdCalculator;
use OxidEsales\EshopCommunity\Core\UtilsObject;
use \oxNewDummyUserModule_parent;
use \oxNewDummyUserModule2_parent;
use \oemodulenameoxorder_parent;
use \oxAttribute;
use OxidEsales\Eshop\Core\Registry;
use oxUtilsObject;
use \oxTestModules;
use Psr\Log\Test\TestLogger;

class modOxUtilsObject_oxUtilsObject extends \oxUtilsObject
{
    public function setClassNameCache($aValue)
    {
        parent::$_aInstanceCache = $aValue;
    }

    public function getClassNameCache()
    {
        return parent::$_aInstanceCache;
    }
}

/**
 * Test class for Unit_Core_oxutilsobjectTest::testGetObject() test case
 */
class _oxutils_test
{
    /**
     * Does nothing
     *
     * @param bool $a [optional]
     * @param bool $b [optional]
     * @param bool $c [optional]
     * @param bool $d [optional]
     * @param bool $e [optional]
     *
     * @return null
     */
    public function __construct($a = false, $b = false, $c = false, $d = false, $e = false)
    {
    }
}

class oxModuleUtilsObject extends \oxUtilsObject
{
    public function getActiveModuleChain($aClassChain)
    {
        return parent::getActiveModuleChain($aClassChain);
    }
}

class UtilsobjectTest extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * Tear down the fixture.
     */
    public function tearDown(): void
    {
        oxRemClassModule(\OxidEsales\EshopCommunity\Tests\Unit\Core\modOxUtilsObject_oxUtilsObject::class);

        $oArticle = oxNew('oxArticle');
        $oArticle->delete('testArticle');

        Registry::get("oxConfigFile")->setVar("sShopDir", $this->getConfigParam('sShopDir'));
        Registry::set('logger', getLogger());

        parent::tearDown();
    }

    private function getOrderClassName()
    {
        $orderClassName = 'oxorder';

        if ($this->getConfig()->getEdition() === 'CE') {
            $orderClassName = 'OxidEsales\EshopCommunity\Application\Model\Order';
        }

        return $orderClassName;
    }

    /**
     * Test, that the method getInstance creates the object of the correct current edition namespace.
     */
    public function testEditionSpecificObjectIsCreatedCorrect()
    {
        $utilsObject = \OxidEsales\Eshop\Core\UtilsObject::getInstance();
        $expectedClass = \OxidEsales\Eshop\Core\UtilsObject::class;
        $this->assertEquals($expectedClass, get_class($utilsObject));
    }

    /**
     * Testing oxUtilsObject object creation.
     *
     * @return null
     */
    public function testGetObject()
    {
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1, 2) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1, 2, 3) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1, 2, 3, 4) instanceof _oxutils_test);
    }

    public function testOxNewSettingParameters()
    {
        $oArticle = oxNew('oxarticle', array('aaa' => 'bbb'));

        $this->assertTrue($oArticle instanceof \OxidEsales\EshopCommunity\Application\Model\Article);
        $this->assertTrue(isset($oArticle->aaa));
        $this->assertEquals('bbb', $oArticle->aaa);
    }

    public function testOxNewClassExtendingWhenClassesExists()
    {
        $structure = array(
            'modules' => array(
                'oxNewDummyModule.php' => '<?php class oxNewDummyModule {}',
                'oxNewDummyUserModule.php' => '<?php class oxNewDummyUserModule extends oxNewDummyUserModule_parent {}',
                'oxNewDummyUserModule2.php' => '<?php class oxNewDummyUserModule2 extends oxNewDummyUserModule2_parent {}',
            )
        );
        $vfsStream = $this->getVfsStreamWrapper();
        $vfsStream->createStructure($structure);
        $fakeShopDir = $vfsStream->getRootPath();

        $aModules = array(strtolower('oxNewDummyModule') => 'oxNewDummyUserModule&oxNewDummyUserModule2');

        include_once $fakeShopDir . "/modules/oxNewDummyModule.php";

        $config = $this->getConfig();

        Registry::getUtilsObject()->setModuleVar("aModules", $aModules);
        $config->setConfigParam("aModules", $aModules);

        $configFile = Registry::get("oxConfigFile");
        $realShopDir = $configFile->getVar('sShopDir');
        $configFile->setVar('sShopDir', $fakeShopDir);

        $oNewDummyModule = oxNew("oxNewDummyModule");

        $configFile->setVar('sShopDir', $realShopDir);

        $this->assertTrue($oNewDummyModule instanceof \oxNewDummyModule);
        $this->assertTrue($oNewDummyModule instanceof \oxNewDummyUserModule);
        $this->assertTrue($oNewDummyModule instanceof \oxNewDummyUserModule2);
    }

    public function testOxNewClassExtendingWhenClassesDoesNotExists()
    {
        /**
         * Real error handling on missing files is disabled for the tests, but when the shop tries to include that not
         * existing file we expect an error to be thrown
         */

        $structure = array(
            'modules' => array(
                'oxNewDummyModule.php' => '<?php class oxNewDummyModule {}',
                'oxNewDummyUserModule.php' => '<?php class oxNewDummyUserModule extends oxNewDummyUserModule_parent {}',
            )
        );
        $vfsStream = $this->getVfsStreamWrapper();
        $vfsStream->createStructure($structure);
        $fakeShopDir = $vfsStream->getRootPath();

        $aModules = array(strtolower('oxNewDummyModule') => 'oxNewDummyUserModule&notExistingClass');

        include_once $fakeShopDir . "/modules/oxNewDummyModule.php";

        $config = $this->getConfig();

        Registry::getUtilsObject()->setModuleVar("aModules", $aModules);
        $logger = new TestLogger();
        Registry::set('logger', $logger);
        
        $config->setConfigParam("aModules", $aModules);

        $configFile = Registry::get("oxConfigFile");
        $realShopDir = $configFile->getVar('sShopDir');
        $configFile->setVar('sShopDir', $fakeShopDir);

        $oNewDummyModule = oxNew("oxNewDummyModule");

        $configFile->setVar('sShopDir', $realShopDir);

        $this->assertTrue($oNewDummyModule instanceof \oxNewDummyModule);
        $this->assertTrue($oNewDummyModule instanceof \oxNewDummyUserModule);
        $this->assertFalse($oNewDummyModule instanceof \oxNewDummyUserModule2);

        $this->assertTrue(
            $logger->hasErrorThatContains(
                'Module class notExistingClass not found. Module ID notExistingClass'
            )
        );
    }

    public function testOxNewCreationOfNonExistingClassContainsClassNameInExceptionMessage()
    {
        $this->expectException(SystemComponentException::class);
        $this->expectExceptionMessage('non_existing_class');

        oxNew("non_existing_class");
    }

    /**
     * No real test possible, but at least generated ids should be different
     */
    public function testGenerateUid()
    {
        $id1 = Registry::getUtilsObject()->generateUid();
        $id2 = Registry::getUtilsObject()->generateUid();
        $this->assertNotEquals($id1, $id2);
    }

    public function testResetInstanceCacheSingle()
    {
        $oTestInstance = new modOxUtilsObject_oxUtilsObject();
        $aInstanceCache = array("oxArticle" => oxNew('oxArticle'), "oxattribute" => new oxAttribute());
        $oTestInstance->setClassNameCache($aInstanceCache);

        $oTestInstance->resetInstanceCache("oxArticle");

        $aGotInstanceCache = $oTestInstance->getClassNameCache();

        $this->assertEquals(1, count($aGotInstanceCache));
        $this->assertTrue($aGotInstanceCache["oxattribute"] instanceof \OxidEsales\EshopCommunity\Application\Model\Attribute);
    }

    public function testResetInstanceCacheAll()
    {
        $oTestInstance = new modOxUtilsObject_oxUtilsObject();
        $aInstanceCache = array("oxArticle" => oxNew('oxArticle'), "oxattribute" => new oxAttribute());
        $oTestInstance->setClassNameCache($aInstanceCache);

        $oTestInstance->resetInstanceCache();

        $aGotInstanceCache = $oTestInstance->getClassNameCache();

        $this->assertEquals(0, count($aGotInstanceCache));
    }

    public function testGetClassName_classExist_moduleClassReturn()
    {
        $sClassName = 'oxorder';
        $sClassNameWhichExtends = $sClassNameExpect = 'oemodulenameoxorder';
        $oUtilsObject = $this->_prepareFakeModule($sClassName, $sClassNameWhichExtends);

        $this->assertSame($sClassNameExpect, $oUtilsObject->getClassName($sClassName));
    }

    public function testGetClassName_classNotExist_originalClassReturn()
    {
        $sClassName = 'oxorder';
        $sClassNameExpect = 'oxorder';

        $sClassNameWhichExtends = 'oemodulenameoxorder_different2';
        $oUtilsObject = $this->prepareFakeModuleNonExistentClass($sClassName, $sClassNameWhichExtends);
        $this->assertSame($sClassNameExpect, $oUtilsObject->getClassName($sClassName));
        $expectedExceptionClass = SystemComponentException::class;
        $this->assertLoggedException($expectedExceptionClass);
    }
    
    
    public function testGetClassName_classNotExistDoNotDisableModuleOnError_errorThrow()
    {
        $sClassName = 'oxorder';
        $sClassNameWhichExtends = 'oemodulenameoxorder_different4';
        $oUtilsObject = $this->_prepareFakeModule($sClassName, $sClassNameWhichExtends);

        $oUtilsObject->getClassName($sClassName);
    }

    public function testUtilsObjectConstructedWithCEShopId()
    {
        $expectedShopId = ShopIdCalculator::BASE_SHOP_ID;

        $utilsObject = new UtilsObject();
        $realShopId = $utilsObject->getShopId();

        $this->assertSame($expectedShopId, $realShopId);
    }

    private function _prepareFakeModule($class, $extension)
    {
        $wrapper = $this->getVfsStreamWrapper();
        Registry::get("oxConfigFile")->setVar("sShopDir", $wrapper->getRootPath());
        $wrapper->createStructure(array(
            'modules' => array(
                $extension . '.php' => "<?php class $extension extends {$extension}_parent {}"
            )
        ));

        $oUtilsObject = Registry::getUtilsObject();
        $oUtilsObject->setModuleVar('aModules', array($class => $extension));

        return $oUtilsObject;
    }

    /**
     * Make a module, which classname is not the expected one. I.e. class name does not match file name.
     * The parent class name matches the expections i.e. {$extension}_parent
     *
     * @param $class
     * @param $extension
     *
     * @return \OxidEsales\Eshop\Core\UtilsObject
     */
    private function prepareFakeModuleNonExistentClass($class, $extension)
    {
        $wrapper = $this->getVfsStreamWrapper();
        Registry::get("oxConfigFile")->setVar("sShopDir", $wrapper->getRootPath());
        $wrapper->createStructure(array(
            'modules' => array(
                $extension . '.php' => "<?php class {$extension}NonExistent extends {$extension}_parent {}"
            )
        ));

        $oUtilsObject = Registry::getUtilsObject();
        $oUtilsObject->setModuleVar('aModules', array($class => $extension));

        return $oUtilsObject;
    }
}
