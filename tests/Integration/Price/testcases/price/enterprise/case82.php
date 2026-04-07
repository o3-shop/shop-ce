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
                    'oxpricea'                 => 100,
                    'inheritToShops'           => [2],
            ],
            1 => [
                    'oxid'                     => '_testId_1_child_1',
                    'oxpricea'                 => 50,
                    'oxparentid'               => '_testId_1',
                    'field2shop' => [
                            'oxshopid' => 2,
                            'oxpricea' => 100,
                    ],
                    'inheritToShops'           => [2],
            ],
            2 => [
                    'oxid'                     => '_testId_1_child_2',
                    'oxpricea'                 => 150,
                    'oxparentid'               => '_testId_1',
                    'field2shop' => [
                            'oxshopid' => 2,
                            'oxpricea' => 100,
                    ],
                    'inheritToShops'           => [2],
            ],

            3 => [
                    'oxid'                     => '_testId_2',
                    'oxpricea'                 => 50,
                    'inheritToShops'           => [2],
            ],
            4 => [
                    'oxid'                     => '_testId_2_child_1',
                    'oxpricea'                 => 30,
                    'oxparentid'               => '_testId_2',
                    'field2shop' => [
                            'oxshopid' => 2,
                            'oxpricea' => 60,
                    ],
                    'inheritToShops'           => [2],
            ],
            5 => [
                    'oxid'                     => '_testId_2_child_2',
                    'oxpricea'                 => 30,
                    'oxparentid'               => '_testId_2',
                    'field2shop' => [
                            'oxshopid' => 2,
                            'oxpricea' => 70,
                    ],
                    'inheritToShops'           => [2],
            ],

            6 => [
                    'oxid'                     => '_testId_3',
                    'oxpricea'                 => 80,
                    'inheritToShops'           => [2],
            ],
            7 => [
                    'oxid'                     => '_testId_3_child_1',
                    'oxpricea'                 => 30,
                    'oxparentid'               => '_testId_3',
                    'field2shop' => [
                            'oxshopid' => 2,
                            'oxpricea' => 60,
                    ],
                    'inheritToShops'           => [2],
            ],
    ],

    'shop' => [
            0 => [
                    'oxactive'          => 1,
                    'oxid'              => 2,
                    'oxparentid'        => 1,
                    'oxname'            => 'subshop',
                    'oxisinherited'     => 1,
                    'activeshop'        => true,
            ],
    ],

    'user' => [
            'oxid'          => '_testUser',
            'oxactive'      => 1,
            'oxusername'    => 'aGroupUser',
    ],

    'group' => [
            0 => [
                    'oxid'              => 'oxidpricea',
                    'oxactive'          => 1,
                    'oxtitle'           => 'Price A',
                    'oxobject2group'    => [ '_testUser' ],
            ],
    ],

    'expected' => [
            '_testId_1' => [
                    'base_price'        => '100,00',
                    'price'             => '100,00',
                    'min_price'         => '100,00',
                    'var_min_price'     => '100,00',
                    'is_range_price'    => false,
            ],
            '_testId_2' => [
                    'base_price'        => '50,00',
                    'price'             => '50,00',
                    'min_price'         => '50,00',
                    'var_min_price'     => '60,00',
                    'is_range_price'    => true,
            ],
            '_testId_3' => [
                    'base_price'        => '80,00',
                    'price'             => '80,00',
                    'min_price'         => '60,00',
                    'var_min_price'     => '60,00',
                    'is_range_price'    => true,
            ],
    ],

    'options' => [
            'config' => [
                    'blEnterNetPrice' => false,
                    'blShowNetPrice' => false,
                    'blVariantParentBuyable' => 1,
                    'blMallCustomPrice' => 1,
            ],
            'activeCurrencyRate' => 1,
    ],
];
