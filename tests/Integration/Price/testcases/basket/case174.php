<?php

/**
 * Price enter mode: netto / brutto
 * Price view mode: netto / brutto
 * Product count: count of used products
 * VAT info: 19%
 * Currency rate: 1.0
 * Discounts: count
 *  1. discaunt for product 50%;
 * Short description: bug entry / support case other info;
 * shop has discount assigned to product 1126 with amount restrictions ( 3 < discount < 999 ), test case is moved from unit test
 *
 */
$aData = [
    // Articles
    'articles' => [
        0 => [
                    'oxid'                     => 1126,
                    'oxprice'                  => 34.00,
                    'oxvat'                    => 19,
                    'amount'                   => 2,
        ],
    ],

    // Discounts
    'discounts' => [
        0 => [
            'oxid'         => 'testdisc',
            'oxaddsum'     => 50,
            'oxaddsumtype' => '%',
            'oxamount' => 3,
            'oxamountto' => 99999,
            'oxactive' => 0,
            'oxarticles' => [ 1126 ],
            'oxsort' => 10,
        ],
        1 => [
            'oxid'         => '_testoxdiscount2',
            'oxaddsum'     => 50,
            'oxaddsumtype' => '%',
            'oxamount' => 3,
            'oxamountto' => 99999,
            'oxprice' => 69,
            'oxpriceto' => 999999,
            'oxactive' => 1,
            'oxarticles' => [ 1126 ],
            'oxsort' => 20,
        ],
    ],

    // TEST EXPECTATIONS
    'expected' => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1126 => [ '34,00', '68,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '68,00',
            // Total NETTO
            'totalNetto'  => '57,14',
            // Total VAT amount: vat% => total cost
            'vats' => [
                19 => '10,86',
            ],

            // GRAND TOTAL
            'grandTotal'  => '68,00',
        ],
    ],
    // Test case options
    'options' => [
        // Configs (real named)
        'config' => [
            'blEnterNetPrice' => false,
            'blShowNetPrice' => false,
            'bl_perfLoadSelectLists' => true,
        ],
        // Other options
        'activeCurrencyRate' => 1,
    ],
];
