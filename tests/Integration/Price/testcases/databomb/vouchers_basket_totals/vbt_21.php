<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_21',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'd01835900486c7c77d18fb87939ef',
      'oxprice' => 572.17,
      'oxvat' => 19,
      'amount' => 442,
    ],
    1 =>
    [
      'oxid' => 'ff826dbb8fb739a992d12501b03d2',
      'oxprice' => 544.79,
      'oxvat' => 43,
      'amount' => 479,
    ],
    2 =>
    [
      'oxid' => 'edfdd92d2e98da070ec4b956ebb3c',
      'oxprice' => 593.32,
      'oxvat' => 35,
      'amount' => 593,
    ],
    3 =>
    [
      'oxid' => 'bb482c61229a45dda0080bd2caf6c',
      'oxprice' => 552.89,
      'oxvat' => 19,
      'amount' => 585,
    ],
    4 =>
    [
      'oxid' => 'bea8833481d4c36b92bcce1d4443c',
      'oxprice' => 66.68,
      'oxvat' => 3,
      'amount' => 558,
    ],
    5 =>
    [
      'oxid' => '453aa28f6c0cfa55e976ab4af512b',
      'oxprice' => 416.01,
      'oxvat' => 43,
      'amount' => 642,
    ],
    6 =>
    [
      'oxid' => '97ab1186091358a6b1e547b62afc5',
      'oxprice' => 806.74,
      'oxvat' => 35,
      'amount' => 100,
    ],
    7 =>
    [
      'oxid' => '36f1cb886ece444bf528f7a671e31',
      'oxprice' => 891.22,
      'oxvat' => 35,
      'amount' => 465,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 30,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'd01835900486c7c77d18fb87939ef',
          1 => 'ff826dbb8fb739a992d12501b03d2',
          2 => 'edfdd92d2e98da070ec4b956ebb3c',
          3 => 'bb482c61229a45dda0080bd2caf6c',
          4 => 'bea8833481d4c36b92bcce1d4443c',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 45,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'd01835900486c7c77d18fb87939ef',
          1 => 'ff826dbb8fb739a992d12501b03d2',
          2 => 'edfdd92d2e98da070ec4b956ebb3c',
          3 => 'bb482c61229a45dda0080bd2caf6c',
          4 => 'bea8833481d4c36b92bcce1d4443c',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 2,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 61,
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
        'oxaddsum' => 20,
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
        'oxdiscount' => 25,
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
      'd01835900486c7c77d18fb87939ef' =>
      [
        0 => '572,17',
        1 => '252.899,14',
      ],
      'ff826dbb8fb739a992d12501b03d2' =>
      [
        0 => '544,79',
        1 => '260.954,41',
      ],
      'edfdd92d2e98da070ec4b956ebb3c' =>
      [
        0 => '593,32',
        1 => '351.838,76',
      ],
      'bb482c61229a45dda0080bd2caf6c' =>
      [
        0 => '552,89',
        1 => '323.440,65',
      ],
      'bea8833481d4c36b92bcce1d4443c' =>
      [
        0 => '66,68',
        1 => '37.207,44',
      ],
      '453aa28f6c0cfa55e976ab4af512b' =>
      [
        0 => '416,01',
        1 => '267.078,42',
      ],
      '97ab1186091358a6b1e547b62afc5' =>
      [
        0 => '806,74',
        1 => '80.674,00',
      ],
      '36f1cb886ece444bf528f7a671e31' =>
      [
        0 => '891,22',
        1 => '414.417,30',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        19 => '92.017,17',
        43 => '158.773,11',
        35 => '219.566,18',
        3 => '1.083,67',
      ],
      'wrapping' =>
      [
        'brutto' => '119.565,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '2.346.441,94',
        'netto' => '1.738.105,14',
        'vat' => '608.336,80',
      ],
      'payment' =>
      [
        'brutto' => '86.697,54',
        'netto' => '64.220,40',
        'vat' => '22.477,14',
      ],
      'voucher' =>
      [
        'brutto' => '75,00',
      ],
      'totalNetto' => '1.516.994,99',
      'totalBrutto' => '1.988.510,12',
      'grandTotal' => '4.541.139,60',
    ],
  ],
];
