<?php

/*
/**
 * Price enter mode: brutto
 * Price view mode:  netto
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
 * From articlePrice.csv: article final price calculations. 9202 - 1st. Domestic vat 17, foreign vat - 0
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9202,
                'oxprice'                  => 16.2,
                'oxvat'                    => 0,
                'amount'                   => 1,
        ],
    ],
    'expected' => [
        'articles' => [
                 9202 => [ '11,02', '11,02' ],
        ],
        'totals' => [
                'totalBrutto' => '11,02',
                'totalNetto'  => '11,02',
                'vats' => [
                    0 => '0,00',
                ],
                'grandTotal'  => '11,02',
        ],
    ],
    'options' => [
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice' => true,
        ],
        'activeCurrencyRate' => 0.68,
    ],
];
