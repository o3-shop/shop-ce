<?php

/*
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 5;
 * VAT info:  count of used vat =3(16%, 17% and 19%);
 * Currency rate:1;
 * Discounts: 4
 *  1.  2% discount for product (9201)
 *  2.  4% discount for product (9211)
 *  3.  2% discount for product (9216)
 * Wrapping:  1;
 *  1.  0.48 wrapping for product's (9219)
 * Gift cart: -;
 * Costs VAT caclulation rule: -;
 * Gift cart:  -;
 * Vouchers: -;
 * Costs:
 *  1. Payment -;
 *  2. Delivery + ;
 *  3. TS -
 * Short description:
 * Brutto-Brutto mode.
 * From basketCalc.csv: Complex order calculation order V.
 */
$aData = [
    'articles' => [
            0 => [
                    'oxid'                     => 9201,
                    'oxprice'                  => 72.85,
                    'oxvat'                    => 17,
                    'amount'                   => 175,
            ],
            1 => [
                    'oxid'                     => 9203,
                    'oxprice'                  => 33.30,
                    'oxvat'                    => 19,
                    'amount'                   => 12,
            ],
            2 => [
                    'oxid'                     => 9211,
                    'oxprice'                  => 5.86,
                    'oxvat'                    => 16,
                    'amount'                   => 5874,
            ],
            3 => [
                    'oxid'                     => 9216,
                    'oxprice'                  => 56.45,
                    'oxvat'                    => 17,
                    'amount'                   => 225,
            ],
            4 => [
                    'oxid'                     => 9219,
                    'oxprice'                  => 24.33,
                    'oxvat'                    => 19,
                    'amount'                   => 31,
            ],
    ],
    'discounts' => [
            0 => [
                    'oxid'         => 'discount2for9201',
                    'oxaddsum'     => 2,
                    'oxaddsumtype' => '%',
                    'oxamount' => 0,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxarticles' => [ 9201 ],
                    'oxsort' => 10,
            ],
            1 => [
                    'oxid'         => 'discount4for9211',
                    'oxaddsum'     => 4,
                    'oxaddsumtype' => '%',
                    'oxamount' => 0,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxarticles' => [ 9211 ],
                    'oxsort' => 20,
            ],
            2 => [
                    'oxid'         => 'discount2for9216',
                    'oxaddsum'     => 2,
                    'oxaddsumtype' => '%',
                    'oxamount' => 0,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxarticles' => [ 9216 ],
                    'oxsort' => 30,
            ],
    ],
    'costs' => [
            'wrapping' => [
                    0 => [
                            'oxtype' => 'WRAP',
                            'oxname' => 'wrapFor9219',
                            'oxprice' => 0.48,
                            'oxactive' => 1,
                            'oxarticles' => [ 9219 ],
                    ],
            ],
            'delivery' => [
                    0 => [
                            'oxactive' => 1,
                            'oxaddsum' => 15.03,
                            'oxaddsumtype' => 'abs',
                            'oxdeltype' => 'p',
                            'oxfinalize' => 1,
                            'oxparamend' => 99999,
                    ],
            ],
    ],
    'expected' => [
        'articles' => [
                9201 => [ '71,39', '12.493,25' ],
                9203 => [ '33,30', '399,60' ],
                9211 => [ '5,63', '33.070,62' ],
                9216 => [ '55,32', '12.447,00' ],
                9219 => [ '24,33', '754,23' ],
        ],
        'totals' => [
                'totalBrutto' => '59.164,70',
                'totalNetto'  => '50.795,22',
                'vats' => [
                        16 => '4.561,46',
                        17 => '3.623,80',
                        19 => '184,22',
                ],
                'wrapping' => [
                        'brutto' => '14,88',
                        'netto' => '12,50',
                        'vat' => '2,38',
                ],
                'delivery' => [
                        'brutto' => '15,03',
                        'netto' => '12,96',
                        'vat' => '2,07',
                ],
                'grandTotal'  => '59.194,61',
        ],
    ],
    'options' => [
        'activeCurrencyRate' => 1,
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
                'blShowVATForWrapping' => true,
                'blShowVATForDelivery' => true,
        ],
    ],
];
