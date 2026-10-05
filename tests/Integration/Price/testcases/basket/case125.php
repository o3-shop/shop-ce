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
            'amount'                   => 100,
        ],
    ],
    'expected' => [
        'articles' => [
             111 => [ '10,49', '1.049,00' ],
        ],
        'totals' => [
            'totalBrutto' => '1.248,31',
            'totalNetto'  => '1.049,00',
            'vats' => [
                19 => '199,31',
            ],
            'grandTotal'  => '1.248,31',
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
