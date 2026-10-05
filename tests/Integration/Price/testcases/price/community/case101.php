<?php

/* RRP = 10
 * Price enter mode: netto
 * Price view mode: brutto
 * Product count: 6
 * VAT info: 15
 * Discount number: 6
 *  1. shop; %
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'            => '1000',
                        'oxprice'         => 200,
                        'oxtprice'        => 10,
                ],
                1 => [
                        'oxid'            => '1001',
                        'oxprice'         => 200,
                        'oxtprice'        => 10,
                ],
                2 => [
                        'oxid'            => '1002',
                        'oxprice'         => 0.05,
                        'oxtprice'        => 10,
                ],
                3 => [
                        'oxid'            => '1003',
                        'oxprice'         => 10,
                        'oxtprice'        => 10,
                ],
                4 => [
                        'oxid'            => '1004',
                        'oxprice'         => 200,
                        'oxtprice'        => 10,
                ],
                5 => [
                        'oxid'            => '1005',
                        'oxprice'         => 200,
                        'oxtprice'        => 10,
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
                        'oxaddsum'         => 20,
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
                        'oxaddsum'     => 10,
                        'oxaddsumtype' => '%',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => [ 1003 ],
                        'oxsort'       => 40,
                ],
                4 => [
                        'oxid'             => 'percentFor1004',
                        'oxaddsum'         => -5.2,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxarticles'       => [ 1004 ],
                        'oxsort'           => 50,
                ],
                5 => [
                        'oxid'         => 'percentFor1005',
                        'oxaddsum'     => 5.5,
                        'oxaddsumtype' => '%',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => [ 1005 ],
                        'oxsort'       => 60,
                ],
        ],
        'expected' => [
                1000 => [
                        'base_price'        => '200,00',
                        'price'             => '184,00',
                        'rrp_price'         => '',
                        'show_rrp'          => false,
                ],
                1001 => [
                        'base_price'        => '200,00',
                        'price'             => '253,00',
                        'rrp_price'         => '',
                        'show_rrp'          => false,
                ],
                1002 => [
                        'base_price'        => '0,05',
                        'price'             => '0,05',
                        'rrp_price'         => '11,50',
                        'show_rrp'          => true,
                ],
                1003 => [
                        'base_price'        => '10,00',
                        'price'             => '10,35',
                        'rrp_price'         => '11,50',
                        'show_rrp'          => true,
                ],
                1004 => [
                        'base_price'        => '200,00',
                        'price'             => '241,96',
                        'rrp_price'         => '',
                        'show_rrp'          => false,
                ],
                1005 => [
                        'base_price'        => '200,00',
                        'price'             => '217,35',
                        'rrp_price'         => '',
                        'show_rrp'          => false,
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => true,
                        'blShowNetPrice' => false,
                        'dDefaultVAT' => 15,
                ],
                'activeCurrencyRate' => 1,
        ],
];
