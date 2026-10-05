<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_3',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'a5e273cd64d63a695e6e2a458567f',
      'oxprice' => 209.77,
      'oxvat' => 22,
      'amount' => 50,
    ],
    1 =>
    [
      'oxid' => 'c426905974c9f6ab5dd77a56c8b46',
      'oxprice' => 994.05,
      'oxvat' => 24,
      'amount' => 515,
    ],
    2 =>
    [
      'oxid' => '8e393c944f92e84b01147b35de2be',
      'oxprice' => 603.77,
      'oxvat' => 22,
      'amount' => 895,
    ],
    3 =>
    [
      'oxid' => 'aa6317984f775d0b10df645943a4e',
      'oxprice' => 160.55,
      'oxvat' => 24,
      'amount' => 474,
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
          0 => 'a5e273cd64d63a695e6e2a458567f',
          1 => 'c426905974c9f6ab5dd77a56c8b46',
          2 => '8e393c944f92e84b01147b35de2be',
          3 => 'aa6317984f775d0b10df645943a4e',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 62,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'a5e273cd64d63a695e6e2a458567f',
          1 => 'c426905974c9f6ab5dd77a56c8b46',
          2 => '8e393c944f92e84b01147b35de2be',
          3 => 'aa6317984f775d0b10df645943a4e',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 64,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'a5e273cd64d63a695e6e2a458567f',
          1 => 'c426905974c9f6ab5dd77a56c8b46',
          2 => '8e393c944f92e84b01147b35de2be',
          3 => 'aa6317984f775d0b10df645943a4e',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 99,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 44,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 12,
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
        'oxaddsum' => 70,
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
        'oxaddsum' => 23,
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
        'oxaddsum' => 98,
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
      'a5e273cd64d63a695e6e2a458567f' =>
      [
        0 => '209,77',
        1 => '10.488,50',
      ],
      'c426905974c9f6ab5dd77a56c8b46' =>
      [
        0 => '994,05',
        1 => '511.935,75',
      ],
      '8e393c944f92e84b01147b35de2be' =>
      [
        0 => '603,77',
        1 => '540.374,15',
      ],
      'aa6317984f775d0b10df645943a4e' =>
      [
        0 => '160,55',
        1 => '76.100,70',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        22 => '99.335,89',
        24 => '113.813,51',
      ],
      'wrapping' =>
      [
        'brutto' => '123.776,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '797.350,37',
        'netto' => '643.024,49',
        'vat' => '154.325,88',
      ],
      'payment' =>
      [
        'brutto' => '99,00',
        'netto' => '79,84',
        'vat' => '19,16',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '925.749,70',
      'totalBrutto' => '1.138.899,10',
      'grandTotal' => '2.060.124,47',
    ],
  ],
];
