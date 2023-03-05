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
use oxOrderArticle;
use oxRegistry;

require_once __DIR__. '/BaseTestCase.php';

/**
 * Basket price calculation test
 * Check:
 * - Article unit & total price
 * - Discount amounts
 * - Vat amounts
 * - Additional fees (wrapping, payment, delivery)
 * - Vouchers
 * - Totals (grand, netto, brutto)
 */
class BasketTest extends BaseTestCase
{
    /** @var array Test case directory array */
    private $testCaseDirectories = array(
        "testcases/basket",
        // "testcases/databomb",
    );

    /** @var string Specified test cases (optional) */
    private $testCases = array(
        //"testCase.php",
    );

    /**
     * Initialize the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reset();
    }

    /**
     * Tear down the fixture.
     */
    protected function tearDown(): void
    {
        $this->addTableForCleanup('oxobject2category');
        parent::tearDown();
    }

    /**
     * Resets db tables, required configs
     */
    protected function reset()
    {
        $database = oxDb::getDb();
        $config = oxRegistry::getConfig();
        $database->execute("TRUNCATE oxarticles");
        $database->execute("TRUNCATE oxcategories");
        $database->execute("TRUNCATE oxdiscount");
        $database->execute("TRUNCATE oxobject2discount");
        $database->execute("TRUNCATE oxwrapping");
        $database->execute("TRUNCATE oxdelivery");
        $database->execute("TRUNCATE oxdel2delset");
        $database->execute("TRUNCATE oxobject2payment");
        $database->execute("TRUNCATE oxvouchers");
        $database->execute("TRUNCATE oxvoucherseries");
        $database->execute("TRUNCATE oxobject2delivery");
        $database->execute("TRUNCATE oxobject2category");
        $database->execute("TRUNCATE oxdeliveryset");
        $database->execute("TRUNCATE oxuser");
        $database->execute("TRUNCATE oxprice2article");
        $config->setConfigParam("blShowVATForDelivery", true);
        $config->setConfigParam("blShowVATForPayCharge", true);
    }

    /**
     * Basket startup data and expected calculations results
     *
     * @return array
     */
    public function providerBasketCalculation()
    {
        return $this->getTestCases($this->testCaseDirectories, $this->testCases);
    }

