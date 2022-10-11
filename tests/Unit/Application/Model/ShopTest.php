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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\EshopCommunity\Core\I18n;

use \oxDb;
use \oxField;

class ShopTest extends \OxidTestCase
{
    public function testStructure()
    {
        $oShop = oxNew('oxShop');
        $this->assertTrue($oShop instanceof \OxidEsales\EshopCommunity\Core\Model\MultiLanguageModel);
        $this->assertEquals('oxshops', $oShop->getCoreTableName());
    }

    public function testIsProductiveMode_ProductiveMode()
    {
        $oShop = oxNew('oxShop');
        $oShop->setId(10);
        $oShop->oxshops__oxproductive = new oxField(true);
        $oShop->oxshops__oxactive = new oxField(1);
        $oShop->oxshops__oxname = new oxField('Test shop');
        $oShop->save();

        $oShop = oxNew('oxShop');
        $oShop->load(10);

        $this->assertTrue($oShop->isProductiveMode());
    }

    public function testIsProductiveMode_nonProductiveMode()
    {
        $oShop = oxNew('oxShop');
        $oShop->setId(12);
        $oShop->oxshops__oxproductive = new oxField(false);
        $oShop->oxshops__oxactive = new oxField(1);
        $oShop->oxshops__oxname = new oxField('Test shop');
        $oShop->save();

        $oShop = oxNew('oxShop');
        $oShop->load(12);

        $this->assertFalse($oShop->isProductiveMode());
    }

    /**
     * Provides parameters and expected results for testMakeViewQuery
     */
    public function makeViewQueryParamProvider()
    {
        $sFieldsMultilang = 'OXID, OXTITLE, OXTITLE_1';
        $sFields = 'OXID, OXTITLE';

        $aMockedFunctionReturns = array(
            '_getViewSelectMultilang' => $sFieldsMultilang,
            '_getViewSelect'          => $sFields,
        );

        return array(
            array('oxarticles', null, $aMockedFunctionReturns,
                  'CREATE OR REPLACE SQL SECURITY INVOKER VIEW `oxv_oxarticles` AS SELECT ' . $sFieldsMultilang . ' FROM oxarticles'), // default
            array('oxarticles', 'de', $aMockedFunctionReturns,
                  'CREATE OR REPLACE SQL SECURITY INVOKER VIEW `oxv_oxarticles_de` AS SELECT ' . $sFields . ' FROM oxarticles'),
        );
    }

    /**
     * Check all the variations of oxShop::createViewQuery()
     *
     * @dataProvider makeViewQueryParamProvider
     */
    public function testMakeViewQuery($sTable, $sLang, $aMockedFunctionReturns, $sQuery)
    {
        /** @var oxShop|PHPUnit\Framework\MockObject\MockObject $oShop */
        $oShop = $this->getMock(\OxidEsales\Eshop\Application\Model\Shop::class, array_keys($aMockedFunctionReturns));
        foreach ($aMockedFunctionReturns as $sFunction => $sReturnValue) {
            $oShop->expects($this->any())->method($sFunction)->will($this->returnValue($sReturnValue));
        }
        $oShop->createViewQuery($sTable, array(0 => $sLang));
        $aQueries = $oShop->getQueries();
        $this->assertEquals(rtrim($sQuery), rtrim($aQueries[0]));
    }

    /**
     * Testing oxshop::generateViews() for removing old unused 'oxv_*' views.
     */
    public function testGenerateViewsRemovingUnnecessaryViews()
    {
        $database = oxDb::getDb();

        // creating view which has to be removed
        $database->execute('CREATE OR REPLACE SQL SECURITY INVOKER VIEW `oxv_oxshops_zz-2015` AS SELECT * FROM oxshops');

        $shop = oxNew(Shop::class);
        $this->assertTrue($shop->generateViews(false, null));

        $databaseMetaDataHandler = oxNew('oxDbMetaDataHandler');
        $incorrectViewExists = $databaseMetaDataHandler->tableExists('oxv_oxshops_zz-2015');
        $this->assertFalse($incorrectViewExists, 'Old view "oxv_oxshops_zz" is not removed');
    }

    /**
     * Testing ShopViewValidator::_getAllViews().
     */
    public function testShowViewTables()
    {
        $shopViewValidator = oxNew('oxShopViewValidator');

        $invalidViews = $shopViewValidator->getInvalidViews();

        $this->assertNotEmpty($invalidViews);
        $this->assertNotContains('oxvouchers', $invalidViews);
    }

    /**
     * Test call to getMultishopTables when it's not set anywhere
     */
    public function testGetMultishopTablesDefaultNotSet()
    {
        $oShop = oxNew('oxShop');
        $this->assertEquals(array(), $oShop->getMultiShopTables());
    }

    /**
     * Test call to getMultishopTables when it's set
     */
    public function testGetMultishopTablesWhenSet()
    {
        $oShop = oxNew('oxShop');
        $oShop->setMultiShopTables(array('table1', 'table2'));
        $this->assertEquals(array('table1', 'table2'), $oShop->getMultiShopTables());
    }

    /**
     * Test addQuery method when adding 1 query, and than another 1
     */
    public function testAddQuery()
    {
        $oShop = oxNew('oxShop');
        $oShop->addQuery('query');
        $this->assertEquals(array('query'), $oShop->getQueries());

        $oShop->addQuery('anotherquery');
        $this->assertEquals(array('query', 'anotherquery'), $oShop->getQueries());
    }

    /**
     * Test getQueries method when no query is added
     */
    public function testGetQueriesNoQueriesAdded()
    {
        $oShop = oxNew('oxShop');
        $this->assertEquals(array(), $oShop->getQueries());
    }

    /**
     * Test getQueries method when queries are added
     */
    public function testGetQueriesQueriesAdded()
    {
        $oShop = oxNew('oxShop');
        $oShop->setQueries(array('query', 'query2'));
        $this->assertEquals(array('query', 'query2'), $oShop->getQueries());
    }

    /**
     * Test delete function when oxid is null
     */
    public function testDeleteWhenOxidIsNull()
    {
        $oShop = oxNew('oxShop');
        $this->assertFalse($oShop->delete(null));
    }
}
