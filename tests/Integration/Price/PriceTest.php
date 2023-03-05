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
namespace OxidEsales\EshopCommunity\Tests\Integration\Price;

use oxDb;

require_once __DIR__. '/BasketConstruct.php';

/**
 * Shop price calculation test
 * Check:
 * - Price
 * - Unit price
 * - Total price
 * - Amount price info
 */
class PriceTest extends BaseTestCase
{
    /** @var array Test case directories. */
    private $testCasesDirectory = "testcases/price";

    /** @var array If specified, runs only these test cases. */
    private $testCases = array();

    /**
     * Initialize the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }

    /**
     * Tear down the fixture.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Resets db tables, required configs
     */
    protected function resetDatabase()
    {
        $database = oxDb::getDb();
        $database->execute("TRUNCATE oxarticles");
        $database->execute("TRUNCATE oxdiscount");
        $database->execute("TRUNCATE oxobject2discount");
        $database->execute("TRUNCATE oxprice2article");
        $tables = $database->getCol("SHOW TABLES");
        if (in_array('oxfield2shop', $tables)) {
            $database->execute("TRUNCATE oxfield2shop");
        }
        $database->execute("TRUNCATE oxuser");
        $database->execute("TRUNCATE oxobject2group");
        $database->execute("TRUNCATE oxgroups");
    }

    /**
     * Order startup data and expected calculations results
     *
     * @return array
     */
    public function providerPrice()
    {
        $directoriesToScan = array($this->testCasesDirectory . '/community/');
        return $this->getTestCases($directoriesToScan, $this->testCases);
    }

    /**
     * Tests price calculation
     *
     * @dataProvider providerPrice
     *
     * @param array $aTestCase
     */
    public function testPrice($aTestCase)
    {
        if (isset($aTestCase['skipped']) && $aTestCase['skipped'] == 1) {
            $this->markTestSkipped("testcase is skipped");
        }

        // gather data from test case
        $aExpected = $aTestCase['expected'];

        // load calculated basket from provided data
        $oConstruct = new BasketConstruct();
        // create shops
        $iActiveShopId = $oConstruct->createShop($aTestCase['shop'] ?? null);

        // create user if specified
        $oUser = $oConstruct->createObj($aTestCase['user'] ?? null, "oxuser", "oxuser");

        // create group and assign
        $oConstruct->createGroup($aTestCase['group'] ?? null);

        // user login
        if ($oUser) {
            $oUser->load($oUser->getId());
            $oUser->login($aTestCase['user']['oxusername'], '');
        }

        // setup options
        $oConstruct->setOptions($aTestCase['options']);

        // create categories
        $oConstruct->setCategories($aTestCase['categories'] ?? null);

        // create articles
        $articlesData = $oConstruct->getArticles($aTestCase['articles']);

        // apply discounts
        $oConstruct->setDiscounts($aTestCase['discounts'] ?? null);

        // set active shop
        if ($iActiveShopId != 1) {
            $oConstruct->setActiveShop($iActiveShopId);
        }

        // iteration through expectations
        foreach ($articlesData as $articleData) {
            $expected = $aExpected[$articleData['id']] ?? null;
            if (empty($expected)) {
                continue;
            }
            $article = oxNew('oxArticle');
            $article->load($articleData['id']);

            $this->assertEquals($expected['base_price'], $this->getFormatted($article->getBasePrice()), "Base Price of article #{$articleData['id']}");
            $this->assertEquals($expected['price'], $article->getFPrice(), "Price of article #{$articleData['id']}");

            if (isset($expected['rrp_price'])) {
                $this->assertEquals($expected['rrp_price'], $article->getFTPrice(), "RRP price of article #{$articleData['id']}");
            }

            if (isset($expected['unit_price'])) {
                $this->assertEquals($expected['unit_price'], $article->getFUnitPrice(), "Unit Price of article #{$articleData['id']}");
            }

            if (isset($expected['is_range_price'])) {
                $this->assertEquals($expected['is_range_price'], $article->isRangePrice(), "Is range price check of article #{$articleData['id']}");
            }

            if (isset($expected['min_price'])) {
                $this->assertEquals($expected['min_price'], $article->getFMinPrice(), "Min price of article #{$articleData['id']}");
            }

            if (isset($expected['var_min_price'])) {
                $this->assertEquals($expected['var_min_price'], $article->getFVarMinPrice(), "Var min price of article #{$articleData['id']}");
            }

            if (isset($expected['show_rrp'])) {
                $blShowRPP = false;
                if ($article->getTPrice() && $article->getTPrice()->getPrice() > $article->getPrice()->getPrice()) {
                    $blShowRPP = true;
                }
                $this->assertEquals($expected['show_rrp'], $blShowRPP, "RRP price showing of article #{$articleData['id']}");
            }
        }
    }

    /**
     * Get formatted price
     *
     * @param double $dPrice
     *
     * @return double
     */
    protected function getFormatted($dPrice)
    {
        return number_format(round($dPrice, 2), 2, ',', '.');
    }

    /**
     * Truncates specified table
     *
     * @param string $sTable table name
     */
    protected function truncateTable($sTable)
    {
        oxDb::getDb()->execute("TRUNCATE {$sTable}");
    }
}
