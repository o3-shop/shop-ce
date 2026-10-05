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
 * only stuff added
 */
$aData = [
    'categories' => [
        0 =>  [
            'oxid'       => 'books',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
        ],
        1 =>  [
            'oxid'       => 'otherStuff',
            'oxparentid' => 'oxrootid',
            'oxshopid'   => 1,
            'oxactive'   => 1,
            'oxarticles' => [ 'stuff' ],
        ],
    ],

    'articles' => [
        0 => [
            'oxid'                     => 'stuff',
            'oxprice'                  => 20,
            'oxvat'                    => 20,
            'amount'                   => 6,
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
            'stuff' => [ '20,00', '120,00' ],
        ],
        'totals' => [
            'totalBrutto' => '120,00',
            'totalNetto'  => '100,00',
            'vats' => [
                20 => '20,00',
            ],
            'delivery' => [
                'brutto' => '5,00',
            ],
            'grandTotal'  => '125,00',
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
