<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_sd_databomb_user_14',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '9db3770cd3d173bc1737cafd3a6be',
      'oxprice' => 539.54,
      'oxvat' => 18,
      'amount' => 691,
    ],
    1 =>
    [
      'oxid' => '7fb746d2fe499b87cfc51d60b57fa',
      'oxprice' => 243.97,
      'oxvat' => 18,
      'amount' => 556,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 11,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '9db3770cd3d173bc1737cafd3a6be',
      ],
    ],
    1 =>
    [
      'oxaddsum' => 9,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '9db3770cd3d173bc1737cafd3a6be',
      ],
    ],
    2 =>
    [
      'oxaddsum' => 10,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '9db3770cd3d173bc1737cafd3a6be',
      ],
    ],
    3 =>
    [
      'oxaddsum' => 1,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '9db3770cd3d173bc1737cafd3a6be',
      ],
    ],
    4 =>
    [
      'oxaddsum' => 10,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '9db3770cd3d173bc1737cafd3a6be',
        1 => '7fb746d2fe499b87cfc51d60b57fa',
      ],
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 19,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '9db3770cd3d173bc1737cafd3a6be',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 37,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '9db3770cd3d173bc1737cafd3a6be',
          1 => '7fb746d2fe499b87cfc51d60b57fa',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 38,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '9db3770cd3d173bc1737cafd3a6be',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 8,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 4,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 26,
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 15,
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
        'oxaddsum' => 16,
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
        'oxaddsum' => 12,
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
      '9db3770cd3d173bc1737cafd3a6be' =>
      [
        0 => '590,59',
        1 => '408.097,69',
      ],
      '7fb746d2fe499b87cfc51d60b57fa' =>
      [
        0 => '277,88',
        1 => '154.501,28',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        18 => '85.820,18',
      ],
      'wrapping' =>
      [
        'brutto' => '46.830,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '157.542,72',
        'netto' => '133.510,78',
        'vat' => '24.031,94',
      ],
      'payment' =>
      [
        'brutto' => '8,00',
        'netto' => '6,78',
        'vat' => '1,22',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '476.778,79',
      'totalBrutto' => '562.598,97',
      'grandTotal' => '766.979,69',
    ],
  ],
];
