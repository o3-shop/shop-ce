<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_23',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'f3ef565757375457fe3bc9bd859b8',
      'oxprice' => 139.51,
      'oxvat' => 19,
      'amount' => 262,
    ],
    1 =>
    [
      'oxid' => '44e409d94cedfdb42ba7d1b455867',
      'oxprice' => 120.55,
      'oxvat' => 40,
      'amount' => 931,
    ],
    2 =>
    [
      'oxid' => 'daadc536db833ff7cd2f62942b416',
      'oxprice' => 570.95,
      'oxvat' => 19,
      'amount' => 441,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 26,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f3ef565757375457fe3bc9bd859b8',
          1 => '44e409d94cedfdb42ba7d1b455867',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 56,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f3ef565757375457fe3bc9bd859b8',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 29,
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
    ],
    'delivery' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 17,
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
        'oxaddsum' => 8,
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
        'oxdiscount' => 25,
        'oxdiscounttype' => 'percent',
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
      'f3ef565757375457fe3bc9bd859b8' =>
      [
        0 => '166,02',
        1 => '43.497,24',
      ],
      '44e409d94cedfdb42ba7d1b455867' =>
      [
        0 => '168,77',
        1 => '157.124,87',
      ],
      'daadc536db833ff7cd2f62942b416' =>
      [
        0 => '679,43',
        1 => '299.628,63',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        19 => '30.816,45',
        40 => '25.252,21',
      ],
      'wrapping' =>
      [
        'brutto' => '38.878,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '85.050,63',
        'netto' => '71.471,12',
        'vat' => '13.579,51',
      ],
      'payment' =>
      [
        'brutto' => '106.268,08',
        'netto' => '89.300,91',
        'vat' => '16.967,17',
      ],
      'voucher' =>
      [
        'brutto' => '218.859,70',
      ],
      'totalNetto' => '225.322,38',
      'totalBrutto' => '500.250,74',
      'grandTotal' => '511.587,75',
    ],
  ],
];
