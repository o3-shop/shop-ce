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
                        'oxid'            => 1003,
                        'oxprice'         => 70.00,
                        'oxpricea'        => 70,
                        'oxpriceb'        => 85,
                        'oxpricec'        => 0,
                          'scaleprices' => [
                            'oxaddabs'     => 75.00,
                            'oxamount'     => 2,
                            'oxamountto'   => 5,
                            'oxartid'      => 1003,
                          ],
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
                1003 => [
                        'base_price'        => '85,00',
                        'price'             => '85,00',
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
