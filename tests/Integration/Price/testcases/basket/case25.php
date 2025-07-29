<?php

/*
 * Calculate VAT proportionately . Neto-Neto mode. Additiona products Neto-Neto.
*/
$aData = [
    // Product
    'articles' => [
         0 => [
            // oxarticles db fields
            'oxid'                     => 1001,
            'oxprice'                  => 20.00,
            'oxvat'                    => 10,
            // Amount in basket
            'amount'                   => 15,
        ],
        1 => [
            // oxarticles db fields
            'oxid'                     => 1002,
            'oxprice'                  => 200.00,
            'oxvat'                    => 19,
            // Amount in basket
            'amount'                   => 1,
        ],
    ],
    // Additional costs
    'costs' => [
     // Wrappings
        'wrapping' => [
            // Giftcard
           3 => [
                'oxtype' => 'CARD',
                'oxname' => 'testCard1001',
                'oxprice' => 2.50,
                'oxactive' => 1,
            ],
        ],
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
                'oxaddsum' => 275,
                'oxaddsumtype' => 'abs',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
            ],
        ],
    ],

    // TEST EXPECTATIONS
    'expected' => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
            1001 => [ '20,00', '300,00' ],
            1002 => [ '200,00', '200,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '568,00',
            // Total NETTO
            'totalNetto'  => '500,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                10 => '30,00',
                19 => '38,00',
            ],
            // Total delivery amounts
            'delivery' => [
                'brutto' => '312,40',
                'netto' => '275,00',
                'vat' => '37,40',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '312,40',
                'netto' => '275,00',
                'vat' => '37,40',
            ],

            // Total giftcard amounts
            'giftcard' => [
                'brutto' => '2,84',
                'netto' => '2,50',
                'vat' => '0,34',
            ],
            // GRAND TOTAL
            'grandTotal'  => '1.195,64',
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
