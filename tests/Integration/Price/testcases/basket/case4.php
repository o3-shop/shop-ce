<?php

/*
/**
 * Price enter mode: netto
 * Price view mode:  brutto
 * Product count: 2
 * VAT info: 19% Default VAT for all Products
 * Currency rate: 0.68
 * Discounts: 5
 *  1. shop discount 5abs for product 9007
 *  2. shop discount 5% for product 9008
 *  3. basket discount 1 abs for product 9007
 *  4. basket discount 6% for product 9008
 *  5. absolute basket discount 5 abs

 * Vouchers: 1
 *  1.  vouchers 6.00 abs

 * Wrapping: +
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment +
 *  2. Delivery +
 *  3. TS -
 * Short description:
 * From advBasketCalc.csv: Complex order calculation IV order.
 */
$aData = [
    'articles' => [
        0 => [
            'oxid'                     => 9007,
            'oxprice'                  => 100,
            'oxvat'                    => 19,
            'amount'                   => 33,
        ],
        1 => [
            'oxid'                     => 9008,
            'oxprice'                  => 66,
            'oxvat'                    => 19,
            'amount'                   => 16,
        ],
    ],
    'discounts' => [
        0 => [
            'oxid'         => 'shopdiscount5for9007',
            'oxaddsum'     => 5,
            'oxaddsumtype' => 'abs',
            'oxamount' => 0,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9007 ],
            'oxsort' => 10,
        ],
        1 => [
            'oxid'         => 'shopdiscount5for9008',
            'oxaddsum'     => 5,
            'oxaddsumtype' => '%',
            'oxamount' => 0,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9008 ],
            'oxsort' => 20,
        ],
        2 => [
            'oxid'         => 'basketdiscount5for9007',
            'oxaddsum'     => 1,
            'oxaddsumtype' => 'abs',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9007 ],
            'oxsort' => 30,
        ],
        3 => [
            'oxid'         => 'basketdiscount5for9008',
            'oxaddsum'     => 6,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9008 ],
            'oxsort' => 40,
        ],
        4 => [
            'oxid'         => 'absolutebasketdiscount',
            'oxaddsum'     => 5,
            'oxaddsumtype' => 'abs',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxsort' => 50,
        ],
    ],
    'costs' => [
        'wrapping' => [
            0 => [
                'oxtype' => 'WRAP',
                'oxname' => 'testWrap9007',
                'oxprice' => 9,
                'oxactive' => 1,
                'oxarticles' => [ 9007 ],
            ],
            1 => [
                'oxtype' => 'WRAP',
                'oxname' => 'testWrap9008',
                'oxprice' => 6,
                'oxactive' => 1,
                'oxarticles' => [ 9008 ],
            ],
        ],
        'delivery' => [
            0 => [
                'oxtitle' => '6_abs_del',
                'oxactive' => 1,
                'oxaddsum' => 6,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'p',
                'oxfinalize' => 1,
                'oxparamend' => 99999,
            ],
        ],
        'payment' => [
            0 => [
                'oxtitle' => '1 abs payment',
                'oxaddsum' => 1,
                'oxaddsumtype' => 'abs',
                'oxfromamount' => 0,
                'oxtoamount' => 1000000,
                'oxchecked' => 1,
            ],
        ],
        'voucherserie' => [
            0 => [
                'oxdiscount' => 6.00,
                'oxdiscounttype' => 'absolute',
                'oxallowsameseries' => 1,
                'oxallowotherseries' => 1,
                'oxallowuseanother' => 1,
                'voucher_count' => 1,
            ],
        ],
    ],
    'expected' => [
        'articles' => [
             9007 => [ '76,84', '2.535,72' ],
             9008 => [ '47,70', '763,20' ],
        ],
        'totals' => [
            'totalBrutto' => '3.298,92',
            'totalNetto'  => '2.765,92',
            'vats' => [
                19 => '525,52',
            ],
            'discounts' => [
                'absolutebasketdiscount' => '3,40',
            ],
            'wrapping' => [
                'brutto' => '267,24',
                'netto' => '224,57',
                'vat' => '42,67',
            ],
            'delivery' => [
                'brutto' => '4,08',
                'netto' => '3,43',
                'vat' => '0,65',
            ],
            'payment' => [
                'brutto' => '0,68',
                'netto' => '0,57',
                    'vat' => '0,11',
            ],
            'voucher' => [
                'brutto' => '4,08',
            ],
            'grandTotal'  => '3.563,44',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => true,
                'blShowNetPrice' => false,
                'blShowVATForWrapping' => true,
                'blShowVATForPayCharge' => true,
                'blShowVATForDelivery' => true,
        ],
        'activeCurrencyRate' => 0.68,
    ],
];
