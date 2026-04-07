<?php

/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 1
 * VAT info: 19%
 * Currency rate: 1.0
 * Discounts: -
 * Short description: Brutto-Brutto user group Price B,  option "Use normal article price instead of zero A, B, C price" is ON
 * Test case is moved from selenium test "testFrontendPriceA"
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'            => 1002,
                        'oxprice'         => 50.00,
                        'oxpricea'        => 35,
                        'oxpriceb'        => 45,
                        'oxpricec'        => 55,
                        'amount'          => 1,
                        'oxvat'           => 19,
                        'oxtitle'     => 'Wall Clock ROBOT',

                        'oxunitname'               => 'kg',
                        'oxunitquantity'           => 2,
                        'oxweight'        => 10,
                    //	'oxheight'        => 2,
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
                        'oxobject2group' => [1002, '_testUserA' ],
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
                1002 => [ '35,00', '35,00' ],
            ],

        'totals' => [
                'totalBrutto' => '35,00',
                'totalNetto'  => '29,41',
                'vats' => [
                      19 => '5,59',
                ],
                'grandTotal'  => '35,00',
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
