<?php

/**
 * Price enter mode: bruto
 * Price view mode:  neto
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
            'amount'                   => 100,
        ],
    ],
    'expected' => [
        'articles' => [
             111 => [ '20,97', '2.097,00' ],
        ],
        'totals' => [
            'totalBrutto' => '2.495,43',
            'totalNetto'  => '2.097,00',
            'vats' => [
                19 => '398,43',
            ],
            'grandTotal'  => '2.495,43',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => true,
                'blShowVATForWrapping' => true,
                'blShowVATForPayCharge' => true,
                'blShowVATForDelivery' => true,
                'sAdditionalServVATCalcMethod' => 'proportional',
        ],
        'activeCurrencyRate' => 1.00,
    ],
];
