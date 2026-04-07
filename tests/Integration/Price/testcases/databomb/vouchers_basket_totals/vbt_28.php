<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_28',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '895dbb1219c53252481faacbff994',
      'oxprice' => 25.03,
      'oxvat' => 30,
      'amount' => 157,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 86,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '895dbb1219c53252481faacbff994',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 73,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '895dbb1219c53252481faacbff994',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 56,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 50,
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
        'oxaddsum' => 26,
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
        'oxaddsum' => 16,
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
      '895dbb1219c53252481faacbff994' =>
      [
        0 => '25,03',
        1 => '3.929,71',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        30 => '518,53',
      ],
      'wrapping' =>
      [
        'brutto' => '11.461,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.037,72',
        'netto' => '798,25',
        'vat' => '239,47',
      ],
      'payment' =>
      [
        'brutto' => '56,00',
        'netto' => '43,08',
        'vat' => '12,92',
      ],
      'voucher' =>
      [
        'brutto' => '1.682,75',
      ],
      'totalNetto' => '1.728,43',
      'totalBrutto' => '3.929,71',
      'grandTotal' => '14.801,68',
    ],
  ],
];
