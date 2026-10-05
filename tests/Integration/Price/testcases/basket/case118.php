<?php

/**
 * Price enter mode: bruto
 * Price view mode:  brutto
 * Product count: count of used products
 * VAT info: 25%
 * Currency rate: 0.50
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
            'oxvat'                    => 25,
            'amount'                   => 100,
        ],
    ],
    'expected' => [
        'articles' => [
             111 => [ '12,48', '1.248,00' ],
        ],
        'totals' => [
            'totalBrutto' => '1.248,00',
            'totalNetto'  => '998,40',
            'vats' => [
                25 => '249,60',
            ],
            'grandTotal'  => '1.248,00',
        ],
    ],
    'options' => [
        'config' => [
                'blEnterNetPrice' => false,
                'blShowNetPrice' => false,
                'blShowVATForWrapping' => true,
                'blShowVATForPayCharge' => true,
                'blShowVATForDelivery' => true,
                'sAdditionalServVATCalcMethod' => 'biggest_net',
        ],
        'activeCurrencyRate' => 0.50,
    ],
];
