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
                        'oxid'            => 1000,
                        'oxprice'         => 50.00,
                        'oxpricea'        => 35,
                        'oxpriceb'        => 45,
                        'oxpricec'        => 55,
                ],
        ],
        'user' => [
                'oxid' => '_testUserA',
                'oxactive' => 1,
                'oxusername' => 'groupAUser',
        ],

        'group' => [
                0 => [
                        'oxid' => 'oxidpricea',
                        'oxactive' => 1,
                        'oxtitle' => 'Price A',
                        'oxobject2group' => [ '_testUserA' ],
                ],
                1 => [
                        'oxid' => 'oxidpriceb',
                        'oxactive' => 1,
                        'oxtitle' => 'Price B',
                        'oxobject2group' => [ '_testUserB' ],
                ],
                2 => [
                        'oxid' => 'oxidpricec',
                        'oxactive' => 1,
                        'oxtitle' => 'Price C',
                        'oxobject2group' => [ '_testUserC' ],
                ],
        ],
        'expected' => [
                1000 => [
                        'base_price'        => '35,00',
                        'price'             => '35,00',
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                        'dDefaultVAT' => 20,
                        'blOverrideZeroABCPrices' => true,
                ],
                'activeCurrencyRate' => 1,
        ],
];
