<?php

/**
 * Price enter mode: bruto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 19%
 * Currency rate: 1.0
 * Discounts: -
 * Vouchers: -
 * Wrapping: -;
 * Gift cart:  -;
 * Costs VAT caclulation rule: max
 * Costs:
 *  1. Payment -
 *  2. Delivery -
 *  3. TS +
 */
$aData = [
    'articles' => [
        0 => [
            'oxid'                     => 111,
            'oxprice'                  => 24.95,
            'oxvat'                    => 19,
            'amount'                   => 150,
        ],
        1 => [
            'oxid'                     => 222,
            'oxprice'                  => 7.99,
            'oxvat'                    => 19,
            'amount'                   => 1,
        ],
    ],
    'expected' => [
        'articles' => [
             111 => [ '10,49', '1.573,50' ],
             222 => [ '3,36', '3,36' ],
        ],
        'totals' => [
            'totalBrutto' => '1.876,46',
            'totalNetto'  => '1.576,86',
            'vats' => [
                19 => '299,60',
            ],
            'grandTotal'  => '1.876,46',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => true,
                'blShowVATForWrapping' => true,
                'blShowVATForPayCharge' => true,
                'blShowVATForDelivery' => true,
                'sAdditionalServVATCalcMethod' => 'biggest_net',
        ],
        'activeCurrencyRate' => 0.50,
    ],
];
