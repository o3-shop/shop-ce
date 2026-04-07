<?php

/*
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 5;
 * VAT info:  count of used vat =2(15% and 0%);
 * Currency rate: - ;
 * Discounts: -;
 * Wrapping:  -;
 * Gift cart: -;
 * Costs VAT caclulation rule: -;
 * Gift cart:  -;
 * Vouchers: -;
 * Costs:
 *  1. Payment -;
 *  2. Delivery - ;
 *  3. TS -
 * Short description:
 * Brutto-Brutto mode.
 * From basketCalc.csv: IV order.
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9202,
                'oxprice'                  => 15.93,
                'oxvat'                    => 15,
                'amount'                   => 21,
        ],
        1 => [
                'oxid'                     => 9208,
                'oxprice'                  => 70.87,
                'oxvat'                    => 15,
                'amount'                   => 9,
        ],
        2 => [
                'oxid'                     => 9213,
                'oxprice'                  => 25.86,
                'oxvat'                    => 0,
                'amount'                   => 10,
        ],
        3 => [
                'oxid'                     => 9216,
                'oxprice'                  => 48.25,
                'oxvat'                    => 0,
                'amount'                   => 4,
        ],
        4 => [
                'oxid'                     => 9218,
                'oxprice'                  => 58.09,
                'oxvat'                    => 15,
                'amount'                   => 5,
        ],
    ],
    'expected' => [
        'articles' => [
                 9202 => [ '15,93', '334,53' ],
                 9208 => [ '70,87', '637,83' ],
                 9213 => [ '25,86', '258,60' ],
                 9216 => [ '48,25', '193,00' ],
                 9218 => [ '58,09', '290,45' ],
        ],
        'totals' => [
                'totalBrutto' => '1.714,41',
                'totalNetto'  => '1.549,70',
                'vats' => [
                        0 => '0,00',
                        15 => '164,71',
                ],
                'grandTotal'  => '1.714,41',
        ],
    ],
    'options' => [
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice' => false,
        ],
    ],
];
