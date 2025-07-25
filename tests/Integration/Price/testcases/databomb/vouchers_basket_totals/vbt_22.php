<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_22',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '45d73f3ba15817200a7d09673eddf',
      'oxprice' => 448.35,
      'oxvat' => 36,
      'amount' => 165,
    ],
    1 =>
    [
      'oxid' => 'a70533566a7115a8b9d318467b85c',
      'oxprice' => 807.37,
      'oxvat' => 36,
      'amount' => 850,
    ],
    2 =>
    [
      'oxid' => '3941da8092d419c66c44647e39083',
      'oxprice' => 330.8,
      'oxvat' => 36,
      'amount' => 688,
    ],
    3 =>
    [
      'oxid' => '5f8bf975e67e5152ffaa47f158efd',
      'oxprice' => 183.61,
      'oxvat' => 36,
      'amount' => 295,
    ],
    4 =>
    [
      'oxid' => '01b7f134674475bc00dfa9e8a9bd5',
      'oxprice' => 734.58,
      'oxvat' => 36,
      'amount' => 813,
    ],
    5 =>
    [
      'oxid' => '251ca77d2c04d681508fcf15b5b8e',
      'oxprice' => 21.27,
      'oxvat' => 36,
      'amount' => 796,
    ],
    6 =>
    [
      'oxid' => 'be35a2793fd523e917ed3c266f6fd',
      'oxprice' => 8.73,
      'oxvat' => 36,
      'amount' => 712,
    ],
    7 =>
    [
      'oxid' => '6aecdac0fe71f97e1727ad06205d7',
      'oxprice' => 25.69,
      'oxvat' => 36,
      'amount' => 766,
    ],
    8 =>
    [
      'oxid' => 'e979e7033947a4baf9dbbb9d8e14b',
      'oxprice' => 623.39,
      'oxvat' => 36,
      'amount' => 17,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 98,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '45d73f3ba15817200a7d09673eddf',
          1 => 'a70533566a7115a8b9d318467b85c',
          2 => '3941da8092d419c66c44647e39083',
          3 => '5f8bf975e67e5152ffaa47f158efd',
          4 => '01b7f134674475bc00dfa9e8a9bd5',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 34,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '45d73f3ba15817200a7d09673eddf',
          1 => 'a70533566a7115a8b9d318467b85c',
          2 => '3941da8092d419c66c44647e39083',
          3 => '5f8bf975e67e5152ffaa47f158efd',
          4 => '01b7f134674475bc00dfa9e8a9bd5',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 39,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 94,
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
        'oxaddsum' => 88,
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
        'oxaddsum' => 57,
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
        'oxdiscount' => 31,
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
      '45d73f3ba15817200a7d09673eddf' =>
      [
        0 => '448,35',
        1 => '73.977,75',
      ],
      'a70533566a7115a8b9d318467b85c' =>
      [
        0 => '807,37',
        1 => '686.264,50',
      ],
      '3941da8092d419c66c44647e39083' =>
      [
        0 => '330,80',
        1 => '227.590,40',
      ],
      '5f8bf975e67e5152ffaa47f158efd' =>
      [
        0 => '183,61',
        1 => '54.164,95',
      ],
      '01b7f134674475bc00dfa9e8a9bd5' =>
      [
        0 => '734,58',
        1 => '597.213,54',
      ],
      '251ca77d2c04d681508fcf15b5b8e' =>
      [
        0 => '21,27',
        1 => '16.930,92',
      ],
      'be35a2793fd523e917ed3c266f6fd' =>
      [
        0 => '8,73',
        1 => '6.215,76',
      ],
      '6aecdac0fe71f97e1727ad06205d7' =>
      [
        0 => '25,69',
        1 => '19.678,54',
      ],
      'e979e7033947a4baf9dbbb9d8e14b' =>
      [
        0 => '623,39',
        1 => '10.597,63',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        36 => '448.025,56',
      ],
      'wrapping' =>
      [
        'brutto' => '95.574,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '964.889,37',
        'netto' => '709.477,48',
        'vat' => '255.411,89',
      ],
      'payment' =>
      [
        'brutto' => '1.036.397,84',
        'netto' => '762.057,24',
        'vat' => '274.340,60',
      ],
      'voucher' =>
      [
        'brutto' => '93,00',
      ],
      'totalNetto' => '1.244.515,43',
      'totalBrutto' => '1.692.633,99',
      'grandTotal' => '3.789.402,20',
    ],
  ],
];
