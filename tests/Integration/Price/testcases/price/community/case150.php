<?php

/*
 * Price enter mode: brutto
 * Price view mode: brutto
 * Price type: range
 * Articles: 1 with price 13.00
 * Variants: 0-2
 * Parent buyable: yes
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'                     => '_testId_1',
                        'oxprice'                  => 13,
                ],

                1 => [
                        'oxid'                     => '_testId_2',
                        'oxprice'                  => 13,
                ],
                2 => [
                        'oxid'                     => '_testId_2_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_2',
                ],

                3 => [
                        'oxid'                     => '_testId_3',
                        'oxprice'                  => 13,
                ],
                4 => [
                        'oxid'                     => '_testId_3_child_1',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_3',
                ],

                5 => [
                        'oxid'                     => '_testId_4',
                        'oxprice'                  => 13,
                ],
                6 => [
                        'oxid'                     => '_testId_4_child_1',
                        'oxprice'                  => 13,
                        'oxparentid'               => '_testId_4',
                ],

                7 => [
                        'oxid'                     => '_testId_5',
                        'oxprice'                  => 13,
                ],
                8 => [
                        'oxid'                     => '_testId_5_child_1',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_5',
                ],

                9 => [
                        'oxid'                     => '_testId_6',
                        'oxprice'                  => 13,
                ],
                10 => [
                        'oxid'                     => '_testId_6_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_6',
                ],
                11 => [
                        'oxid'                     => '_testId_6_child_2',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_6',
                ],

                12 => [
                        'oxid'                     => '_testId_7',
                        'oxprice'                  => 13,
                ],
                13 => [
                        'oxid'                     => '_testId_7_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_7',
                ],
                14 => [
                        'oxid'                     => '_testId_7_child_2',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_7',
                ],

                15 => [
                        'oxid'                     => '_testId_8',
                        'oxprice'                  => 13,
                ],
                16 => [
                        'oxid'                     => '_testId_8_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_8',
                ],
                17 => [
                        'oxid'                     => '_testId_8_child_2',
                        'oxprice'                  => 13,
                        'oxparentid'               => '_testId_8',
                ],

                18 => [
                        'oxid'                     => '_testId_9',
                        'oxprice'                  => 13,
                ],
                19 => [
                        'oxid'                     => '_testId_9_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_9',
                ],
                20 => [
                        'oxid'                     => '_testId_9_child_2',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_9',
                ],

                21 => [
                        'oxid'                     => '_testId_10',
                        'oxprice'                  => 13,
                ],
                22 => [
                        'oxid'                     => '_testId_10_child_1',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_10',
                ],
                23 => [
                        'oxid'                     => '_testId_10_child_2',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_10',
                ],

                24 => [
                        'oxid'                     => '_testId_11',
                        'oxprice'                  => 13,
                ],
                25 => [
                        'oxid'                     => '_testId_11_child_1',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_11',
                ],
                26 => [
                        'oxid'                     => '_testId_11_child_2',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_11',
                ],

                27 => [
                        'oxid'                     => '_testId_12',
                        'oxprice'                  => 13,
                ],
                28 => [
                        'oxid'                     => '_testId_12_child_1',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_12',
                ],
                29 => [
                        'oxid'                     => '_testId_12_child_2',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_12',
                ],
        ],

        'expected' => [
                '_testId_1' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '13,00',
                        'is_range_price' => false,
                ],

                '_testId_2' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '13,00',
                        'is_range_price' => false,
                ],

                '_testId_3' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '6,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => false,
                ],

                '_testId_4' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '13,00',
                        'is_range_price' => false,
                ],

                '_testId_5' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '27,00',
                        'is_range_price' => false,
                ],

                '_testId_6' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '13,00',
                        'is_range_price' => false,
                ],

                '_testId_7' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '6,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => true,
                ],

                '_testId_8' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '13,00',
                        'is_range_price' => false,
                ],

                '_testId_9' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '13,00',
                        'is_range_price' => true,
                ],

                '_testId_10' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '6,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => true,
                ],

                '_testId_11' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '6,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => true,
                ],

                '_testId_12' => [
                        'base_price' => '13,00',
                        'price' => '13,00',
                        'min_price' => '13,00',
                        'var_min_price' => '27,00',
                        'is_range_price' => false,
                ],
        ],
        'options' => [
                'config' => [
                        'blEnterNetPrice' => false,
                        'blShowNetPrice' => false,
                        'blVariantParentBuyable' => 0,
                ],
        ],
];
