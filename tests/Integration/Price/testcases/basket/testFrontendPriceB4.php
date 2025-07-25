<?php

/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 1
 * VAT info: 20%
 * Currency rate: 1.0
 * Discounts: -
 * Short description: Brutto-Brutto user group Price B, Checking option "Use normal article price instead of zero A, B, C price" is OFF
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
                        'amount'          => 1,
                        'oxvat'           => 19,
                        'scaleprices' => [
                            'oxaddabs'     => 75.00,
                            'oxamount'     => 2,
                            'oxamountto'   => 5,
                            'oxartid'      => 1003,
                          ],
                ],
                1 => [
         // oxarticles db fields
            'oxid'                     => 1112,
            'oxprice'                  => 5.02,
            'oxvat'                    => 19,
            // Amount in basket
            'amount'                   => 1,
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
                        'oxobject2group' => [1003, '_testUserB' ],
                ],
                2 => [
                        'oxid' => 'oxidpricec',
                        'oxactive' => 1,
                        'oxtitle' => 'Price C',
                        'oxobject2group' => [ '_testUserC' ],
                ],
        ],

        'expected' => [
          'articles' => [
                1003 => [ '85,00', '85,00' ],
                1112 => [ '0,00', '0,00' ],
            ],

        'totals' => [
                'totalBrutto' => '85,00',
                'totalNetto'  => '71,43',
                'vats' => [
                      19 => '13,57',
                ],
                'grandTotal'  => '85,00',
        ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                        'blOverrideZeroABCPrices' => false,
                        'dDefaultVAT' => 19,
                ],
                'activeCurrencyRate' => 1,
        ],
];
