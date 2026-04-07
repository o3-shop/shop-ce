<?php

/*
 * Price enter mode: netto / brutto
 * Price view mode: netto / brutto
 * Product count: count of used products
 * VAT info: count of used vat's (list)
 5 different VAT
 * Currency rate: 1.0 (change if needed)
 * Wrapping:  +;
 * Gift cart: +;
 * Costs VAT caclulation rule: proportiona
 * Short description:  5 products with different vat. Payment, shipping, greeting card and wrapping fees.  Calculate VAT proportionately . Bruto-Neto mode. Additiona products Neto-Neto.
 */

$aData = [
    // Product
    'articles' => [
         0 => [
            // oxarticles db fields
            'oxid'                     => 1001,
            'oxprice'                  => 1382.42,
            'oxvat'                    => 19,
            // Amount in basket
            'amount'                   => 2,
        ],
        1 => [
            // oxarticles db fields
            'oxid'                     => 1002,
            'oxprice'                  => 13.58,
            'oxvat'                    => 13,
            // Amount in basket
            'amount'                   => 14,
        ],
        2 => [
            // oxarticles db fields
            'oxid'                     => 1003,
            'oxprice'                  => 1756.66,
            'oxvat'                    => 3,
            // Amount in basket
            'amount'                   => 13,
        ],
        3 => [
            // oxarticles db fields
            'oxid'                     => 1004,
            'oxprice'                  => 13.64,
            'oxvat'                    => 17,
            // Amount in basket
            'amount'                   => 62,
        ],
    ],
    // Additional costs
    'costs' => [
     // Wrappings
        'wrapping' => [
            // oxwrapping DB fields
            0 => [
                'oxtype' => 'WRAP',
                'oxname' => 'testWrap9001',
                'oxprice' => 3.98,
                'oxactive' => 1,

                // If for article, specify here
                'oxarticles' => [ 1001 ],
            ],
            1 => [
                'oxtype' => 'WRAP',
                'oxname' => 'testWrap9002',
                'oxprice' => 1.47,
                'oxactive' => 1,

                // If for article, specify here
                'oxarticles' => [ 1002 ],
            ],
           2 => [
                'oxtype' => 'WRAP',
                'oxname' => 'testWrap9003',
                'oxprice' => 2.14,
                'oxactive' => 1,

                // If for article, specify here
                'oxarticles' => [ 1003 ],
            ],
            // Giftcard
           3 => [
                'oxtype' => 'CARD',
                'oxname' => 'testCard9001',
                'oxprice' => 2.97,
                'oxactive' => 1,
            ],
        ],
        // Delivery
        'delivery' => [
            0 => [
                // oxdelivery DB fields
                'oxactive' => 1,
                'oxaddsum' => 3.14,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'p',
                'oxfinalize' => 1,
                'oxparamend' => 99999,
            ],
        ],
        // Payment
        'payment' => [
            0 => [
                // oxpayments DB fields
                'oxaddsum' => 7.59,
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
            1001 => [ '1.161,70', '2.323,40' ],
            1002 => [ '12,02', '168,28' ],
            1003 => [ '1.705,50', '22.171,50' ],
            1004 => [ '11,66', '722,92' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '26.637,48',
            // Total NETTO
            'totalNetto'  => '25.386,10', //W:'25.286,10',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '441,45',
                13 => '21,88',
                3  => '665,15',
                17 => '122,90',
            ],
            // Total delivery amounts
            'delivery' => [
                'brutto' => '3,23',
                'netto' => '3,14',
                'vat' => '0,09',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '7,82',
                'netto' => '7,59',
                'vat' => '0,23',
            ],
            // Total wrapping amounts
            'wrapping' => [
                'brutto' => '61,38',
                'netto' => '56,36',
                'vat' => '5,02',
            ],
            // Total giftcard amounts
            'giftcard' => [
                'brutto' => '3,06',
                'netto' => '2,97',
                'vat' => '0,09',
            ],
            // GRAND TOTAL
            'grandTotal'  => '26.712,97', //W: '26.707,28'
        ],
    ],
    // Test case options
    'options' => [
        // Configs (real named)
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice' => true,
            'blShowVATForDelivery'=> true,
            'blShowVATForPayCharge'=> true,
            'blShowVATForWrapping'=> true,
            'sAdditionalServVATCalcMethod' => 'biggest_net', //W: true,
            'blDeliveryVatOnTop' => true,
            'blPaymentVatOnTop' => true,
            'blWrappingVatOnTop' => true,
        ],
        // Other options
        'activeCurrencyRate' => 1,
    ],
];
