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
                        'inheritToShops'           => [2],
                ],
                1 => [
                        'oxid'                     => '_testId_1_child_1',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_1',
                        'field2shop' => [
                                'oxshopid'  => 2,
                                'oxprice'   => 100,
                        ],
                        'inheritToShops'           => [2],
                ],

                2 => [
                        'oxid'                     => '_testId_2',
                        'oxprice'                  => 40,
                        'inheritToShops'           => [2],
                ],
                3 => [
                        'oxid'                     => '_testId_2_child_1',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_2',
                        'field2shop' => [
                                'oxshopid' => 2,
                                'oxprice' => 60,
                        ],
                        'inheritToShops'           => [2],
                ],

                4 => [
                        'oxid'                     => '_testId_3',
                        'oxprice'                  => 100,
                        'inheritToShops'           => [2],
                ],
                5 => [
                        'oxid'                     => '_testId_3_child_1',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_3',
                        'field2shop' => [
                                'oxshopid' => 2,
                                'oxprice' => 70,
                        ],
                        'inheritToShops'           => [2],
                ],
                6 => [
                        'oxid'                     => '_testId_3_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_3',
                        'field2shop' => [
                                'oxshopid' => 2,
                                'oxprice' => 60,
                        ],
                        'inheritToShops'           => [2],
                ],
                7 => [
                        'oxid'                     => '_testId_4',
                        'oxprice'                  => 60,
                        'inheritToShops'           => [2],
                ],
                8 => [
                        'oxid'                     => '_testId_4_child_1',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_4',
                        'field2shop' => [
                                'oxshopid' => 2,
                                'oxprice' => 70,
                        ],
                        'inheritToShops'           => [2],
                ],
                9 => [
                        'oxid'                     => '_testId_4_child_2',
                        'oxprice'                  => 50,
                        'oxparentid'               => '_testId_4',
                        'field2shop' => [
                                'oxshopid' => 2,
                                'oxprice' => 60,
                        ],
                        'inheritToShops'           => [2],
                ],
        ],

        'shop' => [
                0 => [
                        'oxactive'     => 1,
                        'oxid'   => 2,
                        'oxparentid'   => 1,
                        'oxname'       => 'subshop',
                        'oxisinherited' => 1,
                        'activeshop'     => true,
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
                        'base_price' => '40,00',
                        'price' => '40,00',
                        'min_price' => '40,00',
                        'var_min_price' => '60,00',
                        'is_range_price' => true,
                ],
                '_testId_3' => [
                        'base_price' => '100,00',
                        'price' => '100,00',
                        'min_price' => '60,00',
                        'var_min_price' => '60,00',
                        'is_range_price' => true,
                ],
                '_testId_4' => [
                        'base_price' => '60,00',
                        'price' => '60,00',
                        'min_price' => '60,00',
                        'var_min_price' => '60,00',
                        'is_range_price' => true,
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
