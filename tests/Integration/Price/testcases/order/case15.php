<?php

/*
/**
 * Price enter mode: netto
 * Price view mode:  netto
 * Product count: 2
 * VAT info: 19% Default VAT for all Products , additional VAT=10% for product 1001
 * Currency rate: 1.0
 * Discounts: 1
 *  1. discount item for product 1002
 * Vouchers: +
 * Wrapping: +
 * Costs VAT caclulation rule: biggest_net
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS -
 * Short description:
 * Netto - Netto start case, after order saving, added two product's,
 * updating, changed product (1001) amound from 15 to 10, changed mode from N/N to Netto/Brutto
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
                'oxaddsum' => 10.00,
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
                'oxaddsum' => 275,
                'oxaddsumtype' => 'abs',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
            ],
        ],
        'voucherserie' => [
            0 => [
                'oxdiscount' => 10.00,
                'oxdiscounttype' => '%',
                'oxallowsameseries' => 1,
                'oxallowotherseries' => 1,
                'oxallowuseanother' => 1,
                'voucher_count' => 1,
            ],
        ],
    ],

    // TEST EXPECTATIONS
    'expected' => [
     1 => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
            1001 => [ '20,00', '300,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '297,00',
            // Total NETTO
            'totalNetto'  => '300,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                10 => '27,00',
            ],

            // Total delivery amounts
            'delivery' => [
                'brutto' => '11,00',
                'netto' => '10,00',
                'vat' => '1,00',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '302,50',
                'netto' => '275,00',
                'vat' => '27,50',
            ],
            'discount'  => '0,00',
                'voucher' => [
                'brutto' => '30,00',
            ],
            // Total giftcard amounts
            'giftcard' => [
                'brutto' => '2,75',
                'netto' => '2,50',
                'vat' => '0,25',
            ],
            // GRAND TOTAL
            'grandTotal'  => '613,25',
            ],
        ],
    2 => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
            1001 => [ '20,00', '200,00' ],
            1002 => [ '200,00', '200,00' ],
            1004 => [ '0,00', '0,00' ],
            1006 => [ '10,00', '10,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '422,91',
            // Total NETTO
            'totalNetto'  => '410,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '35,91',
                10 => '18,00',
            ],

            // Total delivery amounts
            'delivery' => [
                'brutto' => '11,90',
                'netto' => '10,00',
                'vat' => '0,90',
            ],
            // Total payment amounts
            'discount'  => '0,00',
            'payment' => [
                'brutto' => '327,25',
                'netto' => '275,00',
                'vat' => '52,25',
            ],
                'voucher' => [
                'brutto' => '41,00',
            ],
            // Total giftcard amounts
            'giftcard' => [
                'brutto' => '2,98',
                'netto' => '2,50',
                'vat' => '0,48',
            ],
            // GRAND TOTAL
            'grandTotal'  => '765,04',
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
            '_changeConfigs' => [
                    'blShowNetPrice' => false,
            ],
             // '_removeArticles' => array ( '1001' ),
            '_changeArticles' => [
                    0 => [
                            'oxid'       => '1001',
                            'amount'     => 10,
                    ],
        ],
            '_addArticles' => [
                    0 => [
                            'oxid'       => '1006',
                            'oxtitle'    => '1006',
                            'oxprice'    => 10,
                            'oxvat'      => 19,
                            'oxstock'    => 999,
                            'amount' => 1,
                     ],
                    1 => [
                            'oxid'       => '1002',
                            'oxtitle'    => '1002',
                            'oxprice'    => 200,
                            'oxvat'      => 19,
                            'oxstock'    => 999,
                            'amount' => 1,
                    ],
        ],
    ],
];
