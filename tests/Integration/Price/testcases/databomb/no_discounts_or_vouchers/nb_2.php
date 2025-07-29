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
      'oxid' => '8176aaf484187c03769d9e5cc6550',
      'oxprice' => 534.53,
      'oxvat' => 22,
      'amount' => 114,
    ],
    1 =>
    [
      'oxid' => '09adb93d36ab632a7a53134e228c2',
      'oxprice' => 20.23,
      'oxvat' => 22,
      'amount' => 957,
    ],
    2 =>
    [
      'oxid' => 'e980cd16209e6021210d42d9d8c91',
      'oxprice' => 760.76,
      'oxvat' => 22,
      'amount' => 108,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 31,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8176aaf484187c03769d9e5cc6550',
          1 => '09adb93d36ab632a7a53134e228c2',
          2 => 'e980cd16209e6021210d42d9d8c91',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 89,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8176aaf484187c03769d9e5cc6550',
          1 => '09adb93d36ab632a7a53134e228c2',
          2 => 'e980cd16209e6021210d42d9d8c91',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 78,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8176aaf484187c03769d9e5cc6550',
          1 => '09adb93d36ab632a7a53134e228c2',
          2 => 'e980cd16209e6021210d42d9d8c91',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 36,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 23,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 91,
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 5,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 62,
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
      '8176aaf484187c03769d9e5cc6550' =>
      [
        0 => '652,13',
        1 => '74.342,82',
      ],
      '09adb93d36ab632a7a53134e228c2' =>
      [
        0 => '24,68',
        1 => '23.618,76',
      ],
      'e980cd16209e6021210d42d9d8c91' =>
      [
        0 => '928,13',
        1 => '100.238,04',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        22 => '35.740,92',
      ],
      'wrapping' =>
      [
        'brutto' => '91.962,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '83,00',
        'netto' => '68,03',
        'vat' => '14,97',
      ],
      'payment' =>
      [
        'brutto' => '36,00',
        'netto' => '29,51',
        'vat' => '6,49',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '162.458,70',
      'totalBrutto' => '198.199,62',
      'grandTotal' => '290.280,62',
    ],
  ],
];
