<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_20',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '01c9103a1902bfaae7b950a2bc7cf',
      'oxprice' => 812.2,
      'oxvat' => 21,
      'amount' => 923,
    ],
    1 =>
    [
      'oxid' => 'd07e6dc66ed60e916d835f72eb91e',
      'oxprice' => 102.85,
      'oxvat' => 21,
      'amount' => 419,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 5,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '01c9103a1902bfaae7b950a2bc7cf',
          1 => 'd07e6dc66ed60e916d835f72eb91e',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 87,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '01c9103a1902bfaae7b950a2bc7cf',
          1 => 'd07e6dc66ed60e916d835f72eb91e',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 32,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 62,
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
        'oxaddsum' => 98,
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
        'oxaddsum' => 47,
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
      '01c9103a1902bfaae7b950a2bc7cf' =>
      [
        0 => '812,20',
        1 => '749.660,60',
      ],
      'd07e6dc66ed60e916d835f72eb91e' =>
      [
        0 => '102,85',
        1 => '43.094,15',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        21 => '137.568,35',
      ],
      'wrapping' =>
      [
        'brutto' => '116.754,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '776.946,66',
        'netto' => '642.104,68',
        'vat' => '134.841,98',
      ],
      'payment' =>
      [
        'brutto' => '502.272,77',
        'netto' => '415.101,46',
        'vat' => '87.171,31',
      ],
      'voucher' =>
      [
        'brutto' => '99,00',
      ],
      'totalNetto' => '655.087,40',
      'totalBrutto' => '792.754,75',
      'grandTotal' => '2.188.629,18',
    ],
  ],
];
