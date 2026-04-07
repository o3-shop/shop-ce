<?php

/*
/**
 * Price enter mode: brutto
 * Price view mode:  brutto
 * Product count: 2
 * VAT info: 18% Default VAT for all Products
 * Currency rate: 0.58
 * Discounts: -
 * Vouchers: -
 * Wrapping: -
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery -
 *  3. TS -
 * Short description:
 * From articlePrice.csv: article final price calculations. 9209 - 1st
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9209,
                'oxprice'                  => 42.36,
                'oxvat'                    => 18,
                'amount'                   => 1,
        ],
    ],
    'expected' => [
        'articles' => [
                 9209 => [ '24,57', '24,57' ],
        ],
        'totals' => [
                'totalBrutto' => '24,57',
                'totalNetto'  => '20,82',
                'vats' => [
                        18 => '3,75',
                ],
                'grandTotal'  => '24,57',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
        ],
        'activeCurrencyRate' => 0.58,
    ],
];
