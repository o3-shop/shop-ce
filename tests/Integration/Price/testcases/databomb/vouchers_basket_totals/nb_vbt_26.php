<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_26',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '308dbca1825fd6c195c4ccd8d014c',
      'oxprice' => 735.48,
      'oxvat' => 31,
      'amount' => 17,
    ],
    1 =>
    [
      'oxid' => 'eac5275f74facf9130551c1ec769c',
      'oxprice' => 767.8,
      'oxvat' => 31,
      'amount' => 862,
    ],
    2 =>
    [
      'oxid' => 'c3061f7157211bced75af6f82389b',
      'oxprice' => 131.91,
      'oxvat' => 31,
      'amount' => 331,
    ],
    3 =>
    [
      'oxid' => '2c361f921e2fbfe96a0fe6e8afaff',
      'oxprice' => 717.16,
      'oxvat' => 31,
      'amount' => 614,
    ],
    4 =>
    [
      'oxid' => 'ab5a7d0877b7a34c710e8789a8467',
      'oxprice' => 593.87,
      'oxvat' => 31,
      'amount' => 406,
    ],
    5 =>
    [
      'oxid' => '5049e399775ee5749f19b92e0059b',
      'oxprice' => 952.45,
      'oxvat' => 31,
      'amount' => 621,
    ],
    6 =>
    [
      'oxid' => '12791a147d1d7c17e7c6415f5c2eb',
      'oxprice' => 56.96,
      'oxvat' => 31,
      'amount' => 593,
    ],
    7 =>
    [
      'oxid' => '4ac4985b895ea56fada89e45e02d4',
      'oxprice' => 840.5,
      'oxvat' => 31,
      'amount' => 763,
    ],
    8 =>
    [
      'oxid' => 'a82457a463a23409dc522c3236f3e',
      'oxprice' => 861.92,
      'oxvat' => 31,
      'amount' => 393,
    ],
    9 =>
    [
      'oxid' => '4e1f5b6ae2e34d987ff2124da8179',
      'oxprice' => 181.1,
      'oxvat' => 31,
      'amount' => 538,
    ],
    10 =>
    [
      'oxid' => '8965c1d5934a719cb5992bc3560ba',
      'oxprice' => 870.99,
      'oxvat' => 31,
      'amount' => 942,
    ],
    11 =>
    [
      'oxid' => '01ac78013211e93d9ca33cea0a165',
      'oxprice' => 12.19,
      'oxvat' => 31,
      'amount' => 666,
    ],
    12 =>
    [
      'oxid' => '3e6b7326a7008f088be287c197663',
      'oxprice' => 193.29,
      'oxvat' => 31,
      'amount' => 337,
    ],
    13 =>
    [
      'oxid' => 'c77140f4c74f5d04330e0bcf9af0f',
      'oxprice' => 907.36,
      'oxvat' => 31,
      'amount' => 447,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 88,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '308dbca1825fd6c195c4ccd8d014c',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 42,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '308dbca1825fd6c195c4ccd8d014c',
          1 => 'eac5275f74facf9130551c1ec769c',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 14,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 33,
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
        'oxaddsum' => 4,
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
        'oxdiscount' => 18,
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
      '308dbca1825fd6c195c4ccd8d014c' =>
      [
        0 => '963,48',
        1 => '16.379,16',
      ],
      'eac5275f74facf9130551c1ec769c' =>
      [
        0 => '1.005,82',
        1 => '867.016,84',
      ],
      'c3061f7157211bced75af6f82389b' =>
      [
        0 => '172,80',
        1 => '57.196,80',
      ],
      '2c361f921e2fbfe96a0fe6e8afaff' =>
      [
        0 => '939,48',
        1 => '576.840,72',
      ],
      'ab5a7d0877b7a34c710e8789a8467' =>
      [
        0 => '777,97',
        1 => '315.855,82',
      ],
      '5049e399775ee5749f19b92e0059b' =>
      [
        0 => '1.247,71',
        1 => '774.827,91',
      ],
      '12791a147d1d7c17e7c6415f5c2eb' =>
      [
        0 => '74,62',
        1 => '44.249,66',
      ],
      '4ac4985b895ea56fada89e45e02d4' =>
      [
        0 => '1.101,06',
        1 => '840.108,78',
      ],
      'a82457a463a23409dc522c3236f3e' =>
      [
        0 => '1.129,12',
        1 => '443.744,16',
      ],
      '4e1f5b6ae2e34d987ff2124da8179' =>
      [
        0 => '237,24',
        1 => '127.635,12',
      ],
      '8965c1d5934a719cb5992bc3560ba' =>
      [
        0 => '1.141,00',
        1 => '1.074.822,00',
      ],
      '01ac78013211e93d9ca33cea0a165' =>
      [
        0 => '15,97',
        1 => '10.636,02',
      ],
      '3e6b7326a7008f088be287c197663' =>
      [
        0 => '253,21',
        1 => '85.331,77',
      ],
      'c77140f4c74f5d04330e0bcf9af0f' =>
      [
        0 => '1.188,64',
        1 => '531.322,08',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        31 => '1.364.456,92',
      ],
      'wrapping' =>
      [
        'brutto' => '36.918,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.383.836,04',
        'netto' => '1.056.363,39',
        'vat' => '327.472,65',
      ],
      'payment' =>
      [
        'brutto' => '14,00',
        'netto' => '10,69',
        'vat' => '3,31',
      ],
      'voucher' =>
      [
        'brutto' => '36,00',
      ],
      'totalNetto' => '4.401.473,92',
      'totalBrutto' => '5.765.966,84',
      'grandTotal' => '7.186.698,88',
    ],
  ],
];
