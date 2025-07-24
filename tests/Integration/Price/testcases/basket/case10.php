<?php

/*
/**
 * Price enter mode: bruto
 * Price view mode:  bruto
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
 * From articlePrice.csv: article final price calculations. 9206 - 1st
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9206,
                'oxprice'                  => 103,
                'oxvat'                    => 19,
                'amount'                   => 1,
        ],
    ],
    'expected' => [
        'articles' => [
                 9206 => [ '103,00', '103,00' ],
        ],
        'totals' => [
                'totalBrutto' => '103,00',
                'totalNetto'  => '86,55',
                'vats' => [
                        19 => '16,45',
                ],
                'grandTotal'  => '103,00',
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
