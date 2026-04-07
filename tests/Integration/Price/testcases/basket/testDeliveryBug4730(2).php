<?php

/**
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 5;
 * VAT info:  count of used vat =3(20%, 10%);
 * Currency rate:1;
 * Costs VAT calculation rule: biggest_net;
 * Costs:
 *  1. Delivery;
 * 0004730: Order rules with Quantity -> Items would be count double
 */
$aData = [
    'categories' => [
        0 =>  [
            'oxid'       => 'vine',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
            'oxarticles' => [ 'vine1' ],
        ],
        1 =>  [
            'oxid'       => 'supplies',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
            'oxarticles' => [ 'supply1' ],
        ],
    ],

    'articles' => [
        0 => [
            'oxid'                     => 'vine1',
            'oxprice'                  => 5,
            'oxvat'                    => 10,
            'amount'                   => 2,
        ],
        1 => [
            'oxid'                     => 'supply1',
            'oxprice'                  => 10,
            'oxvat'                    => 10,
            'amount'                   => 1,
        ],
    ],

    'costs' => [
        'delivery' => [
            0 => [
                'oxtitle' => 'more than 12 Bottles',
                'oxactive' => 1,
                'oxaddsum' => 0,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxfinalize' => 1,
                'oxparam' => 12, //from
                'oxparamend' => 99999, //to
                'oxsort' => 1,
                'oxcategories' => [
                    'vine',
                ],
            ],
            1 => [
                'oxtitle' => '4 - 11 Bottles',
                'oxactive' => 1,
                'oxaddsum' => 5.9,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxfinalize' => 1,
                'oxparam' => 4, //from
                'oxparamend' => 11, //to
                'oxsort' => 1,
                'oxcategories' => [
                    'vine',
                ],
            ],
            2 => [
                'oxtitle' => '1 - 3 Bottles',
                'oxactive' => 1,
                'oxaddsum' => 4.9,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxfinalize' => 1,
                'oxparam' => 1, //from
                'oxparamend' => 3, //to
                'oxsort' => 1,
                'oxcategories' => [
                    'vine',
                ],
            ],
            3 => [
                'oxtitle' => 'supplies',
                'oxactive' => 1,
                'oxaddsum' => 2.9,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxfinalize' => 1,
                'oxparam' => 0, //from
                'oxparamend' => 99999, //to
                'oxsort' => 4,
                'oxcategories' => [
                    'supplies',
                ],
            ],
        ],
    ],
    'expected' => [
        'articles' => [
            'vine1' => [ '5,00', '10,00' ],
            'supply1' => [ '10,00', '10,00' ],
        ],
        'totals' => [
            'totalBrutto' => '20,00',
            'totalNetto'  => '18,18',
            'vats' => [
                10 => '1,82',
            ],
            'delivery' => [
                'brutto' => '4,90',
            ],
            'grandTotal'  => '24,90',
        ],
    ],
    'options' => [
        'activeCurrencyRate' => 1,
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice' => false,
            'blShowVATForWrapping' => false,
            'blShowVATForDelivery' => false,
            'sAdditionalServVATCalcMethod' => 'biggest_net',
        ],
    ],
];
