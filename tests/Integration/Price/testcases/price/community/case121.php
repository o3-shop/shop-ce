<?php

/*
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 2
 * Short description: product's variant uses it's own unit quantity and doesn't inherit from parent (bug fix for #3876)
 */
$aData = [
    'articles' => [
        0 => [
            'oxid'            => '_testId_1',
            'oxprice'         => 50.80,
            'oxunitquantity'  => 40,
            'oxunitname'      => 'm',
        ],
        1 => [
            'oxid'            => '_testId_1_childId_1',
            'oxparentid'      => '_testId_1',
            'oxunitquantity'  => '20',
        ],
    ],
    'expected' => [
        '_testId_1' => [
            'base_price'      => '50,80',
            'price'           => '50,80',
            'unit_price'      => '1,27',
        ],
        '_testId_1_childId_1' => [
            'base_price'      => '50,80',
            'price'           => '50,80',
            'unit_price'      => '2,54',
        ],
    ],
    'options' => [
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice'  => false,
        ],
    ],
];
