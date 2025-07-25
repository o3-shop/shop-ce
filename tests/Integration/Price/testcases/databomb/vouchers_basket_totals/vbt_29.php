<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_29',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'c1a2959471539facb8906aefadc92',
      'oxprice' => 526.85,
      'oxvat' => 15,
      'amount' => 690,
    ],
    1 =>
    [
      'oxid' => '4155e1afdff93d5d35d7dae6325b0',
      'oxprice' => 334.11,
      'oxvat' => 15,
      'amount' => 809,
    ],
    2 =>
    [
      'oxid' => 'a1a7e877bd1cb9f9379bd90eb9cf5',
      'oxprice' => 630.82,
      'oxvat' => 34,
      'amount' => 699,
    ],
    3 =>
    [
      'oxid' => 'cb3d0b4a4f378d15ac8c4809abac4',
      'oxprice' => 270.23,
      'oxvat' => 15,
      'amount' => 244,
    ],
    4 =>
    [
      'oxid' => '7fa6826cddc73507c4ad9e27c5dbc',
      'oxprice' => 572.63,
      'oxvat' => 34,
      'amount' => 990,
    ],
    5 =>
    [
      'oxid' => 'b7d0e641c210a12c95377adbfec53',
      'oxprice' => 474.23,
      'oxvat' => 15,
      'amount' => 964,
    ],
    6 =>
    [
      'oxid' => 'ef90e72985286f669a24cc68d71f6',
      'oxprice' => 861.82,
      'oxvat' => 34,
      'amount' => 582,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 99,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c1a2959471539facb8906aefadc92',
          1 => '4155e1afdff93d5d35d7dae6325b0',
          2 => 'a1a7e877bd1cb9f9379bd90eb9cf5',
          3 => 'cb3d0b4a4f378d15ac8c4809abac4',
          4 => '7fa6826cddc73507c4ad9e27c5dbc',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 94,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c1a2959471539facb8906aefadc92',
          1 => '4155e1afdff93d5d35d7dae6325b0',
          2 => 'a1a7e877bd1cb9f9379bd90eb9cf5',
          3 => 'cb3d0b4a4f378d15ac8c4809abac4',
          4 => '7fa6826cddc73507c4ad9e27c5dbc',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 11,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 42,
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
        'oxaddsum' => 54,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 51,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
    ],
    'voucherserie' =>
    [
      0 =>
      [
        'oxdiscount' => 17,
        'oxdiscounttype' => 'percent',
        'oxallowsameseries' => 1,
        'oxallowotherseries' => 1,
        'oxallowuseanother' => 1,
        'voucher_count' => 3,
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
      'c1a2959471539facb8906aefadc92' =>
      [
        0 => '526,85',
        1 => '363.526,50',
      ],
      '4155e1afdff93d5d35d7dae6325b0' =>
      [
        0 => '334,11',
        1 => '270.294,99',
      ],
      'a1a7e877bd1cb9f9379bd90eb9cf5' =>
      [
        0 => '630,82',
        1 => '440.943,18',
      ],
      'cb3d0b4a4f378d15ac8c4809abac4' =>
      [
        0 => '270,23',
        1 => '65.936,12',
      ],
      '7fa6826cddc73507c4ad9e27c5dbc' =>
      [
        0 => '572,63',
        1 => '566.903,70',
      ],
      'b7d0e641c210a12c95377adbfec53' =>
      [
        0 => '474,23',
        1 => '457.157,72',
      ],
      'ef90e72985286f669a24cc68d71f6' =>
      [
        0 => '861,82',
        1 => '501.579,24',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        15 => '86.283,80',
        34 => '218.987,97',
      ],
      'wrapping' =>
      [
        'brutto' => '322.608,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '105,00',
        'netto' => '78,36',
        'vat' => '26,64',
      ],
      'payment' =>
      [
        'brutto' => '11,00',
        'netto' => '8,21',
        'vat' => '2,79',
      ],
      'voucher' =>
      [
        'brutto' => '1.141.762,08',
      ],
      'totalNetto' => '1.219.307,60',
      'totalBrutto' => '2.666.341,45',
      'grandTotal' => '1.847.303,37',
    ],
  ],
];
