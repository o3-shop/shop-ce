<?php

/* RRP = 94.52
 * Price enter mode: netto
 * Price view mode: netto
 * Product count: 4
 * VAT info: 15
 * Discount number: 4
 *  1. shop; %
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'            => '1000',
                        'oxprice'         => 100,
                        'oxtprice'        => 94.52,
                ],
                1 => [
                        'oxid'            => '1001',
                        'oxprice'         => 100,
                        'oxtprice'        => 94.52,
                ],
                2 => [
                        'oxid'            => '1002',
                        'oxprice'         => 100,
                        'oxtprice'        => 94.52,
                ],
                3 => [
                        'oxid'            => '1003',
                        'oxprice'         => 100,
                        'oxtprice'        => 94.52,
                ],
        ],
        'discounts' => [
                0 => [
                        'oxid'             => 'percentFor1000',
                        'oxaddsum'         => 20,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxarticles'       => [ 1000 ],
                        'oxsort'           => 10,
                ],
                1 => [
                        'oxid'         => 'percentFor1001',
                        'oxaddsum'     => -10,
                        'oxaddsumtype' => '%',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => [ 1001 ],
                        'oxsort'       => 20,
                ],
                2 => [
                        'oxid'             => 'percentFor1002',
                        'oxaddsum'         => -5.2,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxarticles'       => [ 1002 ],
                        'oxsort'           => 30,
                ],
                3 => [
                        'oxid'         => 'percentFor1003',
                        'oxaddsum'     => 5.5,
                        'oxaddsumtype' => '%',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => [ 1003 ],
                        'oxsort'       => 40,
                ],
        ],
        'expected' => [
                1000 => [
                        'base_price'        => '100,00',
                        'price'             => '80,00',
                        'rrp_price'         => '94,52',
                        'show_rrp'          => true,
                ],
                1001 => [
                        'base_price'        => '100,00',
                        'price'             => '110,00',
                        'rrp_price'         => '',
                        'show_rrp'          => false,
                ],
                1002 => [
                        'base_price'        => '100,00',
                        'price'             => '105,20',
                        'rrp_price'         => '',
                        'show_rrp'          => false,
                ],
                1003 => [
                        'base_price'        => '100,00',
                        'price'             => '94,50',
                        'rrp_price'         => '94,52',
                        'show_rrp'          => true,
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => true,
                        'blShowNetPrice' => true,
                        'dDefaultVAT' => 15,
                ],
                'activeCurrencyRate' => 1,
        ],
];
