<?php

/*
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 5;
 * VAT info:  count of used vat =3(10%, 5% and 19%);
 * Currency rate:1;
 * Discounts: 1;
 *1. 5abs for product's 10011, 1000
 * Wrapping:  -;
 * Gift cart: -;
 * Costs VAT caclulation rule: biggest_net;
 * Gift cart:  -;
 * Vouchers: +;
 * Costs:
 *  1. Payment +;
 *  2. Delivery + ;
 *  3. TS -;
 * Short description:
 * Brutto-Brutto mode.
 * Short description: test added from selenium test (testFrontendVatForBillingCountry) ; Checking VAT functionality, when it is calculated for Billing country
 */
$aData = [
    'articles' => [
            0 => [
                    'oxid'                     => 10011,
                    'oxprice'                  => 101,
                    'oxvat'                    => 10,
                    'amount'                   => 1,
                    'oxpricea'       		   => 0,
                    'oxpriceb' 			       => 0,
                    'oxpricec' 			       => 0,
            ],

            1 => [
                    'oxid'                     => 1003,
                    'oxprice'                  => 75.00,
                    'oxvat'                    => 19,
                    'amount'                   => 1,
                    'oxpricea'       		   => 70,
                    'oxpriceb' 			       => 85,
                    'oxpricec' 			       => 0,
            ],
            2 => [
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

        // User
    'user' => [
            'oxactive' => 1,
            'oxusername' => 'basketUser',
            // country id, for example this is Schweiz, make sure country with specified ID is active
            'oxcountryid' => 'a7c40f6321c6f6109.43859248',
    ],
    'discounts' => [
            0 => [
                    'oxid'         => 'discount2',
                    'oxaddsum'     => 5,
                    'oxaddsumtype' => 'abs',
                    'oxamount'     => 1,
                    'oxamountto'   => 99999,
                    'oxactive'     => 1,
                    'oxarticles'   => [ 10011, 1000 ],
                    'oxsort'       => 10,
            ],
    ],

    'costs' => [
        'delivery' => [
            0 => [
                'oxactive' => 1,
                'oxaddsum' => 0,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'p',
                'oxfinalize' => 1,
                'oxparamend' => 99999,
            ],
        ],
                // Payment
        'payment' => [
             0 => [
                // oxpayments DB fields,
                'oxaddsum' => 7.50,
                'oxaddsumtype' => 'abs',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
                'oxaddsumrules'=>0,
            ],
        ],
    ],
    'expected' => [
        'articles' => [
                10011 => [ '86,82', '86,82' ],
                1003 => [ '63,03', '63,03' ],
                1000 => [ '42,62', '42,62' ],
        ],
        'totals' => [
                'totalBrutto' => '192,47',
                'totalNetto'  => '192,47',
                'vats' => [
                        0 => '0,00',
                ],
                'delivery' => [
                        'brutto' => '0,00',
                ],
               'payment' => [
                'brutto' => '7,50',
                'netto' => '7,50',
              //  'vat' => '0,00'
            ],
                'grandTotal'  => '199,97',
        ],
    ],
    'options' => [
        'activeCurrencyRate' => 1,
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
                'blShowVATForWrapping' => false,
                'blShowVATForDelivery' => false,
                'blDeliveryVatOnTop' => false,
                'blPaymentVatOnTop' => false,
                'sAdditionalServVATCalcMethod' => 'biggest_net',
        ],
    ],
];
