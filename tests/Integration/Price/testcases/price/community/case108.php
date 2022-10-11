<?php
/* RRP = 1000
 * Price enter mode: netto
 * Price view mode: brutto
 * Product count: 4
 * VAT info: 15
 * Discount number: 4
 *  1. shop; %
 */
$aData = array(
        'articles' => array(
                0 => array(
                        'oxid'            => '1000',
                        'oxprice'         => 150,
                        'oxtprice'        => 1000
                ),
                1 => array(
                        'oxid'            => '1001',
                        'oxprice'         => 150,
                        'oxtprice'        => 1000
                ),
                2 => array(
                        'oxid'            => '1002',
                        'oxprice'         => 150,
                        'oxtprice'        => 1000
                ),
                3 => array(
                        'oxid'            => '1003',
                        'oxprice'         => 150,
                        'oxtprice'        => 1000
                ),
        ),
        'discounts' => array(
                0 => array(
                        'oxid'             => 'percentFor1000',
                        'oxaddsum'         => 20,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxarticles'       => array( 1000 ),
                        'oxsort'           => 10,
                ),
                1 => array(
                        'oxid'         => 'percentFor1001',
                        'oxaddsum'     => -10,
                        'oxaddsumtype' => '%',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => array( 1001 ),
                        'oxsort'       => 20,
                ),
                2 => array(
                        'oxid'             => 'percentFor1002',
                        'oxaddsum'         => -5.2,
                        'oxaddsumtype'     => '%',
                        'oxprice'          => 0,
                        'oxpriceto'        => 99999,
                        'oxamount'         => 0,
                        'oxamountto'       => 99999,
                        'oxactive'         => 1,
                        'oxarticles'       => array( 1002 ),
                        'oxsort'           => 30,
                ),
                3 => array(
                        'oxid'         => 'percentFor1003',
                        'oxaddsum'     => 5.5,
                        'oxaddsumtype' => '%',
                        'oxprice'    => 0,
                        'oxpriceto' => 99999,
                        'oxamount' => 0,
                        'oxamountto' => 99999,
                        'oxactive' => 1,
                        'oxarticles' => array( 1003 ),
                        'oxsort'       => 40,
                ),
        ),
        'expected' => array(
                1000 => array(
                        'base_price'        => '150,00',
                        'price'             => '138,00',
                        'rrp_price'         => '1.150,00',
                        'show_rrp'          => true
                ),
                1001 => array(
                        'base_price'        => '150,00',
                        'price'             => '189,75',
                        'rrp_price'         => '1.150,00',
                        'show_rrp'          => true
                ),
                1002 => array(
                        'base_price'        => '150,00',
                        'price'             => '181,47',
                        'rrp_price'         => '1.150,00',
                        'show_rrp'          => true
                ),
                1003 => array(
                        'base_price'        => '150,00',
                        'price'             => '163,01',
                        'rrp_price'         => '1.150,00',
                        'show_rrp'          => true
                ),
        ),
        'options' => array(
                'config' => array(
                        'blEnterNetPrice' => true,
                        'blShowNetPrice' => false,
                        'dDefaultVAT' => 15,
                ),
                'activeCurrencyRate' => 1,
        ),
);
