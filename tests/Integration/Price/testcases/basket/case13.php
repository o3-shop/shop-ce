<?php

/**
 * Price enter mode: brutto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 17%
 * Currency rate: 1.0
 * Discounts: count
 *  1. shop  5.05 abs for 9201
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
                'oxprice'                  => 77.9,
                'oxvat'                    => 17,
                'amount'                   => 1,
        ],
    ],
    'discounts' => [
        0 => [
                'oxid'         => 'abs_discount_for_9201',
                'oxaddsum'     => 5.05,
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
                 9201 => [ '72,85', '72,85' ],
        ],
        'totals' => [
                'totalBrutto' => '72,85',
                'totalNetto'  => '62,26',
                'vats' => [
                        17 => '10,59',
                ],
                'grandTotal'  => '72,85',
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
