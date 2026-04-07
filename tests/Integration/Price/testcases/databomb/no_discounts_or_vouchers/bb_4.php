<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_4',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'be03ff50fad81646ff9548e7c67a2',
      'oxprice' => 561.88,
      'oxvat' => 42,
      'amount' => 893,
    ],
    1 =>
    [
      'oxid' => '0e181c47bd990f5605f02ea7a30b8',
      'oxprice' => 172.99,
      'oxvat' => 42,
      'amount' => 132,
    ],
    2 =>
    [
      'oxid' => '37427e09f14fe8ee394235f57bc37',
      'oxprice' => 501.18,
      'oxvat' => 11,
      'amount' => 510,
    ],
    3 =>
    [
      'oxid' => 'ae4fc1114759a41c48c953feb906e',
      'oxprice' => 200.09,
      'oxvat' => 28,
      'amount' => 24,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 36,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'be03ff50fad81646ff9548e7c67a2',
          1 => '0e181c47bd990f5605f02ea7a30b8',
          2 => '37427e09f14fe8ee394235f57bc37',
          3 => 'ae4fc1114759a41c48c953feb906e',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 15,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'be03ff50fad81646ff9548e7c67a2',
          1 => '0e181c47bd990f5605f02ea7a30b8',
          2 => '37427e09f14fe8ee394235f57bc37',
          3 => 'ae4fc1114759a41c48c953feb906e',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 16,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'be03ff50fad81646ff9548e7c67a2',
          1 => '0e181c47bd990f5605f02ea7a30b8',
          2 => '37427e09f14fe8ee394235f57bc37',
          3 => 'ae4fc1114759a41c48c953feb906e',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 7,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 94,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 25,
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
        'oxaddsum' => 9,
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
        'oxaddsum' => 68,
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
        'oxaddsum' => 39,
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
      'blEnterNetPrice' => false,
      'blShowNetPrice' => false,
    ],
    'activeCurrencyRate' => 1,
  ],
  'expected' =>
  [
    'articles' =>
    [
      'be03ff50fad81646ff9548e7c67a2' =>
      [
        0 => '561,88',
        1 => '501.758,84',
      ],
      '0e181c47bd990f5605f02ea7a30b8' =>
      [
        0 => '172,99',
        1 => '22.834,68',
      ],
      '37427e09f14fe8ee394235f57bc37' =>
      [
        0 => '501,18',
        1 => '255.601,80',
      ],
      'ae4fc1114759a41c48c953feb906e' =>
      [
        0 => '200,09',
        1 => '4.802,16',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        42 => '155.161,46',
        11 => '25.329,91',
        28 => '1.050,47',
      ],
      'wrapping' =>
      [
        'brutto' => '24.944,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '910.597,08',
        'netto' => '641.265,55',
        'vat' => '269.331,53',
      ],
      'payment' =>
      [
        'brutto' => '118.691,62',
        'netto' => '83.585,65',
        'vat' => '35.105,97',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '603.455,64',
      'totalBrutto' => '784.997,48',
      'grandTotal' => '1.839.230,18',
    ],
  ],
];
