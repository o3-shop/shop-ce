<?php

/*
 * Price enter mode: brutto
 * Price view mode:  brutto
 * Product count: 1
 * Discounts: 1
 *  1. 500abs discount for basket
 * Short description:
 * @bug #3727:
 * Discount with such options:
 * FROM-TO range of units: 1-99999
 * Sum: 500 abs (500 EUR)
 * Product price less than discount value.
 */
$aData = [
    'articles' => [
         0 => [
             'oxid'                     => '3727',
             'oxprice'                  => 5,
             'amount'                   => 1,
         ],
     ],
    'discounts' => [
        0 => [
            'oxid'         => 'discount500forShop',
            'oxaddsum'     => 500,
            'oxaddsumtype' => 'abs',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
            'oxsort' => 10,
        ],
    ],
    'expected' => [
        'articles' => [
            '3727' => [ '5,00', '5,00' ],
        ],
        'totals' => [
            'totalBrutto' => '5,00',
            'discounts' => [
                'discount500forShop' => '5,00',
            ],
            'totalNetto'  => '0,00',
            'vats' => [
                '19' => '0,00',
            ],
            'grandTotal'  => '0,00',
        ],
    ],
    'options' => [
            'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
            ],
    ],
];
