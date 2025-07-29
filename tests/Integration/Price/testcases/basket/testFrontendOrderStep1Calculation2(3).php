<?php

/*
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 5;
 * VAT info:  count of used vat =3(10%, 5% and 19%);
 * Currency rate:1;
 * Discounts: 2
 *  1.  10% discount for product (1002, 1003)
 *  2.  5abs discount for product (1001, 1000)
 * Wrapping:  -;
 * Gift cart: -;
 * Costs VAT caclulation rule: biggest_net;
 * Gift cart:  -;
 * Vouchers: +;
 * Costs:
 *  1. Payment -;
 *  2. Delivery + ;
 *  3. TS -
 * Short description:
 * Brutto-Brutto mode.
 * Short description: test added from selenium test (testFrontendOrderStep1Calculation2) ;Is testing basked Step1 Calculation
 */
$aData = [
    'articles' => [
            0 => [
                    'oxid'                     => 10014,
                    'oxprice'                  => 102,
                    'oxvat'                    => 10,
                    'amount'                   => 1,
                    'oxpricea'       		   => 0,
                    'oxpriceb' 			       => 0,
                    'oxpricec' 			       => 0,
            ],
            1 => [
                    'oxid'                     => 1002,
                    'oxprice'                  => 67.00,
                    'oxvat'                    => 19,
                    'amount'                   => 1,
            ],
            2 => [
                    'oxid'                     => 1003,
                    'oxprice'                  => 75.00,
                    'oxvat'                    => 19,
                    'amount'                   => 6,
                    'oxpricea'       		   => 70,
                    'oxpriceb' 			       => 85,
                    'oxpricec' 			       => 0,
                    'scaleprices' => [
                        'oxamount'     => 6,
                        'oxamountto'   => 999999,
                        'oxartid'      => 1003,
                        'oxaddperc'    => 20,
                    ],
            ],
            3 => [
                    'oxid'                     => 1000,
                    'oxprice'                  => 50.00,
                    'oxvat'                    => 5,
                    'amount'                   => 1,
                    'oxpricea'       		   => 35,
                    'oxpriceb' 			       => 45,
                    'oxpricec' 			       => 55,
                    'oxunitname'               => 'kg',
                    'oxunitquantity'           => 2,
                    'oxweight'                 => 2,
            ],
    ],
    'discounts' => [
            0 => [
                    'oxid'         => 'discount1',
                    'oxaddsum'     => 10,
                    'oxaddsumtype' => '%',
                    'oxamount'     => 0,
                    'oxamountto'   => 99999,
                    'oxprice'      => 100,
                    'oxpriceto'    => 99999,
                    'oxactive'     => 1,
                    'oxarticles'   => [ 1002, 1003 ],
                    'oxsort'       => 10,
            ],
            1 => [
                    'oxid'         => 'discount2',
                    'oxaddsum'     => 5,
                    'oxaddsumtype' => 'abs',
                    'oxamount'     => 1,
                    'oxamountto'   => 99999,
                    'oxactive'     => 1,
                    'oxarticles'   => [ 10014, 1000 ],
                    'oxsort'       => 20,
            ],
    ],

    'costs' => [
        'delivery' => [
            0 => [
                'oxactive' => 1,
                'oxaddsum' => 1.50,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'p',
                'oxfinalize' => 1,
                'oxparamend' => 99999,
            ],
        ],
                   // VOUCHERS
        'voucherserie' => [
            0 => [
                // oxvoucherseries DB fields
                'oxdiscount' => 10.00,
                'oxdiscounttype' => 'absolute',
                'oxallowsameseries' => 1,
                'oxallowotherseries' => 1,
                'oxallowuseanother' => 1,
                'oxminimumvalue' =>75,
                // voucher of this voucherserie count
                'voucher_count' => 1,
            ],
        ],
    ],
    'expected' => [
        'articles' => [
                10014 => [ '97,00', '97,00' ],
                1002 => [ '60,30', '60,30' ],
                1003 => [ '54,00', '324,00' ],
                1000 => [ '45,00', '45,00' ],
        ],
        'totals' => [
                'totalBrutto' => '526,30',
                'totalNetto'  => '445,36',
                'vats' => [
                        10 => '8,65',
                        19 => '60,19',
                        5 => '2,10',
                ],
                'delivery' => [
                        'brutto' => '1,50',
                ],
                // Total voucher amounts
                'voucher' => [
                'brutto' => '10,00',
                ],
                'grandTotal'  => '517,80',
        ],
    ],
    'options' => [
        'activeCurrencyRate' => 1,
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
                'blShowVATForWrapping' => false,
                'blShowVATForDelivery' => false,
                'sAdditionalServVATCalcMethod' => 'biggest_net',
        ],
    ],
];
