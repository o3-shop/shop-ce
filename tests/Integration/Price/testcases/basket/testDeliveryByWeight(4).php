<?php

/*
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 2;
 * VAT info:  count of used vat =1(19%);
 * Currency rate:1;
 * Discounts: -
 * Wrapping:  -;
 * Gift cart: -;
 * Costs VAT caclulation rule: biggest_net;
 * Gift cart:  -;
 * Vouchers: -;
 * Costs:
 *  1. Payment -;
 *  2. Delivery +3;
 *  3. TS -
 * Short description:
 * Brutto-Brutto mode.
 * Short description: test added from selenium test (testDeliveryByWeight) ; checking on weight depending delivery costs
 */
$aData = [
    'articles' => [
            0 => [
                    'oxid'                     => 10011,
                    'oxprice'                  => 1.80,
                    'oxvat'                    => 19,
                    'amount'                   => 1,
                    'oxpricea'       		   => 0,
                    'oxpriceb' 			       => 0,
                    'oxpricec' 			       => 0,
                    'oxweight'                 => 2,
            ],
            1 => [
                    'oxid'                     => 10012,
                    'oxprice'                  => 2.00,
                    'oxvat'                    => 19,
                    'amount'                   => 1,
                    'oxweight'                 => 2,
            ],
    ],

    'costs' => [
        'delivery' => [
            0 => [
                'oxactive' => 1,
                'oxaddsum' => 10.00,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'w',
                'oxparam' => 15.00,
                'oxfinalize' => 0,
                'oxparamend' => 999,
                //For each product
                'oxfixed' => 2,
                'oxsort' => 4,
            ],
            1 => [
                'oxactive' => 1,
                'oxaddsum' => 1.00,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'w',
                'oxparam' => 1.00,
                'oxfinalize' => 0,
                'oxparamend' => 4.99999999,
                //For each product
                'oxfixed' => 2,
                'oxsort' => 1,
            ],
            2 => [
                'oxactive' => 1,
                'oxaddsum' => 5.00,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'w',
                'oxparam' => 5.00,
                'oxfinalize' => 0,
                'oxparamend' => 14.9999999 ,
                //For each product
                'oxfixed' => 2,
                'oxsort' => 2,
            ],
        ],
    ],
    'expected' => [
        'articles' => [
                10011 => [ '1,80', '1,80' ],
                10012 => [ '2,00', '2,00' ],
        ],
        'totals' => [
                'totalBrutto' => '3,80',
                'totalNetto'  => '3,19',
                'vats' => [
                        19 => '0,61',
                ],
                'delivery' => [
                    'brutto' => '2,00',
                ],

                'grandTotal'  => '5,80',
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
