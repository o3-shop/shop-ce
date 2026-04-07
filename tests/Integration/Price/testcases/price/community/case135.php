<?php

/**
 * Price enter mode: brutto
 * Price view mode: netto
 * Product count: 1
 * VAT info: 20%
 * Currency rate: 1.0
 * Discounts: count
 *  1. shop; %; 10; group
 *  2. shop; %; 5; group
 *  3. shop; %; 5.5; general
 * Short description: brutto-netto general discount to user groups, prices ABC and separate discounts;
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'            => 1000,
                        'oxprice'         => 100.55,
                        'oxpricea'        => 100,
                        'oxpriceb'        => 10,
                ],
        ],
        'user' => [
                'oxid' => '_testUserB',
                'oxactive' => 1,
                'oxusername' => 'groupBUser',
        ],
        'discounts' => [
                0 => [
                        'oxid'             => 'percentForShop',
                        'oxaddsum'         => 5.5,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxsort'           => 10,
                ],
                1 => [
                        'oxid'             => 'groupADiscount',
                        'oxaddsum'         => 10,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxgroups'         => [ 'oxidpricea' ],
                        'oxsort'           => 20,
                ],
                2 => [
                        'oxid'             => 'groupBDiscount',
                        'oxaddsum'         => 5,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxgroups'         => [ 'oxidpriceb' ],
                        'oxsort'           => 30,
                ],
        ],
        'group' => [
                0 => [
                        'oxid' => 'oxidpricea',
                        'oxactive' => 1,
                        'oxtitle' => 'Price A',
                        'oxobject2group' => [ '_testUserA', 'groupADiscount' ],
                ],
                1 => [
                        'oxid' => 'oxidpriceb',
                        'oxactive' => 1,
                        'oxtitle' => 'Price B',
                        'oxobject2group' => [ '_testUserB', 'groupBDiscount' ],
                ],
        ],
        'expected' => [
                1000 => [
                        'base_price'        => '10,00',
                        'price'             => '7,48',
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => true,
                        'dDefaultVAT' => 20,
                ],
                'activeCurrencyRate' => 1,
        ],
];
