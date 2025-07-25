<?php

/*
 * Price enter mode: brutto
 * Price view mode: brutto
 * Discounts: 0
 * Price type: range
 * User group: oxpriceb
 * config: 0 not override
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'                     => '_testId_1',
                        'oxprice'                  => 100,
                        'oxpriceb'                 => 10,
                ],
                1 => [
                        'oxid'                     => '_testId_1_child_1',
                        'oxprice'                  => 100,
                        'oxparentid'               => '_testId_1',
                ],
                2 => [
                        'oxid'                     => '_testId_1_child_2',
                        'oxprice'                  => 100,
                        'oxpriceb'                 => 20,
                        'oxparentid'               => '_testId_1',
                ],
                3 => [
                        'oxid'                     => '_testId_2',
                        'oxprice'                  => 100,
                        'oxpricea'                 => 20,
                ],
                4 => [
                        'oxid'                     => '_testId_2_child_1',
                        'oxprice'                  => 120,
                        'oxparentid'               => '_testId_2',
                        'oxpricea'                 => 20,
                ],
                5 => [
                        'oxid'                     => '_testId_2_child_2',
                        'oxprice'                  => 150,
                        'oxparentid'               => '_testId_2',
                ],
                6 => [
                        'oxid'                     => '_testId_3',
                        'oxprice'                  => 100,
                ],
                7 => [
                        'oxid'                     => '_testId_3_child_1',
                        'oxprice'                  => 20,
                        'oxparentid'               => '_testId_3',
                        'oxpriceb'                 => 20,
                ],
                8 => [
                        'oxid'                     => '_testId_3_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_3',
                        'oxpriceb'                 => 20,
                ],
                9 => [
                        'oxid'                     => '_testId_4',
                        'oxprice'                  => 100,
                        'oxpriceb'                 => 10,
                ],
                10 => [
                        'oxid'                     => '_testId_4_child_1',
                        'oxprice'                  => 100,
                        'oxparentid'               => '_testId_4',
                        'oxpriceb'                 => 110,
                ],
                11 => [
                        'oxid'                     => '_testId_4_child_2',
                        'oxprice'                  => 150,
                        'oxparentid'               => '_testId_4',
                        'oxpriceb'                 => 10,
                ],
                12 => [
                        'oxid'                     => '_testId_5',
                        'oxprice'                  => 100,
                ],
                13 => [
                        'oxid'                     => '_testId_5_child_1',
                        'oxprice'                  => 100,
                        'oxparentid'               => '_testId_5',
                ],
                14 => [
                        'oxid'                     => '_testId_5_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_5',
                ],
                15 => [
                        'oxid'                     => '_testId_6',
                        'oxprice'                  => 100,
                        'oxpriceb'                 => 1,
                ],
                16 => [
                        'oxid'                     => '_testId_6_child_1',
                        'oxprice'                  => 100,
                        'oxparentid'               => '_testId_6',
                        'oxpriceb'                 => 1,
                ],
                17 => [
                        'oxid'                     => '_testId_6_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_6',
                        'oxpriceb'                 => 1,
                ],
        ],
        'user' => [
                'oxid' => '_testUser',
                'oxactive' => 1,
                'oxusername' => 'bGroupUser',
        ],
        'group' => [
                0 => [
                        'oxid' => 'oxidpriceb',
                        'oxactive' => 1,
                        'oxtitle' => 'Price B',
                        'oxobject2group' => [ '_testUser' ],
                ],
        ],
       'expected' => [
               '_testId_1' => [
                        'base_price' => '10,00',
                        'price' => '10,00',
                        'min_price' => '0,00',
                        'var_min_price' => '0,00',
                        'is_range_price' => true,
               ],

               '_testId_2' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '0,00',
                        'is_range_price' => false,
                ],

                '_testId_3' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '20,00',
                        'is_range_price' => false,
                ],

                '_testId_4' => [
                        'base_price' => '10,00',
                        'price' => '10,00',
                        'min_price' => '10,00',
                        'var_min_price' => '10,00',
                        'is_range_price' => true,
                ],

                '_testId_5' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '0,00',
                        'is_range_price' => false,
                ],

                '_testId_6' => [
                        'base_price' => '1,00',
                        'price' => '1,00',
                        'min_price' => '1,00',
                        'var_min_price' => '1,00',
                        'is_range_price' => false,
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                        'blOverrideZeroABCPrices' => false,
                        'blVariantParentBuyable' => 0,
                ],
                'activeCurrencyRate' => 1,
        ],
];
