<?php

/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 1
 * VAT info: 19%
 * Currency rate: 1.0
 * Discounts: -
 * Short description: Brutto-Brutto user group Price B, option "Use normal article price instead of zero A, B, C price" is ON
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
                        'amount'          => 7,
                        'oxvat'           => 19,
                       'scaleprices' => [
                            'oxamount'     => 6,
                            'oxamountto'   => 999999,
                            'oxartid'      => 1003,
                            'oxaddperc'    => 20,
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
                1003 => [ '68,00', '476,00' ],
            ],

        'totals' => [
                'totalBrutto' => '476,00',
                'totalNetto'  => '400,00',
                'vats' => [
                      19 => '76,00',
                ],
                'grandTotal'  => '476,00',
        ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                        'blOverrideZeroABCPrices' => true,
                        'dDefaultVAT' => 19,
                ],
                'activeCurrencyRate' => 1,
        ],
];
