<?php

/**
 * Price enter mode: netto
 * Price view mode: netto
 * Product count: 1001 and 1002
 * VAT info: count of used vat's (list)
 * Currency rate: 1.0
 * Discounts: -
 * Wrapping:  -
 * Gift cart: -;
 * Costs VAT caclulation rule: proportiona
 * Wrapping: -;
 * Gift cart:  -;
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS -
 * Short description:
 * Neto-Neto mode. Additiona products Neto-Neto. Calculate VAT according to the proportional value
 */
$aData = [
    // Product
    'articles' => [
        0 => [
                // oxarticles db fields
                'oxid'                     => 1001,
                'oxprice'                  => 30.00,
                'oxvat'                    => 25,
                // Amount in basket
                'amount'                   => 15,
        ],
        1 => [
         // oxarticles db fields
                'oxid'                     => 1002,
                'oxprice'                  => 100.00,
                'oxvat'                    => 20,
                'amount'                   => 15,
        ],
    ],

         // Additional costs
    'costs' => [
        // oxwrapping db fields
        // Delivery
        'delivery' => [
              0 => [
                // oxdelivery DB fields
                'oxactive' => 1,
                'oxaddsum' => 55.00,
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
                'oxaddsum' => 55.00,
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
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1001 => [ '30,00', '450,00' ],
             1002 => [ '100,00', '1.500,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '2.362,50',
            // Total NETTO
            'totalNetto'  => '1.950,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                25 => '112,50',
                20 => '300,00',
            ],
              // Total delivery amounts
        'delivery' => [
                'brutto' => '1.299,38',
                'netto' => '1.072,50',
                'vat' => '226,88',
         ],
            // Total payment amounts
        'payment' => [
                'brutto' => '1.299,38',
                'netto' => '1.072,50',
                'vat' => '226,88',
        ],
            // GRAND TOTAL
            'grandTotal'  => '4.961,26',
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
            'sAdditionalServVATCalcMethod' => 'proportional',
            'blDeliveryVatOnTop' => true,
            'blPaymentVatOnTop' => true,
            'blWrappingVatOnTop' => true,
        ],
        // Other options
        'activeCurrencyRate' => 1,
    ],
];
