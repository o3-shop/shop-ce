<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_18',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'e79dde671471c76e3d64177ce081c',
      'oxprice' => 929.29,
      'oxvat' => 11,
      'amount' => 429,
    ],
    1 =>
    [
      'oxid' => '34dc2f21ffb5351e383614cdd77d5',
      'oxprice' => 349.01,
      'oxvat' => 20,
      'amount' => 103,
    ],
    2 =>
    [
      'oxid' => '8a837da3166436e64ca927731c6a3',
      'oxprice' => 661.26,
      'oxvat' => 11,
      'amount' => 942,
    ],
    3 =>
    [
      'oxid' => 'f11a0e94e135194fdbb36f4f621a8',
      'oxprice' => 905.81,
      'oxvat' => 8,
      'amount' => 187,
    ],
    4 =>
    [
      'oxid' => 'ac67e02d6c5429a48e2e1d70bd5c3',
      'oxprice' => 639.97,
      'oxvat' => 8,
      'amount' => 576,
    ],
    5 =>
    [
      'oxid' => 'd45b758666d6ea3d9d416f6898aba',
      'oxprice' => 619.06,
      'oxvat' => 8,
      'amount' => 4,
    ],
    6 =>
    [
      'oxid' => '35867e60bf4ae0579b2c99ce108e3',
      'oxprice' => 582.23,
      'oxvat' => 8,
      'amount' => 495,
    ],
    7 =>
    [
      'oxid' => '0be520e084c2c6baf3501fb617416',
      'oxprice' => 982.57,
      'oxvat' => 8,
      'amount' => 735,
    ],
    8 =>
    [
      'oxid' => '85f2d7b72ee67d966897d80f65ffb',
      'oxprice' => 560.5,
      'oxvat' => 11,
      'amount' => 451,
    ],
    9 =>
    [
      'oxid' => 'b44bc62bff67eede29285dc7d1c9e',
      'oxprice' => 633.49,
      'oxvat' => 8,
      'amount' => 171,
    ],
    10 =>
    [
      'oxid' => 'd0b1b7075c1045802a156d111998d',
      'oxprice' => 581.49,
      'oxvat' => 8,
      'amount' => 219,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 33,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'e79dde671471c76e3d64177ce081c',
          1 => '34dc2f21ffb5351e383614cdd77d5',
          2 => '8a837da3166436e64ca927731c6a3',
          3 => 'f11a0e94e135194fdbb36f4f621a8',
          4 => 'ac67e02d6c5429a48e2e1d70bd5c3',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 50,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'e79dde671471c76e3d64177ce081c',
          1 => '34dc2f21ffb5351e383614cdd77d5',
          2 => '8a837da3166436e64ca927731c6a3',
          3 => 'f11a0e94e135194fdbb36f4f621a8',
          4 => 'ac67e02d6c5429a48e2e1d70bd5c3',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 79,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'e79dde671471c76e3d64177ce081c',
          1 => '34dc2f21ffb5351e383614cdd77d5',
          2 => '8a837da3166436e64ca927731c6a3',
          3 => 'f11a0e94e135194fdbb36f4f621a8',
          4 => 'ac67e02d6c5429a48e2e1d70bd5c3',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 52,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 98,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 1,
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
        'oxaddsum' => 84,
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
        'oxaddsum' => 72,
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
        'oxaddsum' => 43,
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
      'e79dde671471c76e3d64177ce081c' =>
      [
        0 => '1.031,51',
        1 => '442.517,79',
      ],
      '34dc2f21ffb5351e383614cdd77d5' =>
      [
        0 => '418,81',
        1 => '43.137,43',
      ],
      '8a837da3166436e64ca927731c6a3' =>
      [
        0 => '734,00',
        1 => '691.428,00',
      ],
      'f11a0e94e135194fdbb36f4f621a8' =>
      [
        0 => '978,27',
        1 => '182.936,49',
      ],
      'ac67e02d6c5429a48e2e1d70bd5c3' =>
      [
        0 => '691,17',
        1 => '398.113,92',
      ],
      'd45b758666d6ea3d9d416f6898aba' =>
      [
        0 => '668,58',
        1 => '2.674,32',
      ],
      '35867e60bf4ae0579b2c99ce108e3' =>
      [
        0 => '628,81',
        1 => '311.260,95',
      ],
      '0be520e084c2c6baf3501fb617416' =>
      [
        0 => '1.061,18',
        1 => '779.967,30',
      ],
      '85f2d7b72ee67d966897d80f65ffb' =>
      [
        0 => '622,16',
        1 => '280.594,16',
      ],
      'b44bc62bff67eede29285dc7d1c9e' =>
      [
        0 => '684,17',
        1 => '116.993,07',
      ],
      'd0b1b7075c1045802a156d111998d' =>
      [
        0 => '628,01',
        1 => '137.534,19',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        11 => '140.179,63',
        20 => '7.189,57',
        8 => '142.924,46',
      ],
      'wrapping' =>
      [
        'brutto' => '176.723,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '3.895.315,27',
        'netto' => '3.606.773,40',
        'vat' => '288.541,87',
      ],
      'payment' =>
      [
        'brutto' => '52,00',
        'netto' => '48,15',
        'vat' => '3,85',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '3.096.863,96',
      'totalBrutto' => '3.387.157,62',
      'grandTotal' => '7.459.247,89',
    ],
  ],
];
