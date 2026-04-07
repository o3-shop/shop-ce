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
      'oxid' => 'eda4d43ae9a656fc241f02e062b71',
      'oxprice' => 209.31,
      'oxvat' => 23,
      'amount' => 188,
    ],
    1 =>
    [
      'oxid' => '990fd541778181e0d8f7a1532e906',
      'oxprice' => 964.36,
      'oxvat' => 31,
      'amount' => 203,
    ],
    2 =>
    [
      'oxid' => 'cecf9b82589b143feb877868c5371',
      'oxprice' => 157.59,
      'oxvat' => 4,
      'amount' => 830,
    ],
    3 =>
    [
      'oxid' => 'e747456a281769502bce8e8e9ab44',
      'oxprice' => 792.66,
      'oxvat' => 23,
      'amount' => 688,
    ],
    4 =>
    [
      'oxid' => '77c081721d7e0eccb141994fe44d4',
      'oxprice' => 140.52,
      'oxvat' => 23,
      'amount' => 352,
    ],
    5 =>
    [
      'oxid' => '670c676abee7d2a236ec0d82ae77c',
      'oxprice' => 147.77,
      'oxvat' => 23,
      'amount' => 323,
    ],
    6 =>
    [
      'oxid' => '02e42c7ca54282374083c008ee27a',
      'oxprice' => 418.91,
      'oxvat' => 31,
      'amount' => 726,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 71,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'eda4d43ae9a656fc241f02e062b71',
          1 => '990fd541778181e0d8f7a1532e906',
          2 => 'cecf9b82589b143feb877868c5371',
          3 => 'e747456a281769502bce8e8e9ab44',
          4 => '77c081721d7e0eccb141994fe44d4',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 69,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'eda4d43ae9a656fc241f02e062b71',
          1 => '990fd541778181e0d8f7a1532e906',
          2 => 'cecf9b82589b143feb877868c5371',
          3 => 'e747456a281769502bce8e8e9ab44',
          4 => '77c081721d7e0eccb141994fe44d4',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 82,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 56,
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
        'oxaddsum' => 24,
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
        'oxaddsum' => 98,
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
      'eda4d43ae9a656fc241f02e062b71' =>
      [
        0 => '209,31',
        1 => '39.350,28',
      ],
      '990fd541778181e0d8f7a1532e906' =>
      [
        0 => '964,36',
        1 => '195.765,08',
      ],
      'cecf9b82589b143feb877868c5371' =>
      [
        0 => '157,59',
        1 => '130.799,70',
      ],
      'e747456a281769502bce8e8e9ab44' =>
      [
        0 => '792,66',
        1 => '545.350,08',
      ],
      '77c081721d7e0eccb141994fe44d4' =>
      [
        0 => '140,52',
        1 => '49.463,04',
      ],
      '670c676abee7d2a236ec0d82ae77c' =>
      [
        0 => '147,77',
        1 => '47.729,71',
      ],
      '02e42c7ca54282374083c008ee27a' =>
      [
        0 => '418,91',
        1 => '304.128,66',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        23 => '127.498,85',
        31 => '118.286,54',
        4 => '5.030,38',
      ],
      'wrapping' =>
      [
        'brutto' => '156.009,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '315.118,77',
        'netto' => '256.194,12',
        'vat' => '58.924,65',
      ],
      'payment' =>
      [
        'brutto' => '1.334.637,18',
        'netto' => '1.085.070,88',
        'vat' => '249.566,30',
      ],
      'voucher' =>
      [
        'brutto' => '99,00',
      ],
      'totalNetto' => '1.061.671,78',
      'totalBrutto' => '1.312.586,55',
      'grandTotal' => '3.118.252,50',
    ],
  ],
];
