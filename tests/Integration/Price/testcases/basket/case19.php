<?php

/**
 * Price enter mode: neto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 17%
 * Currency rate: 1.0
 * Discounts: count
 *  1. shop  4.32 abs for 9201
 * Vouchers: -;
 * Wrapping: -;
 * Gift cart: -;
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery  -
 *  3. TS -
 * Actions with basket or order:
 *   change config
 * Short description: From articlePrice.csv: article final price calculations. 9201 - 1st
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9201,
                'oxprice'                  => 66.58,
                'oxvat'                    => 17,
                'amount'                   => 1,
        ],
    ],
    'discounts' => [
        0 => [
                'oxid'         => 'abs_discount_for_9201',
                'oxaddsum'     => 4.32,
                'oxaddsumtype' => 'abs',
                'oxamount' => 0,
                'oxamountto' => 99999,
                'oxactive' => 1,
                'oxarticles' => [ 9201 ],
                'oxsort' => 10,
        ],
    ],
    'expected' => [
        'articles' => [
                 9201 => [ '73,58', '73,58' ],
        ],
        'totals' => [
                'totalBrutto' => '73,58',
                'totalNetto'  => '62,89',
                'vats' => [
                    17 => '10,69',
                ],
                'grandTotal'  => '73,58',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => true,
                'blShowNetPrice' => false,
        ],
        'activeCurrencyRate' => 1,
    ],
];
