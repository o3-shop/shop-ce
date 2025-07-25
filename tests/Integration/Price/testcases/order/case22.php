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
                     'oxid'       => '11',
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
           1 => [
                'oxid'         => 'discount_for_prod',
                'oxaddsum'     => 15,
                'oxaddsumtype' => '%',
                'oxamount' => 0,
                'oxamountto' => 99999,
                'oxactive' => 1,
                'oxarticles' => [ 666, 777, 888 ],
                'oxsort' => 20,
        ],
    ],
    'costs' => [
        'delivery' => [
                0 => [
                    'oxactive' => 1,
                    'oxaddsum' => 5.00,
                    'oxaddsumtype' => 'abs',
                    'oxdeltype' => 'p',
                    'oxfinalize' => 1,
                    'oxparamend' => 99999,
                    'shippingSetId' => 'oxidstandard',
                ],
        ],
        'payment' => [
                0 => [
                    'oxaddsum' => 5.00,
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
                    '11' => [ '1.002,55', '2.005,10' ],
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
                            'brutto' => '5,00',
                    ],
                    'payment' => [
                            'brutto' => '5,00',
                    ],
                    'grandTotal'  => '9.040,13',
            ],
        ],
        2 => [
            'articles' => [
                    '666' => [ '852,17', '5.113,02' ],
                    '777' => [ '9,83', '58,98' ],
                    '888' => [ '1.127,86', '6.767,16' ],
                    '444' => [ '6,66', '39,96' ],
                    '555' => [ '0,66', '3,96' ],
                    ],
            'totals' => [
                    'totalBrutto' => '11.983,08',
                    'discount' => '1.198,31',
                    'totalNetto'  => '9.224,35',
                    'vats' => [
                            21 => '845,62',
                            17 => '12,94',
                            14 => '791,94',
                            33 => '0,88',
                    ],
                    'delivery' => [
                            'brutto' => '5,00',
                    ],
                    'payment' => [
                            'brutto' => '5,00',
                    ],
                    'grandTotal'  => '10.794,77',
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
                'blEnterNetPrice' => false,
                'blShowNetPrice' => true,
            ],
             '_removeArticles' => [ '11', '222', '333' ],

             '_addArticles' => [
            0 => [
                     'oxid'       => '666',
                     'oxtitle'    => '666',
                     'oxprice'    => 1002.55,
                     'oxvat'      => 21,
                     'oxstock'    => 999,
                     'amount'     => 6,
             ],
             1 => [
                     'oxid'       => '777',
                     'oxtitle'    => '777',
                     'oxprice'    => 11.56,
                     'oxvat'      => 17,
                     'oxstock'    => 999,
                     'amount'     => 6,
             ],
             2 => [
                     'oxid'       => '888',
                     'oxtitle'    => '888',
                     'oxprice'    => 1326.89,
                     'oxvat'      => 14,
                     'oxstock'    => 999,
                     'amount'     => 6,
             ],
            ],
    ],
];
