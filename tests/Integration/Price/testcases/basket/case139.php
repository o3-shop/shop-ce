<?php

/*
 * Price enter mode: brutto;
 * Price view mode: brutto;
 * Product count: 1;
 * VAT info:  count of used vat =1(19%);
 * Currency rate:1-;
 * Discounts: 1
 * 1. 5% abs discount for basket
 * Wrapping:  -;
 * Gift cart: -;
 * Costs VAT caclulation rule: -;
 * Wrapping: -;
 * Gift cart:  -;
 * Vouchers: -;
 * Costs:
 *  1. Payment -;
 *  2. Delivery- ;
 *  3. TS -
 * Short description:
 * Brutto-Brutto mode.
 * Testing basket item price calculator also sets items discounts
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'        => 'testarticle',
                'oxprice'     => 19,
                'oxweight'    => 10,
                'oxstock'     => 100,
                'oxstockflag' => 2,
                'amount'      => 1,
                'scaleprices' => [],
        ],
    ],
    'discounts' => [
            0 => [
                    'oxid'        => 'testdiscount0',
                    'oxactive'    => 1,
                    'oxtitle'     => 'Test discount 0',
                    'oxamount'    => 1,
                    'oxamountto'  => 99999,
                    'oxprice'     => 1,
                    'oxpriceto'   => 99999,
                    'oxaddsumtype'=> 'abs',
                    'oxaddsum'    => 5,
                    'oxsort'      => 10,
            ],
            1 => [
                    'oxid'        => 'testdiscount1',
                    'oxactive'    => 1,
                    'oxtitle'     => 'Test discount 1',
                    'oxamount'    => 1,
                    'oxamountto'  => 99999,
                    'oxprice'     => 1,
                    'oxpriceto'   => 99999,
                    'oxaddsumtype'=> 'abs',
                    'oxaddsum'    => 7,
                    'oxsort'      => 20,
            ],
    ],
    'expected' => [
        'articles' => [
             'testarticle' => [ '19,00', '19,00' ],
        ],
        'totals' => [
            'totalBrutto' => '19,00',
            'totalNetto'  => '5,88',
            'vats' => [
                19 => '1,12',
            ],
            'discounts' => [
                    'testdiscount0' => '5,00',
                    'testdiscount1' => '7,00',
                ],
            'grandTotal'  => '7,00',
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
