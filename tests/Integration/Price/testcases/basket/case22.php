<?php

/*
/**
 * Price enter mode: netto
 * Price view mode:  brutto
 * Product count: 2
 * VAT info: 19% Default VAT for all Products
 * Currency rate: 1.0
 * Discounts: 5
 *  1. shop discount 5abs for product 100
 *  2. shop discount 5% for product 1001
 *  3. basket discount 1 abs for product 100
 *  4. basket discount 6% for product 1001
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
            'oxid'                     => 100,
            'oxprice'                  => 100,
            'oxvat'                    => 19,
            'amount'                   => 33,
        ],
        1 => [
            'oxid'                     => 1001,
            'oxprice'                  => 66,
            'oxvat'                    => 19,
            'amount'                   => 33,
        ],
    ],
    'discounts' => [
        0 => [
            'oxid'         => 'shopdiscount5for100',
            'oxaddsum'     => 5,
            'oxaddsumtype' => 'abs',
            'oxamount' => 0,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 100 ],
            'oxsort' => 10,
        ],
        1 => [
            'oxid'         => 'shopdiscount5for1001',
            'oxaddsum'     => 5,
            'oxaddsumtype' => '%',
            'oxamount' => 0,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 1001 ],
            'oxsort' => 20,
        ],
        2 => [
            'oxid'         => 'basketdiscount5for100',
            'oxaddsum'     => 1,
            'oxaddsumtype' => 'abs',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 100 ],
            'oxsort' => 30,
        ],
        3 => [
            'oxid'         => 'basketdiscount5for1001',
            'oxaddsum'     => 6,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 1001 ],
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
                'oxname' => 'testWrap9005',
                'oxprice' => 9,
                'oxactive' => 1,
                'oxarticles' => [ 100 ],
            ],
            1 => [
                'oxtype' => 'WRAP',
                'oxname' => 'testWrap9006',
                'oxprice' => 6,
                'oxactive' => 1,
                'oxarticles' => [ 1001 ],
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
             100 => [ '113,00', '3.729,00' ],
             1001 => [ '70,13', '2.314,29' ],
        ],
        'totals' => [
            'totalBrutto' => '6.043,29',
            'totalNetto'  => '5.069,15',
            'vats' => [
                19 => '963,14',
            ],
            'discounts' => [
                'absolutebasketdiscount' => '5,00',
            ],
            'wrapping' => [
                'brutto' => '495,00',
                'netto' => '415,97',
                'vat' => '79,03',
            ],
            'delivery' => [
                'brutto' => '6,00',
                'netto' => '5,04',
                'vat' => '0,96',
            ],
            'payment' => [
                'brutto' => '1,00',
                'netto' => '0,84',
                    'vat' => '0,16',
            ],
            'voucher' => [
                'brutto' => '6,00',
            ],
            'grandTotal'  => '6.534,29',
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
        'activeCurrencyRate' => 1.00,
    ],
];
