<?php

/*
/**
 * Price enter mode: netto
 * Price view mode:  brutto
 * Product count: 2
 * VAT info: 19% Default VAT for all Products
 * Currency rate: 1.0
 * Discounts: 5
 *  1. shop discount 5abs for product 9005
 *  2. shop discount 5% for product 9006
 *  3. basket discount 1 abs for product 9005
 *  4. basket discount 6% for product 9006
 *  5. absolute basket discount 5 abs
 * Vouchers: 1
 // *  1.  vouchers 6.00 abs
 * Wrapping: +
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment +
 *  2. Delivery
 * Short description:
 */
$aData = [
    'articles' => [
        0 => [
            'oxid'                     => 9005,
            'oxprice'                  => 100,
            'oxvat'                    => 19,
            'amount'                   => 33,
        ],
        1 => [
            'oxid'                     => 9006,
            'oxprice'                  => 66,
            'oxvat'                    => 19,
            'amount'                   => 16,
        ],
    ],
    'discounts' => [
        0 => [
            'oxid'         => 'shopdiscount5for9005',
            'oxaddsum'     => 5,
            'oxaddsumtype' => 'abs',
            'oxamount' => 0,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9005 ],
            'oxsort' => 10,
        ],
        1 => [
            'oxid'         => 'shopdiscount5for9006',
            'oxaddsum'     => 5,
            'oxaddsumtype' => '%',
            'oxamount' => 0,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9006 ],
            'oxsort' => 20,
        ],
        2 => [
            'oxid'         => 'basketdiscount5for9005',
            'oxaddsum'     => 1,
            'oxaddsumtype' => 'abs',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9005 ],
            'oxsort' => 30,
        ],
        3 => [
            'oxid'         => 'basketdiscount5for9006',
            'oxaddsum'     => 6,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxarticles' => [ 9006 ],
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
                'oxarticles' => [ 9005 ],
            ],
            1 => [
                'oxtype' => 'WRAP',
                'oxname' => 'testWrap9006',
                'oxprice' => 6,
                'oxactive' => 1,
                'oxarticles' => [ 9006 ],
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
             9005 => [ '113,00', '3.729,00' ],
             9006 => [ '70,13', '1.122,08' ],
        ],
        'totals' => [
            'totalBrutto' => '4.851,08',
            'totalNetto'  => '4.067,29',
            'vats' => [
                19 => '772,79',
            ],
            'discounts' => [
                'absolutebasketdiscount' => '5,00',
            ],
            'wrapping' => [
                'brutto' => '393,00',
                'netto' => '330,25',
                'vat' => '62,75',
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
            'grandTotal'  => '5.240,08',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => true,
                'blShowNetPrice' => false,
                'blShowVATForWrapping' => true,
                'sAdditionalServVATCalcMethod' => 'biggest_net',
                'blShowVATForPayCharge' => true,
                'blShowVATForDelivery' => true,
        ],
        'activeCurrencyRate' => 1.00,
    ],
];
