<?php

/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 1
 * VAT info: 20%
 * Currency rate: 1.0
 * Discounts: -
 * Short description: Brutto-Brutto user group Price B,
 * Test case is moved from selenium test "testFrontendPriceB"
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
                'oxid' => '_testUserB',
                'oxactive' => 1,
                'oxusername' => 'groupBUser',
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
                        'base_price'        => '45,00',
                        'price'             => '45,00',
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                        'blOverrideZeroABCPrices' => true,
                        'dDefaultVAT' => 20,
                ],
                'activeCurrencyRate' => 1,
        ],
];
