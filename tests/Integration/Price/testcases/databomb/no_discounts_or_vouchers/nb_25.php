<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_25',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'f902dfb2f7f69b5ef7dfff40439ae',
      'oxprice' => 808.95,
      'oxvat' => 15,
      'amount' => 326,
    ],
    1 =>
    [
      'oxid' => '2b574e975fbc77e1a0b250d0332f8',
      'oxprice' => 355.97,
      'oxvat' => 15,
      'amount' => 27,
    ],
    2 =>
    [
      'oxid' => 'c3d028065f6deb470ef414cdfd484',
      'oxprice' => 301.89,
      'oxvat' => 15,
      'amount' => 885,
    ],
    3 =>
    [
      'oxid' => 'c1afcf7cd275567d4780b9a2a4ba7',
      'oxprice' => 362.56,
      'oxvat' => 15,
      'amount' => 555,
    ],
    4 =>
    [
      'oxid' => 'b898d0bd0f7f87203209aa1de517a',
      'oxprice' => 376.19,
      'oxvat' => 15,
      'amount' => 999,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 4,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f902dfb2f7f69b5ef7dfff40439ae',
          1 => '2b574e975fbc77e1a0b250d0332f8',
          2 => 'c3d028065f6deb470ef414cdfd484',
          3 => 'c1afcf7cd275567d4780b9a2a4ba7',
          4 => 'b898d0bd0f7f87203209aa1de517a',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 35,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f902dfb2f7f69b5ef7dfff40439ae',
          1 => '2b574e975fbc77e1a0b250d0332f8',
          2 => 'c3d028065f6deb470ef414cdfd484',
          3 => 'c1afcf7cd275567d4780b9a2a4ba7',
          4 => 'b898d0bd0f7f87203209aa1de517a',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 10,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f902dfb2f7f69b5ef7dfff40439ae',
          1 => '2b574e975fbc77e1a0b250d0332f8',
          2 => 'c3d028065f6deb470ef414cdfd484',
          3 => 'c1afcf7cd275567d4780b9a2a4ba7',
          4 => 'b898d0bd0f7f87203209aa1de517a',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 38,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 42,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 90,
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
        'oxaddsumtype' => '%',
        'oxaddsum' => 70,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 3,
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
      'f902dfb2f7f69b5ef7dfff40439ae' =>
      [
        0 => '930,29',
        1 => '303.274,54',
      ],
      '2b574e975fbc77e1a0b250d0332f8' =>
      [
        0 => '409,37',
        1 => '11.052,99',
      ],
      'c3d028065f6deb470ef414cdfd484' =>
      [
        0 => '347,17',
        1 => '307.245,45',
      ],
      'c1afcf7cd275567d4780b9a2a4ba7' =>
      [
        0 => '416,94',
        1 => '231.401,70',
      ],
      'b898d0bd0f7f87203209aa1de517a' =>
      [
        0 => '432,62',
        1 => '432.187,38',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        15 => '167.629,83',
      ],
      'wrapping' =>
      [
        'brutto' => '27.920,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.465.084,74',
        'netto' => '1.273.986,73',
        'vat' => '191.098,01',
      ],
      'payment' =>
      [
        'brutto' => '38,00',
        'netto' => '33,04',
        'vat' => '4,96',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.117.532,23',
      'totalBrutto' => '1.285.162,06',
      'grandTotal' => '2.778.204,80',
    ],
  ],
];
