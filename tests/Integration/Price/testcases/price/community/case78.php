<?php

/*
 * Price enter mode: brutto
 * Price view mode: brutto
 * Discounts: 0
 * Price type: range
 *
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'                     => '_testId_1',
                        'oxprice'                  => 100,
                ],
                1 => [
                        'oxid'                     => '_testId_1_child_1',
                        'oxprice'                  => 100,
                        'oxparentid'               => '_testId_1',
                ],
                2 => [
                        'oxid'                     => '_testId_1_child_2',
                        'oxprice'                  => 100,
                        'oxparentid'               => '_testId_1',
                ],
                3 => [
                        'oxid'                     => '_testId_2',
                        'oxprice'                  => 100,
                ],
                4 => [
                        'oxid'                     => '_testId_2_child_1',
                        'oxprice'                  => 120,
                        'oxparentid'               => '_testId_2',
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
                ],
                8 => [
                        'oxid'                     => '_testId_3_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_3',
                ],
                9 => [
                        'oxid'                     => '_testId_4',
                        'oxprice'                  => 100,
                ],
                10 => [
                        'oxid'                     => '_testId_4_child_1',
                        'oxprice'                  => 100,
                        'oxparentid'               => '_testId_4',
                ],
                11 => [
                        'oxid'                     => '_testId_4_child_2',
                        'oxprice'                  => 150,
                        'oxparentid'               => '_testId_4',
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
                ],
                16 => [
                        'oxid'                     => '_testId_6_child_1',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_6',
                ],
                17 => [
                        'oxid'                     => '_testId_6_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_6',
                ],

                18 => [
                        'oxid'                     => '_testId_7',
                        'oxprice'                  => 40,
                ],
                19 => [
                        'oxid'                     => '_testId_7_child_1',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_7',
                ],
                20 => [
                        'oxid'                     => '_testId_7_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_7',
                ],
        ],

       'expected' => [
                '_testId_1' => [
                        'base_price' => '100,00',
                        'price' => '100,00',
                        'min_price' => '100,00',
                        'var_min_price' => '100,00',
                        'is_range_price' => false,
                ],

                '_testId_2' => [
                        'base_price' => '100,00',
                        'price' => '100,00',
                        'min_price' => '100,00',
                        'var_min_price' => '120,00',
                        'is_range_price' => true,
                ],

                '_testId_3' => [
                        'base_price' => '100,00',
                        'price' => '100,00',
                        'min_price' => '20,00',
                        'var_min_price' => '20,00',
                        'is_range_price' => true,
                ],

                '_testId_4' => [
                        'base_price' => '100,00',
                        'price' => '100,00',
                        'min_price' => '100,00',
                        'var_min_price' => '100,00',
                        'is_range_price' => true,
                ],

                '_testId_5' => [
                        'base_price' => '100,00',
                        'price' => '100,00',
                        'min_price' => '50,00',
                        'var_min_price' => '50,00',
                        'is_range_price' => true,
                ],

                '_testId_6' => [
                        'base_price' => '100,00',
                        'price' => '100,00',
                        'min_price' => '50,00',
                        'var_min_price' => '50,00',
                        'is_range_price' => true,
                ],

                '_testId_7' => [
                        'base_price' => '40,00',
                        'price' => '40,00',
                        'min_price' => '40,00',
                        'var_min_price' => '50,00',
                        'is_range_price' => true,
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                        'blVariantParentBuyable' => 1,
                ],
                'activeCurrencyRate' => 1,
        ],
];
