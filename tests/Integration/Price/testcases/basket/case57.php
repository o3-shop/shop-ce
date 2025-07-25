<?php

/**
 * Price enter mode: netto
 * Price view mode: netto
 * Product count: 5
 * VAT info: 19% for all products
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
 * Netto-Netto mode. Additiona products Neto-Neto.
 */
$aData = [
    // Articles
    'articles' => [
        0 => [
                // oxarticles db fields
                'oxid'                     => 1001,
                'oxprice'                  => 0.55,
                'oxvat'                    => 19,
                // Amount in basket
                'amount'                   => 1,
                    'scaleprices' => [
                        'oxamount'     => 2,
                        'oxamountto'   => 3,
                        'oxartid'      => 1001,
                    //	'oxaddperc'    => 10,
                        'oxaddabs'     => 2.00,
                ],
        ],
        1 => [
         // oxarticles db fields
                'oxid'                  => 1002,
                'oxprice'               => 5.52,
                'oxvat'                 => 19,
                // Amount in basket
                'amount'                => 1,
        ],
        2 => [
         // oxarticles db fields
                'oxid'                  => 1003,
                'oxprice'               => 945.95,
                'oxvat'                 => 19,
                // Amount in basket
                'amount'                => 1,
        ],
        3 => [
         // oxarticles db fields
                'oxid'                  => 1004,
                'oxprice'               => 4.74,
                'oxvat'                 => 19,
                // Amount in basket
                'amount'                => 1,
        ],
        4 => [
         // oxarticles db fields
                'oxid'                  => 1005,
                'oxprice'               => 1.00,
                'oxvat'                 => 19,
                // Amount in basket
                'amount'                => 5,
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
                'oxaddsum' => 10.00,
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
                'oxaddsum' => 7.50,
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
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1001 => [ '0,55', '0,55' ],
             1002 => [ '5,52', '5,52' ],
             1003 => [ '945,95', '945,95' ],
             1004 => [ '4,74', '4,74' ],
             1005 => [ '1,00', '5,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '1.030,04',
            // Total NETTO
            'totalNetto'  => '961,76',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '164,46',
            ],
            // Total discount amounts: discount id => total cost
            'discounts' => [
                // Expectation for special discount with specified ID
                '%discount' => '96,18',
            ],

            // Total delivery amounts
            'delivery' => [
                'brutto' => '114,45',
                'netto' => '96,18',
                'vat' => '18,27',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '8,93',
                'netto' => '7,50',
                'vat' => '1,43',
            ],

            // GRAND TOTAL
            'grandTotal'  => '1.153,42',
        ],
    ],
    // Test case options
    'options' => [
        // Configs (real named)
        'config' => [
            'blEnterNetPrice' => true,
            'blShowNetPrice' => true,
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
];
