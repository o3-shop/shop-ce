<?php

/*
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 6;
 * VAT info:  count of used vat =1(19%);
 * Currency rate:1;
 * Discounts: 4
 *  1.  5% discount for product (9200)
 *  2.  2% discount for product (9201)
 *  3.  3% discount for product (9208)
 *  4.  1% discount for product (9212)
 * Wrapping:  2;
 *  1.  0.63 wrapping for product (9202)
 *  2.  0.33 wrapping for product (9216)
 * Gift cart: -;
 * Costs VAT caclulation rule: -;
 * Gift cart:  -;
 * Vouchers: -;
 * Costs:
 *  1. Payment +;
 *  2. Delivery + ;
 *  3. TS -
 * Short description:
 * Brutto-Brutto mode.
 * From basketCalc.csv: Complex order calculation order VII.
 */
$aData = [
    'articles' => [
            0 => [
                    'oxid'                     => 9200,
                    'oxprice'                  => 87,
                    'oxvat'                    => 17,
                    'amount'                   => 20315,
            ],
            1 => [
                    'oxid'                     => 9201,
                    'oxprice'                  => 72.85,
                    'oxvat'                    => 17,
                    'amount'                   => 210,
            ],
            2 => [
                    'oxid'                     => 9202,
                    'oxprice'                  => 16.20,
                    'oxvat'                    => 17,
                    'amount'                   => 56,
            ],
            3 => [
                    'oxid'                     => 9208,
                    'oxprice'                  => 72.11,
                    'oxvat'                    => 17,
                    'amount'                   => 691,
            ],
            4 => [
                    'oxid'                     => 9212,
                    'oxprice'                  => 16.37,
                    'oxvat'                    => 17,
                    'amount'                   => 548,
            ],
            5 => [
                    'oxid'                     => 9216,
                    'oxprice'                  => 56.45,
                    'oxvat'                    => 17,
                    'amount'                   => 36,
            ],
    ],
    'discounts' => [
            0 => [
                    'oxid'         => 'discount5for9200',
                    'oxaddsum'     => 5,
                    'oxaddsumtype' => '%',
                    'oxamount' => 0,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxarticles' => [ 9200 ],
                    'oxsort' => 10,
            ],
            1 => [
                    'oxid'         => 'discount2for9201',
                    'oxaddsum'     => 2,
                    'oxaddsumtype' => '%',
                    'oxamount' => 0,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxarticles' => [ 9201 ],
                    'oxsort' => 20,
            ],
            2 => [
                    'oxid'         => 'discount3for9208',
                    'oxaddsum'     => 3,
                    'oxaddsumtype' => '%',
                    'oxamount' => 0,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxarticles' => [ 9208 ],
                    'oxsort' => 30,
            ],
            3 => [
                    'oxid'         => 'discount1for9212',
                    'oxaddsum'     => 1,
                    'oxaddsumtype' => '%',
                    'oxamount' => 0,
                    'oxamountto' => 99999,
                    'oxactive' => 1,
                    'oxarticles' => [ 9212 ],
                    'oxsort' => 40,
            ],
    ],
    'costs' => [
            'wrapping' => [
                    0 => [
                            'oxtype' => 'WRAP',
                            'oxname' => 'wrapFor9202',
                            'oxprice' => 0.63,
                            'oxactive' => 1,
                            'oxarticles' => [ 9202 ],
                    ],
                    1 => [
                            'oxtype' => 'WRAP',
                            'oxname' => 'wrapFor9216',
                            'oxprice' => 0.33,
                            'oxactive' => 1,
                            'oxarticles' => [ 9216 ],
                    ],
            ],
            'delivery' => [
                    0 => [
                            'oxactive' => 1,
                            'oxaddsum' => 117,
                            'oxaddsumtype' => 'abs',
                            'oxdeltype' => 'p',
                            'oxfinalize' => 1,
                            'oxparamend' => 9999999,
                    ],
            ],
            'payment' => [
                    0 => [
                        'oxaddsum' => 3,
                        'oxaddsumtype' => 'abs',
                        'oxfromamount' => 0,
                        'oxtoamount' => 2000000,
                        'oxchecked' => 1,
                    ],
            ],
    ],
    'expected' => [
        'articles' => [
                9200 => [ '82,65', '1.679.034,75' ],
                9201 => [ '71,39', '14.991,90' ],
                9202 => [ '16,20', '907,20' ],
                9208 => [ '69,95', '48.335,45' ],
                9212 => [ '16,21', '8.883,08' ],
                9216 => [ '56,45', '2.032,20' ],
        ],
        'totals' => [
                'totalBrutto' => '1.754.184,58',
                'totalNetto'  => '1.499.303,06',
                'vats' => [
                        17 => '254.881,52',
                ],
                'wrapping' => [
                        'brutto' => '47,16',
                        'netto' => '40,30',
                        'vat' => '6,86',
                ],
                'delivery' => [
                        'brutto' => '117,00',
                        'netto' => '100,00',
                        'vat' => '17,00',
                ],
                'payment' => [
                        'brutto' => '3,00',
                        'netto' => '2,56',
                        'vat' => '0,44',
                ],
                'grandTotal'  => '1.754.351,74',
        ],
    ],
    'options' => [
        'activeCurrencyRate' => 1,
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
                'blShowVATForWrapping' => true,
                'blShowVATForPayCharge' => true,
                'blShowVATForDelivery' => true,
        ],
    ],
];
