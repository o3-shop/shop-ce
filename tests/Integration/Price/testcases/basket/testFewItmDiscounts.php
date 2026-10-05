<?php

/*
/**
 * Price enter mode: brutto
 * Price view mode: brutto
 * Product count: 3
 * VAT info: 5%
 * Currency rate: 1.0
 * Vouchers: -;
 * Wrapping: -;
 * Gift cart: -;
 * Discounts: 2 item discount
 * Short description: test added from selenium test (testFewItmDiscounts);Is testing few Itm discount for products
 */
$aData = [
    // Articles
    'articles' => [
        0 => [
            // oxarticles db fields
            'oxid'                     => 1000,
            'oxprice'                  => 50.00,
            'oxvat'                    => 5,
            // Amount in basket
            'amount'                   => 1,
        ],
        1 => [
            // oxarticles db fields
            'oxid'                     => 1003,
            'oxprice'                  => 50.00,
            'oxvat'                    => 5,
            // Amount in basket
          //  'amount'                   => 1,
        ],
        2 => [
            // oxarticles db fields
            'oxid'                     => 1002,
            'oxprice'                  => 50.00,
            'oxvat'                    => 5,
            // Amount in basket
           // 'amount'                   => 1,
        ],
    ],
    // Discounts
    'discounts' => [
        // oxdiscount DB fields
        0 => [
            'oxid'         => 'testitmdiscount',
            'oxshopid' => 1,
            'oxaddsum'     => 0,
            'oxaddsumtype' => 'itm',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxprice' => 0,
            'oxpriceto' => 0,
            'oxactive' => 1,
            'oxitmartid' => 1003,
            'oxitmamount' => 1,
            'oxitmmultiple' => 0,
            'oxarticles' => [ 1000 ],
            'oxsort' => 10,
        ],
                // oxdiscount DB fields
        1 => [
            'oxid'         => 'testitmdiscounts',
            'oxshopid' => 1,
            'oxaddsum'     => 0,
            'oxaddsumtype' => 'itm',
            'oxamount' => 1,
            'oxamountto' => 99999,
            'oxprice' => 0,
            'oxpriceto' => 0,
            'oxactive' => 1,
            'oxitmartid' => 1002,
            'oxitmamount' => 1,
            'oxitmmultiple' => 0,
            'oxarticles' => [ 1000 ],
            'oxsort' => 20,
        ],
    ],
    // TEST EXPECTATIONS
    'expected' => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
             1000 => [ '50,00', '50,00' ],
             1003 => [ '0,00', '0,00' ],
             1002=> [ '0,00', '0,00' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '50,00',
            // Total NETTO
            'totalNetto'  => '47,62',
            // Total VAT amount: vat% => total cost
            'vats' => [
                5 => '2,38',
            ],
            // Total discount amounts: discount id => total cost
         //   'discounts' => array (
                // Expectation for special discount with specified ID
         //       'testdiscountfrom200' => '25,00',
       //     ),

            // GRAND TOTAL
            'grandTotal'  => '50,00',
        ],
    ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                ],
                'activeCurrencyRate' => 1,
        ],
];