    /**
     * Tests special basket calculations
     *
     * @dataProvider providerBasketCalculation
     *
     * @param array $testCase
     */
    public function testBasketCalculation($testCase)
    {
        if (isset($testCase['skipped']) && $testCase['skipped'] == 1) {
            $this->markTestSkipped("testcase is skipped");
        }
        // gathering data arrays
        $expected = $testCase['expected'];

        //if not finished testing data skip test
        if (empty($expected)) {
            $this->markTestSkipped("skipping test case due invalid data provided");
        }

        // load calculated basket from provided data
        $basketConstruct = new BasketConstruct();
        $basket = $basketConstruct->calculateBasket($testCase);

        // check basket item list
        $expectedArticles = $expected['articles'];
        $basketItemList = $basket->getContents();

        $this->assertEquals(count($expectedArticles), count($basketItemList), "Expected basket articles amount doesn't match actual");

        if ($basketItemList) {
            foreach ($basketItemList as $key => $basketItem) {
                /** @var oxOrderArticle $basketItem */
                $articleId = $basketItem->getArticle()->getID();
                $this->assertEquals($expectedArticles[$articleId][0], $basketItem->getFUnitPrice(), "Unit price of article id {$articleId}");
                $this->assertEquals($expectedArticles[$articleId][1], $basketItem->getFTotalPrice(), "Total price of article id {$articleId}");
            }
        }

        // Total discounts
        $expectedDiscounts = $expected['totals']['discounts'] ?? null;
        $expectedDiscountCount = (is_array($expectedDiscounts)) ? count($expectedDiscounts) : 0;
        $productDiscounts = $basket->getDiscounts();
        $productDiscountsCount = (is_array($productDiscounts)) ? count($productDiscounts) : 0;
        $this->assertEquals($expectedDiscountCount, $productDiscountsCount, "Expected basket discount amount doesn't match actual");
        if (!empty($expectedDiscounts)) {
            foreach ($productDiscounts as $discount) {
                $this->assertEquals($expectedDiscounts[$discount->sOXID], $discount->fDiscount, "Total discount of {$discount->sOXID}");
            }
        }

        // Total vats
        $expectedVats = $expected['totals']['vats'] ?? null;
        $expectedVatsCount = (is_array($expectedVats)) ? count($expectedVats) : 0;
        $productVats = $basket->getProductVats();
        $productVatsCount = (is_array($productVats)) ? count($productVats) : 0;
        $this->assertEquals($expectedVatsCount, $productVatsCount, "Expected basket different vat amount doesn't match actual");
        if (!empty($expectedVats)) {
            foreach ($productVats as $percent => $sum) {
                $this->assertEquals($expectedVats[$percent], $sum, "Total Vat of {$percent}%");
            }
        }

        // Wrapping costs
        $expectedWrappings = $expected['totals']['wrapping'] ?? null;
        if (!empty($expectedWrappings)) {
            $this->assertEquals(
                $expectedWrappings['brutto'],
                $basket->getFWrappingCosts(),
                "Total wrappings brutto price"
            );
            $this->assertEquals(
                $expectedWrappings['netto'] ?? null,
                $basket->getWrappCostNet(),
                "Total wrappings netto price"
            );
            $this->assertEquals(
                $expectedWrappings['vat'] ?? null,
                $basket->getWrappCostVat(),
                "Total wrappings vat price"
            );
        }

        // Giftcard costs
        $expectedCards = $expected['totals']['giftcard'] ?? null;
        if (!empty($expectedCards)) {
            $this->assertEquals(
                $expectedCards['brutto'],
                $basket->getFGiftCardCosts(),
                "Total giftcard brutto price"
            );
            $this->assertEquals(
                $expectedCards['netto'] ?? null,
                $basket->getGiftCardCostNet(),
                "Total giftcard netto price"
            );
            $this->assertEquals(
                $expectedCards['vat'] ?? null,
                $basket->getGiftCardCostVat(),
                "Total giftcard vat price"
            );
        }

        // Delivery costs
        $expectedDeliveryCosts = $expected['totals']['delivery'] ?? null;
        if (!empty($expectedDeliveryCosts)) {
            $this->assertEquals(
                $expectedDeliveryCosts['brutto'],
                number_format(round($basket->getDeliveryCosts(), 2), 2, ',', '.'),
                "Delivery total brutto price"
            );
            $this->assertEquals(
                $expectedDeliveryCosts['netto'] ?? null,
                $basket->getDelCostNet(),
                "Delivery total netto price"
            );
            $this->assertEquals(
                $expectedDeliveryCosts['vat'] ?? null,
                $basket->getDelCostVat(),
                "Delivery total vat price"
            );
        }

        // Payment costs
        $expectedPayments = $expected['totals']['payment'] ?? null;
        if (!empty($expectedPayments)) {
            $this->assertEquals(
                $expectedPayments['brutto'] ?? null,
                number_format(round($basket->getPaymentCosts(), 2), 2, ',', '.'),
                "Payment total brutto price"
            );
            $this->assertEquals(
                $expectedPayments['netto'] ?? null,
                $basket->getPayCostNet(),
                "Payment total netto price"
            );
            $this->assertEquals(
                $expectedPayments['vat'] ?? null,
                $basket->getPayCostVat(),
                "Payment total vat price"
            );
        }

        // Vouchers
        $expectedVouchers = $expected['totals']['voucher'] ?? null;
        if (!empty($expectedVouchers)) {
            $this->assertEquals(
                $expectedVouchers['brutto'],
                number_format(round($basket->getVoucherDiscValue(), 2), 2, ',', '.'),
                "Voucher total discount brutto"
            );
        }

        // Total netto & brutto, grand total
        $this->assertEquals($expected['totals']['totalNetto'], $basket->getProductsNetPrice(), "Total Netto");
        $this->assertEquals($expected['totals']['totalBrutto'], $basket->getFProductsPrice(), "Total Brutto");
        $this->assertEquals($expected['totals']['grandTotal'], $basket->getFPrice(), "Grand Total");
    }
}
