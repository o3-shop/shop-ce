<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_6',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '79c189d7f0bc36e53285eeb241dcd',
      'oxprice' => 965.98,
      'oxvat' => 30,
      'amount' => 634,
    ],
    1 =>
    [
      'oxid' => '9c4518edfe67d7353430ac2e856c0',
      'oxprice' => 809.82,
      'oxvat' => 10,
      'amount' => 780,
    ],
    2 =>
    [
      'oxid' => 'f39334e6121f2867e832d42a0d564',
      'oxprice' => 3.34,
      'oxvat' => 26,
      'amount' => 24,
    ],
    3 =>
    [
      'oxid' => 'b928528095bcec1ceb9611307b343',
      'oxprice' => 332.07,
      'oxvat' => 26,
      'amount' => 650,
    ],
    4 =>
    [
      'oxid' => '9cefce850af56482b320e0d9304e8',
      'oxprice' => 208.15,
      'oxvat' => 26,
      'amount' => 861,
    ],
    5 =>
    [
      'oxid' => 'dfda4a539b4ce4c09f9cafb403120',
      'oxprice' => 757.32,
      'oxvat' => 26,
      'amount' => 430,
    ],
    6 =>
    [
      'oxid' => 'caaec1f17506640faff7422160fa9',
      'oxprice' => 809.6,
      'oxvat' => 10,
      'amount' => 714,
    ],
    7 =>
    [
      'oxid' => 'fd8f6ec1dab77da474ae266a025c5',
      'oxprice' => 393.84,
      'oxvat' => 10,
      'amount' => 284,
    ],
    8 =>
    [
      'oxid' => 'ea68ad8ab3dc53ddce502338a5531',
      'oxprice' => 813.08,
      'oxvat' => 30,
      'amount' => 598,
    ],
    9 =>
    [
      'oxid' => 'e8d028510656411ca82f1bfd89582',
      'oxprice' => 618.28,
      'oxvat' => 26,
      'amount' => 973,
    ],
    10 =>
    [
      'oxid' => '97371cd95c36901980fa557759f04',
      'oxprice' => 120.57,
      'oxvat' => 26,
      'amount' => 460,
    ],
    11 =>
    [
      'oxid' => 'b11f8a70f33bd7a8e61a6cfa8f754',
      'oxprice' => 8.18,
      'oxvat' => 30,
      'amount' => 626,
    ],
    12 =>
    [
      'oxid' => '752ebe7652c67d61fb1bf630d2abf',
      'oxprice' => 813.69,
      'oxvat' => 30,
      'amount' => 434,
    ],
    13 =>
    [
      'oxid' => '96167719a4bfded36a38f83409ba9',
      'oxprice' => 975.39,
      'oxvat' => 10,
      'amount' => 967,
    ],
    14 =>
    [
      'oxid' => 'a3a245acae4049f32df7928084b21',
      'oxprice' => 206.77,
      'oxvat' => 10,
      'amount' => 6,
    ],
    15 =>
    [
      'oxid' => '7a218c79378612a18a3c624b60d91',
      'oxprice' => 990.86,
      'oxvat' => 30,
      'amount' => 720,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 2,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '79c189d7f0bc36e53285eeb241dcd',
          1 => '9c4518edfe67d7353430ac2e856c0',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 42,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '79c189d7f0bc36e53285eeb241dcd',
          1 => '9c4518edfe67d7353430ac2e856c0',
          2 => 'f39334e6121f2867e832d42a0d564',
          3 => 'b928528095bcec1ceb9611307b343',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 12,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 33,
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
        'oxaddsum' => 20,
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
    ],
    'voucherserie' =>
    [
      0 =>
      [
        'oxdiscount' => 33,
        'oxdiscounttype' => 'absolute',
        'oxallowsameseries' => 1,
        'oxallowotherseries' => 1,
        'oxallowuseanother' => 1,
        'voucher_count' => 2,
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
      '79c189d7f0bc36e53285eeb241dcd' =>
      [
        0 => '1.255,77',
        1 => '796.158,18',
      ],
      '9c4518edfe67d7353430ac2e856c0' =>
      [
        0 => '890,80',
        1 => '694.824,00',
      ],
      'f39334e6121f2867e832d42a0d564' =>
      [
        0 => '4,21',
        1 => '101,04',
      ],
      'b928528095bcec1ceb9611307b343' =>
      [
        0 => '418,41',
        1 => '271.966,50',
      ],
      '9cefce850af56482b320e0d9304e8' =>
      [
        0 => '262,27',
        1 => '225.814,47',
      ],
      'dfda4a539b4ce4c09f9cafb403120' =>
      [
        0 => '954,22',
        1 => '410.314,60',
      ],
      'caaec1f17506640faff7422160fa9' =>
      [
        0 => '890,56',
        1 => '635.859,84',
      ],
      'fd8f6ec1dab77da474ae266a025c5' =>
      [
        0 => '433,22',
        1 => '123.034,48',
      ],
      'ea68ad8ab3dc53ddce502338a5531' =>
      [
        0 => '1.057,00',
        1 => '632.086,00',
      ],
      'e8d028510656411ca82f1bfd89582' =>
      [
        0 => '779,03',
        1 => '757.996,19',
      ],
      '97371cd95c36901980fa557759f04' =>
      [
        0 => '151,92',
        1 => '69.883,20',
      ],
      'b11f8a70f33bd7a8e61a6cfa8f754' =>
      [
        0 => '10,63',
        1 => '6.654,38',
      ],
      '752ebe7652c67d61fb1bf630d2abf' =>
      [
        0 => '1.057,80',
        1 => '459.085,20',
      ],
      '96167719a4bfded36a38f83409ba9' =>
      [
        0 => '1.072,93',
        1 => '1.037.523,31',
      ],
      'a3a245acae4049f32df7928084b21' =>
      [
        0 => '227,45',
        1 => '1.364,70',
      ],
      '7a218c79378612a18a3c624b60d91' =>
      [
        0 => '1.288,12',
        1 => '927.446,40',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        30 => '651.093,17',
        10 => '226.598,45',
        26 => '358.234,55',
      ],
      'wrapping' =>
      [
        'brutto' => '87.696,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '493.527,87',
        'netto' => '379.636,82',
        'vat' => '113.891,05',
      ],
      'payment' =>
      [
        'brutto' => '12,00',
        'netto' => '9,23',
        'vat' => '2,77',
      ],
      'voucher' =>
      [
        'brutto' => '66,00',
      ],
      'totalNetto' => '5.814.120,32',
      'totalBrutto' => '7.050.112,49',
      'grandTotal' => '7.631.282,36',
    ],
  ],
];
