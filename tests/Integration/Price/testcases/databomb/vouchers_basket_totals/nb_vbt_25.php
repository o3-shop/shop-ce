<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_25',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'da342c8bd5e4a092056d9d1dd1846',
      'oxprice' => 409.09,
      'oxvat' => 1,
      'amount' => 945,
    ],
    1 =>
    [
      'oxid' => 'c39d83d3b9ec101e0f686e0537c30',
      'oxprice' => 56.31,
      'oxvat' => 1,
      'amount' => 364,
    ],
    2 =>
    [
      'oxid' => 'c69ee0210811d27628d2ef9f179a1',
      'oxprice' => 896.52,
      'oxvat' => 1,
      'amount' => 514,
    ],
    3 =>
    [
      'oxid' => '757ab7c5a5c8e85004604fb4e0813',
      'oxprice' => 160.72,
      'oxvat' => 0,
      'amount' => 699,
    ],
    4 =>
    [
      'oxid' => 'df62867a1d8e1132a4f681dc74e82',
      'oxprice' => 715.23,
      'oxvat' => 0,
      'amount' => 152,
    ],
    5 =>
    [
      'oxid' => 'e1a3d878aba8252c7f139c862b41e',
      'oxprice' => 922.85,
      'oxvat' => 1,
      'amount' => 441,
    ],
    6 =>
    [
      'oxid' => '5d4211178ec925231bc3b7687563e',
      'oxprice' => 139.21,
      'oxvat' => 0,
      'amount' => 932,
    ],
    7 =>
    [
      'oxid' => '7cf0737f57d26690ce3680182fb7e',
      'oxprice' => 414.03,
      'oxvat' => 0,
      'amount' => 397,
    ],
    8 =>
    [
      'oxid' => '8e98bf325325168a99f2830b3c207',
      'oxprice' => 306.39,
      'oxvat' => 0,
      'amount' => 540,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 67,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'da342c8bd5e4a092056d9d1dd1846',
          1 => 'c39d83d3b9ec101e0f686e0537c30',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 14,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'da342c8bd5e4a092056d9d1dd1846',
          1 => 'c39d83d3b9ec101e0f686e0537c30',
          2 => 'c69ee0210811d27628d2ef9f179a1',
          3 => '757ab7c5a5c8e85004604fb4e0813',
          4 => 'df62867a1d8e1132a4f681dc74e82',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 27,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 15,
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
        'oxaddsum' => 7,
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
        'oxdiscount' => 20,
        'oxdiscounttype' => 'percent',
        'oxallowsameseries' => 1,
        'oxallowotherseries' => 1,
        'oxallowuseanother' => 1,
        'voucher_count' => 2,
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
      'da342c8bd5e4a092056d9d1dd1846' =>
      [
        0 => '413,18',
        1 => '390.455,10',
      ],
      'c39d83d3b9ec101e0f686e0537c30' =>
      [
        0 => '56,87',
        1 => '20.700,68',
      ],
      'c69ee0210811d27628d2ef9f179a1' =>
      [
        0 => '905,49',
        1 => '465.421,86',
      ],
      '757ab7c5a5c8e85004604fb4e0813' =>
      [
        0 => '160,72',
        1 => '112.343,28',
      ],
      'df62867a1d8e1132a4f681dc74e82' =>
      [
        0 => '715,23',
        1 => '108.714,96',
      ],
      'e1a3d878aba8252c7f139c862b41e' =>
      [
        0 => '932,08',
        1 => '411.047,28',
      ],
      '5d4211178ec925231bc3b7687563e' =>
      [
        0 => '139,21',
        1 => '129.743,72',
      ],
      '7cf0737f57d26690ce3680182fb7e' =>
      [
        0 => '414,03',
        1 => '164.369,91',
      ],
      '8e98bf325325168a99f2830b3c207' =>
      [
        0 => '306,39',
        1 => '165.450,60',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        1 => '8.159,21',
        0 => '0,00',
      ],
      'wrapping' =>
      [
        'brutto' => '37.436,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '137.792,32',
        'netto' => '136.428,04',
        'vat' => '1.364,28',
      ],
      'payment' =>
      [
        'brutto' => '27,00',
        'netto' => '26,73',
        'vat' => '0,27',
      ],
      'voucher' =>
      [
        'brutto' => '708.569,06',
      ],
      'totalNetto' => '1.251.519,12',
      'totalBrutto' => '1.968.247,39',
      'grandTotal' => '1.434.933,65',
    ],
  ],
];
