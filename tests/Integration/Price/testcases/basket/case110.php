<?php

/**
 * Price enter mode: netto
 * Price view mode:  netto
 * Product count: 9
 * VAT info: 19%
 * Currency rate: 1
 * Discounts: 0
 * Vouchers: 0
 * Wrapping: -;
 * Gift cart:  -;
 * Costs VAT caclulation rule: -
 * Costs:
 *  1. Payment -
 *  2. Delivery -
 *  3. TS -
 * Actions with basket or order:-
 * Short description: bodymed neto mode case;
 */
$aData = [
    'articles' => [
        0 => [
            'oxid'                     => 1,
            'oxprice'                  => 24.72,
            'oxvat'                    => 7,
            'amount'                   => 2,
        ],
        1 => [
            'oxid'                     => 2,
            'oxprice'                  => 14.57,
            'oxvat'                    => 7,
            'amount'                   => 1,
        ],
        2 => [
            'oxid'                     => 3,
            'oxprice'                  => 1.49,
            'oxvat'                    => 7,
            'amount'                   => 5,
        ],
        3 => [
            'oxid'                     => 4,
            'oxprice'                  => 1.65,
            'oxvat'                    => 7,
            'amount'                   => 5,
        ],
        4 => [
            'oxid'                     => 5,
            'oxprice'                  => 17.06,
            'oxvat'                    => 7,
            'amount'                   => 1,
        ],
        5 => [
            'oxid'                     => 6,
            'oxprice'                  => 1.63,
            'oxvat'                    => 7,
            'amount'                   => 6,
        ],
        6 => [
            'oxid'                     => 7,
            'oxprice'                  => 21.57,
            'oxvat'                    => 7,
            'amount'                   => 1,
        ],
        7 => [
            'oxid'                     => 8,
            'oxprice'                  => 21.57,
            'oxvat'                    => 7,
            'amount'                   => 1,
        ],
        8 => [
            'oxid'                     => 9,
            'oxprice'                  => 24.44,
            'oxvat'                    => 7,
            'amount'                   => 1,
        ],
    ],

    'expected' => [
        'articles' => [
             1 => [ '24,72', '49,44' ],
             2 => [ '14,57', '14,57' ],
             3 => [ '1,49', '7,45' ],
             4 => [ '1,65', '8,25' ],
             5 => [ '17,06', '17,06' ],
             6 => [ '1,63', '9,78' ],
             7 => [ '21,57', '21,57' ],
             8 => [ '21,57', '21,57' ],
             9 => [ '24,44', '24,44' ],
        ],
        'totals' => [
            'totalBrutto' => '186,32',
            'totalNetto'  => '174,13',
            'vats' => [
                7 => '12,19',
            ],
            'grandTotal'  => '186,32',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => true,
                'blShowNetPrice' => true,
        ],
        'activeCurrencyRate' => 1,
    ],
];
