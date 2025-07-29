<?php

/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 1
 * VAT info: count of used vat's =19%
 * Currency rate: -
 * Discounts: 1
 *  1. 2,55% discount for shop
 * Vouchers: -
 * Wrapping:  -
 * Gift cart: -
 * Short description:  Vat and rounding issue test case: shop discount without articles ( Discount (from 0 unit to 99999) ) */
$aData = [
     'articles' => [
             0 => [
                 'oxid'    => 'rounding_issue_test_article',
                 'oxprice' => 298.55,
                 'oxvat'   => 19,
                 'amount'  => 200,
             ],
     ],
    'discounts' => [
        0 => [
            'oxid'         => 'discount_2_55_forShop',
            'oxaddsum'     => 2.55,
            'oxaddsumtype' => '%',
            'oxamount' => 0,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxsort' => 10,
        ],
    ],
    'expected' => [
        'articles' => [
            'rounding_issue_test_article' => [ '290,94', '58.188,00' ],
        ],
        'totals' => [
            'totalBrutto' => '58.188,00',
            'totalNetto'  => '48.897,48',
            'vats' => [
                    '19' => '9.290,52',
            ],
            'grandTotal'  => '58.188,00',
        ],
    ],
    'options' => [
            'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
            ],
    ],
];
