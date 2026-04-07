<?php

/**
 * Price enter mode: netto
 * Price view mode:  netto
 * Product count: count of used products
 * VAT info: 19%
 * Currency rate: 1
 * Discounts: -
 * Vouchers: -
 * Wrapping: -;
 * Gift cart:  -;
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS -
 * Short description:
 * Neto-Neto mode. Additiona products Neto-Neto.
 */
$aData = [
    'articles' => [
        0 => [
            'oxid'                     => 9001,
            'oxprice'                  => 10,
            'oxvat'                    => 19,
            'amount'                   => 250,
        ],
    ],
    'costs' => [
        'delivery' => [
            0 => [
                'oxtitle' => '6_abs_del',
                'oxactive' => 1,
                'oxaddsum' => 10,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'p',
                'oxfinalize' => 1,
                'oxparamend' => 99999,
            ],
        ],
        'payment' => [
            0 => [
                'oxtitle' => '1 abs payment',
                'oxaddsum' => 10,
                'oxaddsumtype' => 'abs',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
            ],
        ],
    ],
    'expected' => [
        'articles' => [
             9001 => [ '10,00', '2.500,00' ],
        ],
        'totals' => [
            'totalBrutto' => '2.975,00',
            'totalNetto'  => '2.500,00',
            'vats' => [
                19 => '475,00',
            ],
            'delivery' => [
                'brutto' => '11,90',
                'netto' => '10,00',
                'vat' => '1,90',
            ],
            'payment' => [
                'brutto' => '11,90',
                'netto' => '10,00',
                'vat' => '1,90',
            ],
            'grandTotal'  => '2.998,80',
        ],
    ],
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
