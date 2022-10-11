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

/**
 * Class UnifiedNameSpaceClassMapTest
 *
 * @group module
 * @package Integration\Modules
 */
class UnifiedNameSpaceClassMapTest extends BaseModuleTestCase
{
    /**
     * @var Environment The helper object for the environment.
     */
    protected $environment = null;

    /**
     * Standard set up method. Calls parent first.
     */
    public function setup(): void
    {
        parent::setUp();

        $this->environment = new Environment();
    }

    /**
     * Standard tear down method. Calls parent last.
     */
    public function tearDown(): void
    {
        $this->environment->clean();

        parent::tearDown();
    }

    /**
     * Data provider for the method testUnifiedNamespaceModules.
     *
     * @return array The inputs for the method testUnifiedNamespaceModules.
     */
    public function dataProviderForTestUnifiedNamespaceModules()
    {
        return array(
            array(
                'modulesToActivate'          => array('unifiednamespace_module1'),
                'expectedInheritanceChain'   => array(
                    'Test1ContentController',
                    'OxidEsales\Eshop\Application\Controller\ContentController',
                    'OxidEsales\EshopCommunity\Application\Controller\ContentController',
                    'OxidEsales\Eshop\Application\Controller\FrontendController',
                    'OxidEsales\EshopCommunity\Application\Controller\FrontendController',
                    'OxidEsales\Eshop\Core\Controller\BaseController',
                    'OxidEsales\EshopCommunity\Core\Controller\BaseController',
                    'OxidEsales\Eshop\Core\Base',
                    'OxidEsales\EshopCommunity\Core\Base',
                )
                ,
                'expectedTitle'              => 'Impressum - Module_1_Controller - Module_1_Model'
            ),
            array(
                'modulesToActivate'          => array('unifiednamespace_module1', 'unifiednamespace_module2'),
                'expectedInheritanceChain'   => array(
                    'Test2ContentController',
                    'Test1ContentController',
                    'OxidEsales\Eshop\Application\Controller\ContentController',
                    'OxidEsales\EshopCommunity\Application\Controller\ContentController',
                    'OxidEsales\Eshop\Application\Controller\FrontendController',
                    'OxidEsales\EshopCommunity\Application\Controller\FrontendController',
                    'OxidEsales\Eshop\Core\Controller\BaseController',
                    'OxidEsales\EshopCommunity\Core\Controller\BaseController',
                    'OxidEsales\Eshop\Core\Base',
                    'OxidEsales\EshopCommunity\Core\Base',
                ),
                'expectedTitle'              => 'Impressum - Module_1_Controller - Module_1_Model - Module_2_Controller'
            ),
            array(
                'modulesToActivate'          => array('unifiednamespace_module1', 'unifiednamespace_module2', 'unifiednamespace_module3'),
                'expectedInheritanceChain'   => array(
                    'Test2ContentController',
                    'Test1ContentController',
                    'OxidEsales\Eshop\Application\Controller\ContentController',
                    'OxidEsales\EshopCommunity\Application\Controller\ContentController',
                    'OxidEsales\Eshop\Application\Controller\FrontendController',
                    'OxidEsales\EshopCommunity\Application\Controller\FrontendController',
                    'OxidEsales\Eshop\Core\Controller\BaseController',
                    'OxidEsales\EshopCommunity\Core\Controller\BaseController',
                    'OxidEsales\Eshop\Core\Base',
                    'OxidEsales\EshopCommunity\Core\Base',
                ),
                'expectedTitle'              => 'Impressum - Module_1_Controller - Module_3_Model - Module_2_Controller'
            )
        );
    }

    /**
     * Test, that the overwriting for the modules and their chain works.
     *
     * @dataProvider dataProviderForTestUnifiedNamespaceModules
     */
    public function testUnifiedNamespaceModules($modulesToActivate, $expectedInheritanceChain, $expectedInheritanceChainPE, $expectedInheritanceChainEE, $expectedTitle)
    {
        foreach ($modulesToActivate as $moduleId) {
            $this->installAndActivateModule($moduleId);
        }

        $createdContentController = oxNew('Content');

        $expectedInheritanceChainEdition = $expectedInheritanceChain;

        if ($this->getTestConfig()->getShopEdition() == 'PE') {
            $expectedInheritanceChainEdition = $expectedInheritanceChainPE;
        }
        if ($this->getTestConfig()->getShopEdition() == 'EE') {
            $expectedInheritanceChainEdition = $expectedInheritanceChainEE;
        }

        $this->assertObjectHasInheritances($createdContentController, $expectedInheritanceChainEdition);

        $resultTitle = $createdContentController->getTitle();
        $this->assertSame($expectedTitle, $resultTitle);
    }

    /**
     * Assert, that the given object has the expected inheritance chain.
     *
     * @param object $objectUnderTest          The object, which should have the given inheritance chain.
     * @param array  $expectedInheritanceChain The inheritance chain we expect.
     */
    private function assertObjectHasInheritances($objectUnderTest, $expectedInheritanceChain)
    {
        $classParents = array_keys(class_parents($objectUnderTest));
        $resultInheritanceChain = array_merge(array(get_class($objectUnderTest)), $classParents);

        $this->assertSame($expectedInheritanceChain, $resultInheritanceChain, 'The given object does not have the expected inheritance chain!');
    }
}
