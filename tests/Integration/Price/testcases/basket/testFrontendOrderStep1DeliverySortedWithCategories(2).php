<?php

/**
 * Price enter mode: Brutto;
 * Price view mode: Brutto;
 * Product count: 5;
 * VAT info:  count of used vat =3(20%, 10%);
 * Currency rate:1;
 * Costs VAT caclulation rule: biggest_net;
 * Costs:
 *  1. Delivery;
 * Brutto-Brutto mode.
 * Short description:
 * Given 2 products, 2 categories and 2 delivery costs.
 * When in basket are added 2 items and cost rules are active for these items, also cost rules are sorted desc.
 * Then prices are calculated with shipping cost.
 */
$aData = [
    //'skipped' => 1,

    'categories' => [
        0 =>  [
            'oxid'       => 'testCategory1',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
            'oxarticles' => [ '_test_10012' ],
        ],
        1 =>  [
            'oxid'       => 'testCategory2',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
            'oxarticles' => [ '_test_1002' ],
        ],
    ],

    'articles' => [
        0 => [
            'oxid'                     => '_test_1002',
            'oxprice'                  => 20,
            'oxvat'                    => 20,
            'amount'                   => 6,
        ],
        1 => [
            'oxid'                     => '_test_10012',
            'oxprice'                  => 10,
            'oxvat'                    => 10,
            'amount'                   => 1,
        ],
    ],

    'costs' => [
        'delivery' => [
            0 => [
                'oxactive' => 1,
                'oxaddsum' => 0,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxfinalize' => 1,
                'oxparam' => 5, //from
                'oxparamend' => 99999, //to
                'oxsort' => 2,
                'oxcategories' => [
                    'testCategory2', //uses article '_test_1002'
                ],
            ],
            1 => [
                'oxactive' => 1,
                'oxaddsum' => 2,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxfinalize' => 1,
                'oxparam' => 0, //from
                'oxparamend' => 99999, //to
                'oxsort' => 1,
                'oxcategories' => [
                    'testCategory1', //uses article '_test_10012'
                ],
            ],
        ],
    ],
    'expected' => [
        'articles' => [
            '_test_10012' => [ '10,00', '10,00' ],
            '_test_1002' => [ '20,00', '120,00' ],
        ],
        'totals' => [
            'totalBrutto' => '130,00',
            'totalNetto'  => '109,09',
            'vats' => [
                10 => '0,91',
                20 => '20,00',
            ],
            'delivery' => [
                'brutto' => '2,00',
            ],
            'grandTotal'  => '132,00',
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
