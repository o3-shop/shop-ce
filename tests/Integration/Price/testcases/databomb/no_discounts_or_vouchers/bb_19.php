<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_19',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '8613f67b4f13ce79ac0b8d3526730',
      'oxprice' => 809.69,
      'oxvat' => 0,
      'amount' => 272,
    ],
    1 =>
    [
      'oxid' => '1055ef563fe97bc160550d67f85e0',
      'oxprice' => 759.05,
      'oxvat' => 0,
      'amount' => 24,
    ],
    2 =>
    [
      'oxid' => '1e0ab487d5a20b3f189f71adb3fd9',
      'oxprice' => 248.87,
      'oxvat' => 0,
      'amount' => 315,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 20,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8613f67b4f13ce79ac0b8d3526730',
          1 => '1055ef563fe97bc160550d67f85e0',
          2 => '1e0ab487d5a20b3f189f71adb3fd9',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 98,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8613f67b4f13ce79ac0b8d3526730',
          1 => '1055ef563fe97bc160550d67f85e0',
          2 => '1e0ab487d5a20b3f189f71adb3fd9',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 17,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8613f67b4f13ce79ac0b8d3526730',
          1 => '1055ef563fe97bc160550d67f85e0',
          2 => '1e0ab487d5a20b3f189f71adb3fd9',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 26,
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
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 70,
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
        'oxaddsum' => 45,
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
        'oxaddsum' => 63,
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
        'oxaddsum' => 32,
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
      '8613f67b4f13ce79ac0b8d3526730' =>
      [
        0 => '809,69',
        1 => '220.235,68',
      ],
      '1055ef563fe97bc160550d67f85e0' =>
      [
        0 => '759,05',
        1 => '18.217,20',
      ],
      '1e0ab487d5a20b3f189f71adb3fd9' =>
      [
        0 => '248,87',
        1 => '78.394,05',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        0 => '0,00',
      ],
      'wrapping' =>
      [
        'brutto' => '10.387,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '342.226,69',
        'netto' => '342.226,69',
        'vat' => false,
      ],
      'payment' =>
      [
        'brutto' => '171.359,14',
        'netto' => '171.359,14',
        'vat' => false,
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '316.846,93',
      'totalBrutto' => '316.846,93',
      'grandTotal' => '840.819,76',
    ],
  ],
];
