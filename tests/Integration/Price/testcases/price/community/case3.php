<?php

/*
 * Price enter mode: brutto
 * Price view mode: brutto
 * Discounts: 2
 *  1. shop; abs
 *  2. shop; %
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'                     => '1001_a',
                        'oxprice'                  => 100.55,
                        'oxvat'                    => 20,
                ],
                1 => [
                        'oxid'                     => '1001_b',
                        'oxprice'                  => 100.55,
                        'oxvat'                    => 20,
                ],
        ],
        'discounts' => [
                0 => [
                        'oxid'         => 'abs',
                        'oxaddsum'     => -5.2,
                        'oxaddsumtype' => 'abs',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => [ '1001_a' ],
                        'oxsort'       => 10,
                ],
                1 => [
                        'oxid'         => 'percent',
                        'oxaddsum'     => -5.2,
                        'oxaddsumtype' => '%',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => [ '1001_b' ],
                        'oxsort'       => 20,
                ],
        ],
        'expected' => [
                '1001_a' => [
                        'base_price' => '100,55',
                        'price' => '105,75',
                ],
                '1001_b' => [
                        'base_price' => '100,55',
                        'price' => '105,78',
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
