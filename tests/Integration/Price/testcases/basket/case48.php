<?php

/**
 * Price enter mode:  brutto
 * Price view mode: netto
 * Product count: count of used products
 * VAT info: count of used vat's (list)
 * Currency rate: 1.0 (change if needed)
 * Discounts: count
 *  1. basket 125 ab
 * Wrapping:  -
 * Gift cart: -;
 * Costs VAT caclulation rule: proportiona
 * 5 products with different vat. Absolute discount. Bruto-Bruto mode.
 */
$aData = [
    // Product
    'articles' => [
        0 => [
            // oxarticles db fields
            'oxid'                     => 1001,
            'oxprice'                  => 1002.55,
            'oxvat'                    => 19,
            // Amount in basket
            'amount'                   => 2,
        ],
        1 => [
            // oxarticles db fields
            'oxid'                     => 1002,
            'oxprice'                  => 11.56,
            'oxvat'                    => 13,
            // Amount in basket
            'amount'                   => 2,
        ],
        2 => [
            // oxarticles db fields
            'oxid'                     => 1003,
            'oxprice'                  => 1326.89,
            'oxvat'                    => 3,
            // Amount in basket
            'amount'                   => 6,
        ],
        3 => [
            // oxarticles db fields
            'oxid'                     => 1004,
            'oxprice'                  => 6.66,
            'oxvat'                    => 17,
            // Amount in basket6
            'amount'                   => 6,
        ],
        4 => [
            // oxarticles db fields
            'oxid'                     => 1005,
            'oxprice'                  => 0.66,
            'oxvat'                    => 33,
            // Amount in basket
            'amount'                   => 6,
        ],
    ],
    // Discounts
    'discounts' => [
        // oxdiscount DB fields
        0 => [
            // ID needed for expectation later on, specify meaningful name
            'oxid'         => 'absdiscount',
            'oxaddsum'     => 125.55,
            'oxaddsumtype' => 'abs',
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
            1001 => [ '842,48', '1.684,96' ],
            1002 => [ '10,23', '20,46' ],
            1003 => [ '1.288,24', '7.729,44' ],
            1004 => [ '5,69', '34,14' ],
            1005 => [ '0,50', '3,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '9.900,49', //W:9.900,48
            // Total NETTO
            'totalNetto'  => '9.472,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '315,90',
                13 => '2,62',
                3  => '228,81',
                17 => '5,73',
                33 => '0,98',
            ],
            // Total discount amounts: discount id => total cost
            'discounts' => [
                // Expectation for special discount with specified ID
                'absdiscount' => '125,55',
            ],
            // Total delivery amounts
            'delivery' => [
                'brutto' => '3,14',
                'netto' => '3,05',
                'vat' => '0,09',
            ],
            // Total payment amounts
            'payment' => [
               'brutto' => '7,59',
                'netto' => '7,37',
                'vat' => '0,22',
            ],
            // GRAND TOTAL
            'grandTotal'  => '9.911,22',
        ],
    ],
    // Test case options
    'options' => [
        // Configs (real named)
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice' => true,
            'blShowVATForPayCharge' => true,
            'blShowVATForDelivery' => true,
        ],
        // Other options
        'activeCurrencyRate' => 1,
    ],
];
