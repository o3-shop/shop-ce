<?php

/*
/**
 * Price enter mode: netto
 * Price view mode:  brutto
 * Product count: 1
 * VAT info: 19% Default VAT for all Products
 * Currency rate: 1.0
 * Discounts: -
 * Vouchers: -
 * Wrapping: -
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery -
 *  3. TS -
 * Short description:
 * user with diferent foreign country, adn vat should not be calculated
 */
$aData = [
    'articles' => [
        0 => [
                'oxid'                     => 9202,
                'oxprice'                  => 100,
                'oxvat'                    => 19,
                'amount'                   => 1,
        ],
    ],
     // User
    'user' => [
            'oxactive' => 1,
            'oxusername' => 'basketUser',
            // country id, for example this is United States, make sure country with specified ID is active
            'oxcountryid' => '8f241f11096877ac0.98748826',
    ],
    'expected' => [
        'articles' => [
                 9202 => [ '100,00', '100,00' ],
        ],
        'totals' => [
                'totalBrutto' => '100,00',
                'totalNetto'  => '100,00',
                'vats' => [
                    0 => '0,00',
                ],
                'grandTotal'  => '100,00',
        ],
    ],

    'options' => [
        'config' => [
            'blEnterNetPrice' => true,
            'blShowNetPrice' => false,
        ],
        'activeCurrencyRate' => 1,
    ],
];
