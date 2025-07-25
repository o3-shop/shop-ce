<?php

/*
/**
 * Price enter mode: brutto
 * Price view mode:  brutto
 * Product count: 1
 * VAT info: 19% Default VAT for all Products
 * Currency rate: 1.0
 * Discounts: -
 * Vouchers: -
 * Wrapping: -
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery -
 *  3. TS -
 * Short description:
 * From articlePrice.csv: article final price calculations. 9207 - 1st
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9207,
                'oxprice'                  => 45.5,
                'oxvat'                    => 19,
                'amount'                   => 1,
        ],
    ],
    'expected' => [
        'articles' => [
                 9207 => [ '45,50', '45,50' ],
        ],
        'totals' => [
                'totalBrutto' => '45,50',
                'totalNetto'  => '38,24',
                'vats' => [
                        19 => '7,26',
                ],
                'grandTotal'  => '45,50',
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
