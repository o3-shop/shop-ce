<?php

/**
 * Price enter mode: bruto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 5 different VAT
 * Discounts: basket 10% discount
 * Wrapping: -;
 * Gift cart:  -;
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery -
 *  3. TS -
 * Actions with order:
 *  1. update :changed products amounts
 */
$aData = [
     'articles' => [
             0 => [
                     'oxid'       => '111',
                     'oxtitle'    => '111',
                     'oxprice'    => 1002.55,
                     'oxvat'      => 19,
                     'oxstock'    => 999,
                     'amount'     => 2,
             ],
             1 => [
                     'oxid'       => '222',
                     'oxtitle'    => '222',
                     'oxprice'    => 11.56,
                     'oxvat'      => 13,
                     'oxstock'    => 999,
                     'amount'     => 2,
             ],
            2 => [
                     'oxid'       => '333',
                     'oxtitle'    => '333',
                     'oxprice'    => 1326.89,
                     'oxvat'      => 3,
                     'oxstock'    => 999,
                     'amount'     => 6,
             ],
            3 => [
                     'oxid'       => '444',
                     'oxtitle'    => '444',
                     'oxprice'    => 6.66,
                     'oxvat'      => 17,
                     'oxstock'    => 999,
                     'amount'     => 6,
             ],
            4 => [
                     'oxid'       => '555',
                     'oxtitle'    => '555',
                     'oxprice'    => 0.66,
                     'oxvat'      => 33,
                     'oxstock'    => 999,
                     'amount'     => 6,
             ],
     ],
    'discounts' => [
            0 => [
                    'oxid'         => 'discount10for111',
                    'oxaddsum'     => 10,
                    'oxaddsumtype' => '%',
                    'oxamount' => 1,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxsort' => 10,
            ],
    ],
    'costs' => [
        'delivery' => [
                0 => [
                    'oxactive' => 1,
                    'oxaddsum' => 0.00,
                    'oxaddsumtype' => 'abs',
                    'oxdeltype' => 'p',
                    'oxfinalize' => 1,
                    'oxparamend' => 99999,
                    //'shippingSetId' => 'oxidstandard'
                ],
        ],
        'payment' => [
                0 => [
                    'oxaddsum' => 0.00,
                    'oxaddsumtype' => 'abs',
                    'oxfromamount' => 0,
                    'oxtoamount' => 1000000,
                    'oxchecked' => 1,
                ],
        ],
    ],
    'expected' => [
        1 => [
            'articles' => [
                    '111' => [ '1.002,55', '2.005,10' ],
                    '222' => [ '11,56', '23,12' ],
                    '333' => [ '1.326,89', '7.961,34' ],
                    '444' => [ '6,66', '39,96' ],
                    '555' => [ '0,66', '3,96' ],
                    ],
            'totals' => [
                    'totalBrutto' => '10.033,48',
                    'discount' => '1.003,35',
                    'totalNetto'  => '8.524,80',
                    'vats' => [
                            19 => '288,13',
                            13 => '2,39',
                            3 => '208,70',
                            17 => '5,23',
                            33 => '0,88',
                    ],
                    'delivery' => [
                            'brutto' => '0,00',
                    ],
                    'payment' => [
                            'brutto' => '0,00',
                    ],
                    'grandTotal'  => '9.030,13',
            ],
        ],
        2 => [
            'articles' => [
                    '111' => [ '1.002,55', '1.002,55' ],
                    '222' => [ '11,56', '23,12' ],
                    '333' => [ '1.326,89', '3.980,67' ],
                    '444' => [ '6,66', '26,64' ],
                    '555' => [ '0,66', '3,30' ],
                    ],
            'totals' => [
                    'totalBrutto' => '5.036,28',
                    'discount' => '503,63',
                    'totalNetto'  => '4.277,63',
                    'vats' => [
                            19 => '144,06',
                            13 => '2,39',
                            3 => '104,35',
                            17 => '3,48',
                            33 => '0,74',
                    ],
                    'delivery' => [
                            'brutto' => '0,00',
                    ],
                    'payment' => [
                            'brutto' => '0,00',
                    ],
                    'grandTotal'  => '4.532,65',
            ],
        ],
    ],
    'options' => [
            'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
            ],
    ],
    'actions' => [
            '_changeConfigs' => [
                    'blShowNetPrice' => false,
            ],
            '_changeArticles' => [
                    0 => [
                            'oxid'       => '111',
                            'amount'     => 1,
                    ],
                    1 => [
                            'oxid'       => '333',
                            'amount'     => 3,
                    ],
                    2 => [
                            'oxid'       => '444',
                            'amount'     => 4,
                    ],
                    3 => [
                            'oxid'       => '555',
                            'amount'     => 5,
                    ],
            ],
    ],
    // set custom shipping
   // 'shipping' => '1b842e732a23255b1.91207751',
    // set custom payment if needed
    //'payment' => 'oxidpayadvance',
    // parameter for skipping
   // 'template' => 1
];
