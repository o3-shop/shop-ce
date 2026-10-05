<?php

/**
 * Price enter mode: netto
 * Price view mode: netto
 * Product count: 2
 * VAT info: count of used vat's =2 (19% and 11%)
 * Currency rate: 1.0
 * Discounts: 0
 * Vouchers: -
 * Wrapping:  -
 * Gift cart: -
 * Scale price for product (1001),
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS  -
 * Scale price:1
 * 1. amount (2-3), 10% discount
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
                'oxvat'                    => 11,
                // Amount in basket
                'amount'                   => 2,
                'oxfreeshipping'        => 1,
                'scaleprices' => [
                        'oxid'         => 1,
                        'oxamount'     => 2,
                        'oxamountto'   => 3,
                        'oxartid'      => 1001,
                        'oxaddperc'    => 10,
                ],
        ],
        1 => [
         // oxarticles db fields
                'oxid'                  => 1002,
                'oxprice'               => 200.00,
                'oxvat'                 => 19,
                // Amount in basket
                'amount'                => 1,
                'oxfreeshipping'        => 1,
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
             1001 => [ '18,00', '36,00' ],
             1002 => [ '200,00', '200,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '277,96',
            // Total NETTO
            'totalNetto'  => '236,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '38,00',
                11 => '3,96',
            ],
            // Total discount amounts: discount id => total cost
                // Expectation for special discount with specified ID
                'discount'  => '0,00',

            // Total delivery amounts
            'delivery' => [
                'brutto' => '0,00',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '28,08',
            ],

            // GRAND TOTAL
            'grandTotal'  => '306,04',
        ],
        ],
        2 => [
           // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1001 => [ '18,00', '36,00' ],
             1002 => [ '200,00', '200,00' ],
             1003 => [ '10,00', '10,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '289,86',
            // Total NETTO
            'totalNetto'  => '246,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '39,90',
                11 => '3,96',
            ],
            // Total discount amounts: discount id => total cost
                // Expectation for special discount with specified ID
                'discount'  => '0,00',

            // Total delivery amounts
            'delivery' => [
                'brutto' => '1,19',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '29,27',
            ],

            // GRAND TOTAL
            'grandTotal'  => '320,32',
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
                        'oxprice'    => 10.00,
                        'oxvat'      => 19,
                        'oxstock'    => 999,
                        'amount' => 1,
                ],
        ],
        ],
];
