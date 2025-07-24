<?php

/*
/**
 * Price enter mode: netto
 * Price view mode:  netto
 * Product count: 2
 * VAT info: 19% Default VAT for all Products , additional VAT=10% for product 1001
 * Currency rate: 1.0
 * Discounts: 2
 *  1. discount item for product 1002
 *  2. discount for basket 55%

 * Vouchers: -
 * Wrapping: +
 * Costs VAT caclulation rule: proportionality
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS -
 * Short description:
 * Calculate VAT proportionately . Neto-Neto mode. Additiona products Neto-Neto. Also is testing item discount for basket.
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
        2 => [
            // oxarticles db fields
            'oxid'                     => 1004,
            'oxprice'                  => 200.00,
            'oxvat'                    => 19,
            // Amount in basket
        ],
    ],
    // Discounts
    'discounts' => [
        // oxdiscount DB fields
        0 => [
            // item discount for basket
            'oxid'         => 'discountitm',
            'oxaddsum'     => 0,
            'oxaddsumtype' => 'itm',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxitmartid' => 1004,
            'oxitmamount' => 1,
            'oxitmultiple' => 1,
            'oxarticles' => [ 1002 ],
            'oxsort' => 10,
        ],
        1 => [
            // 55% discount for basket
            'oxid'         => 'discountforbasket55%',
            'oxaddsum'     => 55,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxsort' => 20,
        ],
    ],
    // Additional costs
    'costs' => [
     // Wrappings
        'wrapping' => [
            // Giftcard
           0 => [
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
            1004 => [ '0,00', '0,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '255,60',
            // Total NETTO
            'totalNetto'  => '500,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                10 => '13,50',
                19 => '17,10',
            ],
            'discounts' => [
                // Expectation for special discount with specified ID
                'discountforbasket55%' => '275,00',
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
            'grandTotal'  => '883,24',
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
