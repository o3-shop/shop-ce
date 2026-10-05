<?php

/**
 * Price enter mode: netto
 * Price view mode: netto
 * Product count: 2
 * VAT info: count of used vat's =19%
 * Currency rate: 1.0
 * Discounts: 0
 * Vouchers: -
 * Wrapping:  -
 * Gift cart: -
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS  -
 * Short description:
 * Calculate VAT according to the max value.
 * For products (1001, 1002) is set parameter "free shipping" ;
 * Netto - Netto start case, after order saving,
 * Add additional article(1003) updating,
*/
#bug
$aData = [
  'skipped' => 1,
    // Articles
    'articles' => [
        0 => [
                // oxarticles db fields
                'oxid'                     => 1001,
                'oxprice'                  => 20.00,
                'oxvat'                    => 19,
                // Amount in basket
                'amount'                   => 1,
                'oxfreeshipping'        => 1,
        ],
        1 => [
         // oxarticles db fields
                'oxid'                  => 1002,
                'oxprice'               => 200.00,
                'oxvat'                 => 19,
                // Amount in basket
                'amount'                => 1,
              //  'oxfreeshipping'        => 1,
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
                'oxaddsum' => 10.00,
                'oxaddsumtype' => '%',
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
             1001 => [ '20,00', '20,00' ],
             1002 => [ '200,00', '200,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '261,80',
            // Total NETTO
            'totalNetto'  => '220,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '41,80',
            ],
            // Total discount amounts: discount id => total cost
                // Expectation for special discount with specified ID
                'discount'  => '0,00',

            // Total delivery amounts
            'delivery' => [
                'brutto' => '23,80',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '26,18',
            ],

            // GRAND TOTAL
            'grandTotal'  => '311,78',
        ],
        ],
        2 => [
           // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1001 => [ '20,00', '20,00' ],
             1002 => [ '200,00', '200,00' ],
             1003 => [ '2,00', '2,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '264,18',
            // Total NETTO
            'totalNetto'  => '222,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '42,18',
            ],
            // Total discount amounts: discount id => total cost
                // Expectation for special discount with specified ID
                'discount'  => '0,00',

            // Total delivery amounts
            'delivery' => [
                'brutto' => '24,04',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '26,42',
            ],

            // GRAND TOTAL
            'grandTotal'  => '314,64',
        ],
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
        'actions' => [
      /*  '_changeConfigs' => array (
            'blShowNetPrice' => false,
            'blEnterNetPrice' => true,
        ),*/
        '_addArticles' => [
                0 => [
                        'oxid'       => '1003',
                        'oxtitle'    => '1003',
                        'oxprice'    => 2.00,
                        'oxvat'      => 19,
                        'oxstock'    => 999,
                        'amount' => 1,
                ],
        ],
        ],
];
