<?php

/**
 * Price enter mode: netto
 * Price view mode: netto
 * Product count: 1
 * VAT info: used VAT =20
 * Currency rate: 1.00
 * Discounts: 1
 * 1. 10% discount for basket
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS  -
 * Vouchers: -
 * Wrapping:  -
 * Gift cart: -
 * Short description:
 * Payment methods calculation in Neto-Neto Mode. Used discount for basket
 */
$aData = [
    // Articles
    'articles' => [
        0 => [
            // oxarticles db fields
            'oxid'                     => 111,
            'oxprice'                  => 1,00,
            'oxvat'                    => 20,
            // Amount in basket
            'amount'                   => 2,
        ],
    ],
 // Discounts
    'discounts' => [
        // oxdiscount DB fields
        0 => [
            // 10% discount for basket
            'oxid'         => 'discountforbasket10%',
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
                'oxaddsum' => 55,
                'oxaddsumtype' => '%',
                'oxdeltype' => 'p',
                'oxfinalize' => 1,
                 'oxparam'=> 0.1,
                'oxparamend' => 99999,
            ],
        ],
        // Payment
        'payment' => [
            0 => [
                // oxpayments DB fields
                'oxaddsum' => 10,
                'oxaddsumtype' => '%',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
                // 1. Value of all goods in cart
                'oxaddsumrules'=>1,
            ],
        ],
    ],
    // TEST EXPECTATIONS
    'expected' => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             111 => [ '1,00', '2,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '2,16',
            // Total NETTO
            'totalNetto'  => '2,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                20 => '0,36',
            ],
        // Total discount amounts: discount id => total cost
        'discounts' => [
            // Expectation for special discount with specified ID
            'discountforbasket10%' => '0,20',
            ],

            // Total delivery amounts
            'delivery' => [
                'brutto' => '1,32',
                'netto' => '1,10',
                'vat' => '0,22',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '0,24',
                'netto' => '0,20',
                'vat' => '0,04',
            ],
            // GRAND TOTAL
            'grandTotal'  => '3,72',
        ],
    ],
    // Test case options
    'options' => [
        // Configs (real named)
        'config' => [
            'blEnterNetPrice' => true,
            'blShowNetPrice' => true,
            'blShowVATForPayCharge' => true,
            'blShowVATForDelivery' => true,
            'blPaymentVatOnTop'=>true,
            'blDeliveryVatOnTop'=>true,
        ],
        // Other options
        'activeCurrencyRate' => 1,
    ],
];
