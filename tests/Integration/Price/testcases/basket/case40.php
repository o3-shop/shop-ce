<?php

/**
 * Price enter mode: netto
 * Price view mode: brutto
 * Product count: 3
 * VAT info: count of used vat's =2 (19% and 11%)
 * Currency rate: 3.0
 * Discounts: 2
 *  1. itm discount for product (1002)
 *  2. 10% discount for basket
 *  ...
 * Vouchers: 1
 *  1. 10% voucher
 * Wrapping:  -
 * Gift cart: +
 * Costs VAT caclulation rule: biggest_net
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS  -
 * Short description: Calculate VAT according to the biggest net value  .
 * Neto-brutto mode. Additiona products Neto-Neto. Also is testing item discount for basket.
 * User is assignet to user group "priceA", for user groups is created two discount (itm, 10%) ;
 */
$aData = [
    // Articles
    'articles' => [
        0 => [
                // oxarticles db fields
                'oxid'                     => 1001,
                'oxprice'                  => 20.00,
                'oxvat'                    => 11,
                // Amount in basket
                'amount'                   => 2,
                'scaleprices' => [
                //        'oxaddabs'     => 0.00,
                        'oxamount'     => 2,
                        'oxamountto'   => 3,
                        'oxartid'      => 1001,
                        'oxaddperc'    => 10,
                ],
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
                'OXSHOPID'				   => 2,
    ],
    ],

    // User
    'user' => [
            'oxactive' => 1,
            'oxusername' => 'basketUser',
    ],
    // Group
    'group' => [
            0 => [
                    'oxid' => 'oxidpricea',
                    'oxactive' => 1,
                    'oxtitle' => 'Price A',
                    'oxobject2group' => [
                            'oxobjectid' => [ 1001, 'basketUser' ],
                            'oxobjectid' => [ 1002, 'basketUser' ],
                            'oxobjectid' => [ 'itmdiscount', 'basketUser' ],
                            'oxobjectid' => [ '%discount', 'basketUser' ],
                    ],
            ],
    ],
    // Discounts
    'shop' => [
        0 => [
                'oxactive'     => 1,
                'oxparentid'   => 1,
                'oxname'       => 'subshop',
                // this option sets shop to active or not
                'activeshop'   => true,
        ],
    ],
    'discounts' => [
        // oxdiscount DB fields
        0 => [
            // ID needed for expectation later on, specify meaningful name
            'oxid'         => '%discount',
            'oxaddsum'     => 10,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxsort' => 10,
        ],
        1 => [
                // item discount for basket
            'oxid'         => 'itmdiscount',
            'oxaddsum'     => 0,
            'oxaddsumtype' => 'itm',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxitmartid' => 1004,
            'oxitmamount' => 1,
            'oxitmultiple' => 1,
            'oxarticles' => [ 1002 ],
            'oxsort' => 20,
        ],
    ],
    // Additional costs
    'costs' => [
        // oxwrapping db fields
        'wrapping' => [
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
                'oxaddsum' => 55.00,
                'oxaddsumtype' => '%',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
                'oxaddsumrules'=>1,
            ],
        ],
        // VOUCHERS
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
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1001 => [ '18,00', '36,00' ],
             1002 => [ '200,00', '200,00' ],
             1004 => [ '0,00', '0,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '225,15',
            // Total NETTO
            'totalNetto'  => '236,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '30,78',
                11 => '3,21',
            ],
            // Total discount amounts: discount id => total cost
            'discounts' => [
                // Expectation for special discount with specified ID
                '%discount' => '23,60',
            ],
            // Total giftcard amounts
           'giftcard' => [
                'brutto' => '2,98',
                'netto' => '2,50',
                'vat' => '0,48',
            ],
            // Total delivery amounts
            'delivery' => [
                'brutto' => '154,46',
                'netto' => '129,80',
                'vat' => '24,66',
            ],
            // Total payment amounts
            'payment' => [
                'brutto' => '154,46',
                'netto' => '129,80',
                'vat' => '24,66',
            ],
            // Total voucher amounts
            'voucher' => [
                'brutto' => '21,24',
            ],
            // GRAND TOTAL
            'grandTotal'  => '537,05',
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
];
