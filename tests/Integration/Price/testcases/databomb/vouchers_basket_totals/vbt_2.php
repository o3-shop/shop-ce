<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_2',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'f1f865b24a1d1b1bf23065f928fda',
      'oxprice' => 478.62,
      'oxvat' => 21,
      'amount' => 53,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 79,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f1f865b24a1d1b1bf23065f928fda',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 90,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f1f865b24a1d1b1bf23065f928fda',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 76,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 52,
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
        'oxaddsumtype' => '%',
        'oxaddsum' => 69,
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
        'oxdiscount' => 24,
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
      'f1f865b24a1d1b1bf23065f928fda' =>
      [
        0 => '478,62',
        1 => '25.366,86',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        21 => '1.932,60',
      ],
      'wrapping' =>
      [
        'brutto' => '4.770,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '17.557,13',
        'netto' => '14.510,02',
        'vat' => '3.047,11',
      ],
      'payment' =>
      [
        'brutto' => '21.806,35',
        'netto' => '18.021,78',
        'vat' => '3.784,57',
      ],
      'voucher' =>
      [
        'brutto' => '14.231,43',
      ],
      'totalNetto' => '9.202,83',
      'totalBrutto' => '25.366,86',
      'grandTotal' => '55.268,91',
    ],
  ],
];
