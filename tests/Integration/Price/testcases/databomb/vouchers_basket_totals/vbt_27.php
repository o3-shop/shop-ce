<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_27',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '3951e7d277f01f8fe846437d4556b',
      'oxprice' => 328.81,
      'oxvat' => 5,
      'amount' => 632,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 81,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '3951e7d277f01f8fe846437d4556b',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 5,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '3951e7d277f01f8fe846437d4556b',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 92,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 28,
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
        'oxaddsum' => 41,
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
        'oxaddsum' => 95,
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
        'oxdiscount' => 6,
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
      '3951e7d277f01f8fe846437d4556b' =>
      [
        0 => '328,81',
        1 => '207.807,92',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        5 => '8.219,14',
      ],
      'wrapping' =>
      [
        'brutto' => '3.160,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '85.296,25',
        'netto' => '81.234,52',
        'vat' => '4.061,73',
      ],
      'payment' =>
      [
        'brutto' => '92,00',
        'netto' => '87,62',
        'vat' => '4,38',
      ],
      'voucher' =>
      [
        'brutto' => '35.205,99',
      ],
      'totalNetto' => '164.382,79',
      'totalBrutto' => '207.807,92',
      'grandTotal' => '261.150,18',
    ],
  ],
];
