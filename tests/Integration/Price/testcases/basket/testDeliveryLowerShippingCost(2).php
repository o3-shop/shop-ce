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
 * Brutto-Brutto mode.
 * Short description:
 * http://www.oxid-esales.com/en/support-services/documentation-and-help/archive-oxid-eshop/administer-eshop/set-shipping/lower-shipping-cost.html
 * https://bugs.oxid-esales.com/view.php?id=4123
 *
 * only books added
 */
$aData = [
    'categories' => [
        0 =>  [
            'oxid'       => 'books',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
            'oxarticles' => [ 'book' ],
        ],
        1 =>  [
            'oxid'       => 'otherStuff',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
        ],
    ],

    'articles' => [
        0 => [
            'oxid'                     => 'book',
            'oxprice'                  => 10,
            'oxvat'                    => 10,
            'amount'                   => 1,
        ],
    ],

    'costs' => [
        'delivery' => [
            0 => [
                'oxactive' => 1,
                'oxaddsum' => 2,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxparam' => 0, //from
                'oxparamend' => 99999, //to
                'oxsort' => 1,
            ],
            1 => [
                'oxactive' => 1,
                'oxaddsum' => 3,
                'oxaddsumtype' => 'abs',
                'oxdeltype' => 'a',
                'oxparam' => 0, //from
                'oxparamend' => 99999, //to
                'oxsort' => 2,
                'oxcategories' => [
                    'otherStuff',
                ],
            ],
        ],
    ],
    'expected' => [
        'articles' => [
            'book' => [ '10,00', '10,00' ],
        ],
        'totals' => [
            'totalBrutto' => '10,00',
            'totalNetto'  => '9,09',
            'vats' => [
                10 => '0,91',
            ],
            'delivery' => [
                'brutto' => '2,00',
            ],
            'grandTotal'  => '12,00',
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
