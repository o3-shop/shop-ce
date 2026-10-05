<?php

/**
 * Price enter mode: netto
 * Price view mode: brutto
 * Product count: 2
 * VAT info: count of used vat's =2 (20% and 30%)
 * Currency rate: 1.0
 * Discounts: 1
 * 1. 10% discount for basket
 * Vouchers: -
 * Wrapping:  -
 * Gift cart: -
 * Scale price: for product (1001)
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS  -
 * Short description:
 * Calculate VAT according to the max value  .
 * Neto-Brutto mode. Additiona products Neto-Neto.
 */
$aData = [
    // Articles
    'articles' => [
        0 => [
                // oxarticles db fields
                'oxid'                     => 1001,
                'oxprice'                  => 1.00,
                'oxvat'                    => 20,
                // Amount in basket
                'amount'                   => 2,
        ],
        1 => [
         // oxarticles db fields
                'oxid'                  => 1002,
                'oxprice'               => 2.00,
                'oxvat'                 => 30,
                // Amount in basket
                'amount'                => 2,
        ],
     ],

    'discounts' => [
        // oxdiscount DB fields
        0 => [
            // ID needed for expectation later on, specify meaningful name
            'oxid'         => '%discount',
            'oxaddsum'     => 10,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxsort' => 10,
        ],
      ],

    // Additional costs
    'costs' => [
        // Delivery
        'delivery' => [
              0 => [
                // oxdelivery DB fields
                'oxactive' => 1,
                'oxaddsum' => 50.00,
                'oxaddsumtype' => '%',
                'oxdeltype' => 'p',
                'oxfinalize' => 1,
                'oxparamend' => 99999,
            ],
        ],
        // Payment
        'payment' => [
             0 => [
                // oxpayments DB fields
                'oxaddsum' => 1.00,
                'oxaddsumtype' => 'abs',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
        'oxaddsumrules'=>1,
            ],
        ],
    ],
    // TEST EXPECTATIONS
    'expected' => [
        1 => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1001 => [ '1,20', '2,40' ],
             1002 => [ '2,60', '5,20' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '7,60',
            // Total NETTO
            'totalNetto'  => '5,40',
            // Total VAT amount: vat% => total cost
            'vats' => [
                20 => '0,36',
                30 => '1,08',
            ],
             'discount' => '0,76',

            // Total delivery amounts
            'delivery' => [
                'brutto' => '4,94',
                'netto' => '3,80',
                'vat' => '1,14',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '1,30',
                'netto' => '1,00',
                'vat' => '0,30',
            ],

            // GRAND TOTAL
            'grandTotal'  => '13,08',
        ],
        ],
        2 => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1001 => [ '1,20', '2,40' ],
             1002 => [ '2,60', '5,20' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '7,60',
            // Total NETTO
            'totalNetto'  => '5,40',
            // Total VAT amount: vat% => total cost
            'vats' => [
                20 => '0,36',
                30 => '1,08',
            ],
             'discount' => '0,76',

            // Total delivery amounts
            'delivery' => [
                'brutto' => '4,94',
                'netto' => '3,80',
                'vat' => '1,14',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '1,30',
                'netto' => '1,00',
                'vat' => '0,30',
            ],

            // GRAND TOTAL
            'grandTotal'  => '13,08',
        ],
        ],
    ],
    // Test case options
    'options' => [
        // Configs (real named)
        'config' => [
            'blEnterNetPrice' => true,
            'blShowNetPrice' => false,
            'blShowVATForDelivery'=> true,
            'blShowVATForPayCharge'=> true,
            'blShowVATForWrapping'=> true,
            'sAdditionalServVATCalcMethod' => 'biggest_net',
            'blDeliveryVatOnTop' => true,
            'blPaymentVatOnTop' => true,
            'blWrappingVatOnTop' => true,
        ],
        // Other options
        'activeCurrencyRate' => 1,
    ],
    'actions' => [
            '_changeArticles' => [
                    0 => [
                            'oxid'       => '222',
                            'amount'     => 1,
                    ],
            ],
    ],
];
