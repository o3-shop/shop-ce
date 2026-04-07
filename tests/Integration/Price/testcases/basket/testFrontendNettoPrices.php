<?php

/*
/**
 * Price enter mode: netto;
 * Price view mode: brutto;
 * Product count: 1 ;
 * VAT info: 5%;
 * Currency rate: 1.0;
 * Vouchers: -;
 * Wrapping: +;
 * Gift cart: +;
 * Discounts: -;
 * Short description: test added from selenium test (testFrontendNettoPrices) ;Checking when prices are entered in NETTO
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
            'amount'                   => 3,
        ],
    ],
    // Additional costs
    'costs' => [
        // oxwrapping db fields
        'wrapping' => [
            // Wrapping
            0 => [
                'oxtype' => 'WRAP',
                'oxname' => 'Test wrapping [EN] ðÄßü?',
                'oxprice' => 0.9,
                'oxactive' => 1,
                // If for article, specify here
                'oxarticles' => [ 1000 ],
            ],
            // Giftcard
            1 => [
                'oxtype' => 'CARD',
                'oxname' => 'Test card [EN] ðÄßü',
                'oxprice' => 0.20,
                'oxactive' => 1,
            ],
        ],
    ],
    // TEST EXPECTATIONS
    'expected' => [
        // Article expected prices: ARTICLE ID => ( Unit price, Total Price )
        'articles' => [
            1000 => [ '52,50', '157,50' ],
        ],
        // Expectations of other totals
        'totals' => [
            // Total BRUTTO
            'totalBrutto' => '157,50',
            // Total NETTO
            'totalNetto'  => '150,00',
            // Total VAT amount: vat% => total cost
            'vats' => [
                5 => '7,50',
            ],
            // Total wrapping amounts
            'wrapping' => [
                'brutto' => '2,84',
            ],
            // Total giftcard amounts
            'giftcard' => [
                'brutto' => '0,21',
            ],
            // GRAND TOTAL
            'grandTotal'  => '160,55',
        ],
    ],
       'options' => [
            'config' => [
                'blShowNetPrice' => false,
                'blEnterNetPrice' => true,
                'blWrappingVatOnTop' =>true,
                'blDeliveryVatOnTop' => true,
            ],
                'activeCurrencyRate' => 1,
        ],
];
