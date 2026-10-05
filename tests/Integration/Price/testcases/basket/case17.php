<?php

/**
 * Price enter mode: neto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 17%
 * Currency rate: 0.68
 * Discounts: -;
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
 * Short description: From articlePrice.csv: article final price calculations. 9200 - 1st
 */

$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9200,
                'oxprice'                  => 74.36,
                'oxvat'                    => 17,
                'amount'                   => 1,
        ],
    ],
    'expected' => [
        'articles' => [
                 9200 => [ '59,16', '59,16' ],
        ],
        'totals' => [
                'totalBrutto' => '59,16',
                'totalNetto'  => '50,56',
                'vats' => [
                        17 => '8,60',
                ],
                'grandTotal'  => '59,16',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => true,
                'blShowNetPrice' => false,
        ],
        'activeCurrencyRate' => 0.68,
    ],
];
