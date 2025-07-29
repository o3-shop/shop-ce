<?php

/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 1
 * VAT info: 20%
 * Currency rate: 1.0
 * Discounts: -
 * Short description: Brutto-Brutto user group Price C,
 * Test case is moved from selenium test "testFrontendPriceC"
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'            => 'variantDiscountTestParentArticle',
                        'oxprice'         => 50.00,
                ],
                1 => [
                        'oxid'            => 'variantDiscountTestChildArticle',
                        'oxparentid'      => 1000,
                        'oxprice'         => 50.00,
                ],
        ],
        'categories' => [
                0 =>  [
                        'oxid'       => 'variantDiscountTestCategory',
                        'oxparentid' => 'oxrootid',
                        'oxshopid'   => 1,
                        'oxactive'   => 1,
                        'oxarticles' => [ 'variantDiscountTestChildArticle' ],
                ],
        ],
        'discounts' => [
                0 => [
                        'oxid'         => 'variantDiscountDiscountId',
                        'oxaddsum'     => 20,
                        'oxaddsumtype' => 'abs',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxcategories' => [ 'variantDiscountTestCategory' ],
                        'oxsort'       => 10,
                ],
        ],
        'expected' => [
            1001 => [
                        'base_price'        => '50,00',
                        'price'             => '30,00',
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                ],
                'activeCurrencyRate' => 1,
        ],
];
