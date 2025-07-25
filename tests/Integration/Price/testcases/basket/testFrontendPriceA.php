<?php

/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 1
 * VAT info: 19%
 * Currency rate: 1.0
 * Discounts: -
 * Short description: Brutto-Brutto user group Price B, option "Use normal article price instead of zero A, B, C price" is ON
 * Test case is moved from selenium test "testFrontendPriceA"
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'            => 1003,
                        'oxprice'         => 70.00,
                        'oxpricea'        => 71,
                        'oxpriceb'        => 85,
                        'oxpricec'        => 0,
                        'amount'          => 1,
                        'oxvat'           => 19,
                       'scaleprices' => [
                            'oxamount'     => 6,
                            'oxamountto'   => 999999,
                            'oxartid'      => 1003,
                            'oxaddperc'    => 20,
                        //	'oxaddabs'     => 75.00,
                          ],
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
                        'oxobject2group' => [1003, '_testUserA' ],
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
          'articles' => [
                1003 => [ '71,00', '71,00' ],
            ],

        'totals' => [
                'totalBrutto' => '71,00',
                'totalNetto'  => '59,66',
                'vats' => [
                      19 => '11,34',
                ],
                'grandTotal'  => '71,00',
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
