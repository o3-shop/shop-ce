<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_bd_databomb_user_9',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '2664e5c28b2e43b1ec9c41ee57595',
      'oxprice' => 101.1,
      'oxvat' => 5,
      'amount' => 164,
    ],
    1 =>
    [
      'oxid' => 'ef7906cd983ab7002041e3cfe5f2c',
      'oxprice' => 818.94,
      'oxvat' => 5,
      'amount' => 200,
    ],
    2 =>
    [
      'oxid' => '4869abd52585f60446ac7deb1bed5',
      'oxprice' => 291.57,
      'oxvat' => 5,
      'amount' => 789,
    ],
    3 =>
    [
      'oxid' => '8eb9fa86d2912db8b5970279d8d18',
      'oxprice' => 118,
      'oxvat' => 5,
      'amount' => 898,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 3,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    1 =>
    [
      'oxaddsum' => 3,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    2 =>
    [
      'oxaddsum' => 8,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    3 =>
    [
      'oxaddsum' => 10,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    4 =>
    [
      'oxaddsum' => 15,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 15,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '2664e5c28b2e43b1ec9c41ee57595',
          1 => 'ef7906cd983ab7002041e3cfe5f2c',
          2 => '4869abd52585f60446ac7deb1bed5',
          3 => '8eb9fa86d2912db8b5970279d8d18',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 38,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '2664e5c28b2e43b1ec9c41ee57595',
          1 => 'ef7906cd983ab7002041e3cfe5f2c',
          2 => '4869abd52585f60446ac7deb1bed5',
          3 => '8eb9fa86d2912db8b5970279d8d18',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 92,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '2664e5c28b2e43b1ec9c41ee57595',
          1 => 'ef7906cd983ab7002041e3cfe5f2c',
          2 => '4869abd52585f60446ac7deb1bed5',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 20,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 21,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 29,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
    ],
    'delivery' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 16,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 7,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 9,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
    ],
  ],
  'options' =>
  [
    'config' =>
    [
      'blEnterNetPrice' => true,
      'blShowNetPrice' => false,
    ],
    'activeCurrencyRate' => 1,
  ],
  'expected' =>
  [
    'articles' =>
    [
      '2664e5c28b2e43b1ec9c41ee57595' =>
      [
        0 => '106,16',
        1 => '17.410,24',
      ],
      'ef7906cd983ab7002041e3cfe5f2c' =>
      [
        0 => '859,89',
        1 => '171.978,00',
      ],
      '4869abd52585f60446ac7deb1bed5' =>
      [
        0 => '306,15',
        1 => '241.552,35',
      ],
      '8eb9fa86d2912db8b5970279d8d18' =>
      [
        0 => '123,90',
        1 => '111.262,20',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '3,00',
        'bombDiscount_1' => '16.265,99',
        'bombDiscount_2' => '42.074,70',
        'bombDiscount_3' => '48.385,91',
        'bombDiscount_4' => '65.320,98',
      ],
      'vats' =>
      [
        5 => '17.626,30',
      ],
      'wrapping' =>
      [
        'brutto' => '140.200,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '173.504,90',
        'netto' => '165.242,76',
        'vat' => '8.262,14',
      ],
      'payment' =>
      [
        'brutto' => '108.731,42',
        'netto' => '103.553,73',
        'vat' => '5.177,69',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '352.525,91',
      'totalBrutto' => '542.202,79',
      'grandTotal' => '792.588,53',
    ],
  ],
];
