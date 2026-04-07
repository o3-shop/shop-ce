<?php

/*
 * Price enter mode: brutto
 * Price view mode: brutto
 * Price type: range
 * Articles: 1 with price 0.00
 * Variants: 0-2
 * Parent buyable: no
 */
$aData = [
        'articles' => [
                0 => [
                        'oxid'                     => '_testId_1',
                        'oxprice'                  => 0,
                ],

                1 => [
                        'oxid'                     => '_testId_2',
                        'oxprice'                  => 0,
                ],
                2 => [
                        'oxid'                     => '_testId_2_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_2',
                ],

                3 => [
                        'oxid'                     => '_testId_3',
                        'oxprice'                  => 0,
                ],
                4 => [
                        'oxid'                     => '_testId_3_child_1',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_3',
                ],

                5 => [
                        'oxid'                     => '_testId_4',
                        'oxprice'                  => 0,
                ],
                6 => [
                        'oxid'                     => '_testId_4_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_4',
                ],
                7 => [
                        'oxid'                     => '_testId_4_child_2',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_4',
                ],

                8 => [
                        'oxid'                     => '_testId_5',
                        'oxprice'                  => 0,
                ],
                9 => [
                        'oxid'                     => '_testId_5_child_1',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_5',
                ],
                10 => [
                        'oxid'                     => '_testId_5_child_2',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_5',
                ],

                11 => [
                        'oxid'                     => '_testId_6',
                        'oxprice'                  => 0,
                ],
                12 => [
                        'oxid'                     => '_testId_6_child_1',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_6',
                ],
                13 => [
                        'oxid'                     => '_testId_6_child_2',
                        'oxprice'                  => 0,
                        'oxparentid'               => '_testId_6',
                ],

                14 => [
                        'oxid'                     => '_testId_7',
                        'oxprice'                  => 0,
                ],
                15 => [
                        'oxid'                     => '_testId_7_child_1',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_7',
                ],
                16 => [
                        'oxid'                     => '_testId_7_child_2',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_7',
                ],

                17 => [
                        'oxid'                     => '_testId_8',
                        'oxprice'                  => 0,
                ],
                18 => [
                        'oxid'                     => '_testId_8_child_1',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_8',
                ],
                19 => [
                        'oxid'                     => '_testId_8_child_2',
                        'oxprice'                  => 6,
                        'oxparentid'               => '_testId_8',
                ],

                20 => [
                        'oxid'                     => '_testId_9',
                        'oxprice'                  => 0,
                ],
                21 => [
                        'oxid'                     => '_testId_9_child_1',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_9',
                ],
                22 => [
                        'oxid'                     => '_testId_9_child_2',
                        'oxprice'                  => 27,
                        'oxparentid'               => '_testId_9',
                ],
        ],

        'expected' => [
                '_testId_1' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '0,00',
                        'is_range_price' => false,
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
                        'var_min_price' => '6,00',
                        'is_range_price' => false,
                ],

                '_testId_4' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '0,00',
                        'is_range_price' => false,
                ],

                '_testId_5' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => false,
                ],

                '_testId_6' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => false,
                ],

                '_testId_7' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => true,
                ],

                '_testId_8' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
                        'var_min_price' => '6,00',
                        'is_range_price' => true,
                ],

                '_testId_9' => [
                        'base_price' => '0,00',
                        'price' => '0,00',
                        'min_price' => '0,00',
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
