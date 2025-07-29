<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_6',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '72449dab2c94aee834c906ab1d3c6',
      'oxprice' => 13.69,
      'oxvat' => 23,
      'amount' => 986,
    ],
    1 =>
    [
      'oxid' => 'be07a35283b1b92f28785ac4c7688',
      'oxprice' => 421.98,
      'oxvat' => 31,
      'amount' => 173,
    ],
    2 =>
    [
      'oxid' => '3dfe36d71a41aeceb9ed76fb938c9',
      'oxprice' => 251.31,
      'oxvat' => 4,
      'amount' => 70,
    ],
    3 =>
    [
      'oxid' => '35c80b305c98769eb78d508c575d2',
      'oxprice' => 91.54,
      'oxvat' => 31,
      'amount' => 14,
    ],
    4 =>
    [
      'oxid' => 'db273b0734323bad69c4e2ecaf6bb',
      'oxprice' => 617.9,
      'oxvat' => 31,
      'amount' => 938,
    ],
    5 =>
    [
      'oxid' => '433640dbde37fc3f69cbaa4e7b06c',
      'oxprice' => 514.72,
      'oxvat' => 31,
      'amount' => 877,
    ],
    6 =>
    [
      'oxid' => '870451c1ab0ca6d1ed2a9d0d2ab31',
      'oxprice' => 772.57,
      'oxvat' => 4,
      'amount' => 815,
    ],
    7 =>
    [
      'oxid' => '5fe2b68f21cb25531f3fadda2b0b3',
      'oxprice' => 755.74,
      'oxvat' => 31,
      'amount' => 164,
    ],
    8 =>
    [
      'oxid' => 'e69d4f044814c705f87df1c20c8c7',
      'oxprice' => 7.69,
      'oxvat' => 4,
      'amount' => 701,
    ],
    9 =>
    [
      'oxid' => 'bc1a41601549c3176c4ab0efa9020',
      'oxprice' => 127.86,
      'oxvat' => 4,
      'amount' => 286,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 42,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '72449dab2c94aee834c906ab1d3c6',
          1 => 'be07a35283b1b92f28785ac4c7688',
          2 => '3dfe36d71a41aeceb9ed76fb938c9',
          3 => '35c80b305c98769eb78d508c575d2',
          4 => 'db273b0734323bad69c4e2ecaf6bb',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 59,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '72449dab2c94aee834c906ab1d3c6',
          1 => 'be07a35283b1b92f28785ac4c7688',
          2 => '3dfe36d71a41aeceb9ed76fb938c9',
          3 => '35c80b305c98769eb78d508c575d2',
          4 => 'db273b0734323bad69c4e2ecaf6bb',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 1,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '72449dab2c94aee834c906ab1d3c6',
          1 => 'be07a35283b1b92f28785ac4c7688',
          2 => '3dfe36d71a41aeceb9ed76fb938c9',
          3 => '35c80b305c98769eb78d508c575d2',
          4 => 'db273b0734323bad69c4e2ecaf6bb',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 34,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 62,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 79,
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
        'oxaddsum' => 18,
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
        'oxaddsum' => 81,
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
        'oxaddsum' => 50,
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
      '72449dab2c94aee834c906ab1d3c6' =>
      [
        0 => '16,84',
        1 => '16.604,24',
      ],
      'be07a35283b1b92f28785ac4c7688' =>
      [
        0 => '552,79',
        1 => '95.632,67',
      ],
      '3dfe36d71a41aeceb9ed76fb938c9' =>
      [
        0 => '261,36',
        1 => '18.295,20',
      ],
      '35c80b305c98769eb78d508c575d2' =>
      [
        0 => '119,92',
        1 => '1.678,88',
      ],
      'db273b0734323bad69c4e2ecaf6bb' =>
      [
        0 => '809,45',
        1 => '759.264,10',
      ],
      '433640dbde37fc3f69cbaa4e7b06c' =>
      [
        0 => '674,28',
        1 => '591.343,56',
      ],
      '870451c1ab0ca6d1ed2a9d0d2ab31' =>
      [
        0 => '803,47',
        1 => '654.828,05',
      ],
      '5fe2b68f21cb25531f3fadda2b0b3' =>
      [
        0 => '990,02',
        1 => '162.363,28',
      ],
      'e69d4f044814c705f87df1c20c8c7' =>
      [
        0 => '8,00',
        1 => '5.608,00',
      ],
      'bc1a41601549c3176c4ab0efa9020' =>
      [
        0 => '132,97',
        1 => '38.029,42',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        23 => '3.104,86',
        31 => '381.059,22',
        4 => '27.567,72',
      ],
      'wrapping' =>
      [
        'brutto' => '2.181,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.593.761,23',
        'netto' => '1.216.611,63',
        'vat' => '377.149,60',
      ],
      'payment' =>
      [
        'brutto' => '34,00',
        'netto' => '25,95',
        'vat' => '8,05',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.931.915,60',
      'totalBrutto' => '2.343.647,40',
      'grandTotal' => '3.939.623,63',
    ],
  ],
];
