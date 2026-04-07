<?php

/**
 * Price enter mode: bruto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 19%
 * Currency rate: 0.8
 * Discounts: count
 *  1. shop  15.00 abs for 111
 * Vouchers: -;
 * Wrapping: -;
 * Gift cart: -;
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery  -
 *  3. TS -
 *
 * Case: cheking if corectrly currency rate applyed to discount
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 111,
                'oxprice'                  => 100.00,
                'oxvat'                    => 19,
                'amount'                   => 1,
        ],
    ],
    'discounts' => [
        0 => [
                'oxid'         => 'abs_discount_for_111',
                'oxaddsum'     => 15.00,
                'oxaddsumtype' => 'abs',
                'oxamount' => 0,
                'oxamountto' => 99999,
                'oxprice' => 85,
                'oxpriceto' => 110,
                'oxactive' => 1,
                'oxarticles' => [ 111 ],
                'oxsort' => 10,
        ],
    ],
    'expected' => [
        'articles' => [
                 111 => [ '68,00', '68,00' ],
        ],
        'totals' => [
                'totalBrutto' => '68,00',
                'totalNetto'  => '57,14',
                'vats' => [
                    19 => '10,86',
                ],
                'grandTotal'  => '68,00',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
        ],
        'activeCurrencyRate' => 0.8,
    ],
];
