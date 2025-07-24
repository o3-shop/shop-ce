<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_27',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '7a7979ab393235a72eec04e631638',
      'oxprice' => 78.24,
      'oxvat' => 25,
      'amount' => 473,
    ],
    1 =>
    [
      'oxid' => 'ef12cbcc510aeeef94d1ff07669d3',
      'oxprice' => 565.68,
      'oxvat' => 24,
      'amount' => 526,
    ],
    2 =>
    [
      'oxid' => '26643f3258795c679c06497c29ed6',
      'oxprice' => 714.29,
      'oxvat' => 25,
      'amount' => 422,
    ],
    3 =>
    [
      'oxid' => '6cfebd2de9e0f0274f0b6b9fe65b9',
      'oxprice' => 114.58,
      'oxvat' => 25,
      'amount' => 902,
    ],
    4 =>
    [
      'oxid' => '67e068efeb5f7aa99cb21c66ffd65',
      'oxprice' => 176.66,
      'oxvat' => 24,
      'amount' => 951,
    ],
    5 =>
    [
      'oxid' => 'a6f2b45b27aa060d6e7558846201c',
      'oxprice' => 630.39,
      'oxvat' => 25,
      'amount' => 84,
    ],
    6 =>
    [
      'oxid' => '5b8c398829753a2136f417302c06c',
      'oxprice' => 446.11,
      'oxvat' => 24,
      'amount' => 207,
    ],
    7 =>
    [
      'oxid' => '254795f3f17562dfb706ed96c43b6',
      'oxprice' => 34.43,
      'oxvat' => 25,
      'amount' => 814,
    ],
    8 =>
    [
      'oxid' => 'f13ab6f1009dfd99c41a2971e794f',
      'oxprice' => 2.33,
      'oxvat' => 24,
      'amount' => 531,
    ],
    9 =>
    [
      'oxid' => 'aab4b8cdae8ae8654f800fb2abbf0',
      'oxprice' => 556.28,
      'oxvat' => 24,
      'amount' => 737,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 1,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '7a7979ab393235a72eec04e631638',
          1 => 'ef12cbcc510aeeef94d1ff07669d3',
          2 => '26643f3258795c679c06497c29ed6',
          3 => '6cfebd2de9e0f0274f0b6b9fe65b9',
          4 => '67e068efeb5f7aa99cb21c66ffd65',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 91,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '7a7979ab393235a72eec04e631638',
          1 => 'ef12cbcc510aeeef94d1ff07669d3',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 3,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 25,
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
        'oxaddsum' => 21,
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
        'oxaddsum' => 21,
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
        'oxdiscount' => 13,
        'oxdiscounttype' => 'absolute',
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
      '7a7979ab393235a72eec04e631638' =>
      [
        0 => '97,80',
        1 => '46.259,40',
      ],
      'ef12cbcc510aeeef94d1ff07669d3' =>
      [
        0 => '701,44',
        1 => '368.957,44',
      ],
      '26643f3258795c679c06497c29ed6' =>
      [
        0 => '892,86',
        1 => '376.786,92',
      ],
      '6cfebd2de9e0f0274f0b6b9fe65b9' =>
      [
        0 => '143,23',
        1 => '129.193,46',
      ],
      '67e068efeb5f7aa99cb21c66ffd65' =>
      [
        0 => '219,06',
        1 => '208.326,06',
      ],
      'a6f2b45b27aa060d6e7558846201c' =>
      [
        0 => '787,99',
        1 => '66.191,16',
      ],
      '5b8c398829753a2136f417302c06c' =>
      [
        0 => '553,18',
        1 => '114.508,26',
      ],
      '254795f3f17562dfb706ed96c43b6' =>
      [
        0 => '43,04',
        1 => '35.034,56',
      ],
      'f13ab6f1009dfd99c41a2971e794f' =>
      [
        0 => '2,89',
        1 => '1.534,59',
      ],
      'aab4b8cdae8ae8654f800fb2abbf0' =>
      [
        0 => '689,79',
        1 => '508.375,23',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        25 => '130.691,27',
        24 => '232.584,14',
      ],
      'wrapping' =>
      [
        'brutto' => '93.184,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '42,00',
        'netto' => '33,87',
        'vat' => '8,13',
      ],
      'payment' =>
      [
        'brutto' => '55.655,49',
        'netto' => '44.883,46',
        'vat' => '10.772,03',
      ],
      'voucher' =>
      [
        'brutto' => '26,00',
      ],
      'totalNetto' => '1.491.865,67',
      'totalBrutto' => '1.855.167,08',
      'grandTotal' => '2.004.022,57',
    ],
  ],
];
