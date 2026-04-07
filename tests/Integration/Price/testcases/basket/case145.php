<?php

/*
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 4;
 * VAT info:  count of used vat =2(19% and 17%);
 * Currency rate:-;
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
 * From basketCalc.csv: VI order.
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9200,
                'oxprice'                  => 87.00,
                'oxvat'                    => 17,
                'amount'                   => 2008,
        ],
        1 => [
                'oxid'                     => 9201,
                'oxprice'                  => 72.85,
                'oxvat'                    => 17,
                'amount'                   => 369,
        ],
        2 => [
                'oxid'                     => 9207,
                'oxprice'                  => 45.50,
                'oxvat'                    => 19,
                'amount'                   => 1698,
        ],
        3 => [
                'oxid'                     => 9213,
                'oxprice'                  => 30.77,
                'oxvat'                    => 19,
                'amount'                   => 3665,
        ],
    ],
    'expected' => [
        'articles' => [
                 9200 => [ '87,00', '174.696,00' ],
                 9201 => [ '72,85', '26.881,65' ],
                 9207 => [ '45,50', '77.259,00' ],
                 9213 => [ '30,77', '112.772,05' ],
        ],
        'totals' => [
                'totalBrutto' => '391.608,70',
                'totalNetto'  => '331.978,55',
                'vats' => [
                        17 => '29.289,06',
                        19 => '30.341,09',
                ],
                'grandTotal'  => '391.608,70',
        ],
    ],
    'options' => [
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice' => false,
        ],
    ],
];
