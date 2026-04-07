<?php

/**
 * Price enter mode: bruto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 19%
 * Currency rate: 0.8
 * Discounts: count
 *  1. shop  15.00 abs for 111
 * Vouchers: -;
 * Wrapping: -;
 * Gift cart: -;
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery  -
 *  3. TS -
 *
 * Case: 0004680: Discount recalculation fails on basket refresh
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => '_testProduct',
                'oxprice'                  => 10.00,
                'oxvat'                    => 19,
                'amount'                   => 36,
        ],
    ],
    'discounts' => [
        0 => [
                'oxid'         => 'basket_0',
                'oxaddsum'     => 6.00,
                'oxaddsumtype' => '%',
                'oxamount' => 0,
                'oxamountto' => 99999,
                'oxprice' => 100,
                'oxpriceto' => 199,
                'oxactive' => 1,
                //'oxarticles' => array( 111 ),
                'oxsort' => 10,
        ],
        1 => [
                'oxid'         => 'basket_1',
                'oxaddsum'     => 9.00,
                'oxaddsumtype' => '%',
                'oxamount' => 0,
                'oxamountto' => 99999,
                'oxprice' => 200,
                'oxpriceto' => 299,
                'oxactive' => 1,
                'oxsort' => 20,
        ],
        2 => [
                'oxid'         => 'basket_2',
                'oxaddsum'     => 12.00,
                'oxaddsumtype' => '%',
                'oxamount' => 0,
                'oxamountto' => 99999,
                'oxprice' => 300,
                'oxpriceto' => 99999,
                'oxactive' => 1,
                'oxsort' => 30,
        ],
    ],

    'expected' => [
        'articles' => [
                 '_testProduct' => [ '10,00', '360,00' ],
        ],
        'totals' => [
                'totalBrutto' => '360,00',
                'totalNetto'  => '266,22',
                'vats' => [
                    19 => '50,58',
                ],
                'discounts' => [
                    'basket_2' => '43,20',
                ],
                'grandTotal'  => '316,80',
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
