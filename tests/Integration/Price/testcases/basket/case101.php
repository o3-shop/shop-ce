<?php

/**
 * Price enter mode: netto
 * Price view mode: netto
 * Product count: 5
 * VAT info: count of used vat =2(33% and 50%)
 * Currency rate: 1.00
 * Discounts: 1
 *  1.  10% discount for basket
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS  -
 * Vouchers: -
 * Wrapping:  -
 * Gift cart: -
 * Short description:
 * Vat and rounding issue. 5 products.
 * for all products VAT=33%, spec. one discount for basket.Mode Neto-Neto
 */
$aData = [
    // Articles
    'articles' => [
        0 => [
            // oxarticles db fields
            'oxid'                     => 111,
            'oxprice'                  => 0.55,
            'oxvat'                    => 33,
            // Amount in basket
            'amount'                   => 1,
        ],
        1 => [
         // oxarticles db fields
            'oxid'                     => 1112,
            'oxprice'                  => 1101.10,
            'oxvat'                    => 33,
            // Amount in basket
            'amount'                   => 1,
        ],
        2 => [
         // oxarticles db fields
            'oxid'                     => 1113,
            'oxprice'                  => 110,
            'oxvat'                    => 33,
            // Amount in basket
            'amount'                   => 1,
        ],
        3 => [
         // oxarticles db fields
            'oxid'                     => 1114,
            'oxprice'                  => 1.00,
            'oxvat'                    => 33,
            // Amount in basket
            'amount'                   => 1,
        ],
        4 => [
         // oxarticles db fields
            'oxid'                     => 1115,
            'oxprice'                  => 945.95,
            'oxvat'                    => 50,
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
                'oxaddsum' => 55,
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
             111 => [ '0,55', '0,55' ],
             1112 => [ '1.101,10', '1.101,10' ],
             1113 => [ '110,00', '110,00' ],
             1114 => [ '1,00', '1,00' ],
             1115 => [ '945,95', '1.891,90' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '4.005,61',
            // Total NETTO
            'totalNetto'  => '3.104,55',
            // Total VAT amount: vat% => total cost
            'vats' => [
                33 => '360,16',
                50 => '851,36',
            ],
            // Total discount amounts: discount id => total cost
            'discounts' => [
                // Expectation for special discount with specified ID
                'discountforbasket10%' => '310,46',
            ],

            // Total delivery amounts
            'delivery' => [
                'brutto' => '2.561,25',
                'netto' => '1.707,50',
                'vat' => '853,75',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '82,50',
                'netto' => '55,00',
                'vat' => '27,50',
            ],
            // GRAND TOTAL
            'grandTotal'  => '6.649,36',
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
            'blPaymentVatOnTop'=>true,
        ],
        // Other options
        'activeCurrencyRate' => 1,
    ],
];
