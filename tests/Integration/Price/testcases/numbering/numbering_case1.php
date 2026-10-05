<?php

// Netto - Netto start case, after order saving, switching to Netto - Brutto, updating
$aData = [
     'articles' => [
         0 => [
             'oxid'       => '111',
             'oxtitle'    => '111',
             'oxprice'    => 1,
             'oxvat'      => 19,
             'oxstock'    => 999,
             'amount'     => 1,
         ],
         1 => [
             'oxid'       => '111',
             'oxtitle'    => '111',
             'oxprice'    => 1,
             'oxvat'      => 19,
             'oxstock'    => 999,
             'amount'     => 1,
         ],
     ],
    'discounts' => [
        0 => [
            'oxid'         => 'discount10for111',
            'oxaddsum'     => 10,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
        ],
        1 => [
            'oxid'         => 'discount10for111',
            'oxaddsum'     => 10,
            'oxaddsumtype' => '%',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxactive' => 1,
        ],
    ],
    'costs' => [
        'delivery' => [
                0 => [
                    'oxactive' => 1,
                    'oxaddsum' => 4.64,
                    'oxaddsumtype' => 'abs',
                    'oxdeltype' => 'p',
                    'oxfinalize' => 1,
                    'oxparamend' => 99999,
                ],
                1 => [
                    'oxactive' => 1,
                    'oxaddsum' => 4.64,
                    'oxaddsumtype' => 'abs',
                    'oxdeltype' => 'p',
                    'oxfinalize' => 1,
                    'oxparamend' => 99999,
                ],
        ],
        'payment' => [
                0 => [
                    'oxaddsum' => 59.50,
                    'oxaddsumtype' => 'abs',
                    'oxfromamount' => 0,
                    'oxtoamount' => 1000000,
                    'oxchecked' => 1,
                ],
                1 => [
                    'oxaddsum' => 59.50,
                    'oxaddsumtype' => 'abs',
                    'oxfromamount' => 0,
                    'oxtoamount' => 1000000,
                    'oxchecked' => 1,
                ],
        ],
    ],
    'options' => [
            'separateNumbering' => false,
    ],
];
