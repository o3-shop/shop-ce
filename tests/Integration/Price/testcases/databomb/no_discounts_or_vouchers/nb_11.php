<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_11',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '4fd047d48fa7cc2d301a5503b99a0',
      'oxprice' => 825.54,
      'oxvat' => 0,
      'amount' => 622,
    ],
    1 =>
    [
      'oxid' => 'ceccaa2703a2ca467fbc5e4bffe8e',
      'oxprice' => 197.21,
      'oxvat' => 30,
      'amount' => 981,
    ],
    2 =>
    [
      'oxid' => 'e83b485583e37622db972d7998d0c',
      'oxprice' => 584.02,
      'oxvat' => 17,
      'amount' => 121,
    ],
    3 =>
    [
      'oxid' => '448b40097e98dec36277b47c12109',
      'oxprice' => 364.66,
      'oxvat' => 30,
      'amount' => 653,
    ],
    4 =>
    [
      'oxid' => '90bd4e0a3ee4e0faa75f802f99380',
      'oxprice' => 377.9,
      'oxvat' => 0,
      'amount' => 425,
    ],
    5 =>
    [
      'oxid' => 'c51e696a727d24035333f18b0e8e8',
      'oxprice' => 227.98,
      'oxvat' => 30,
      'amount' => 518,
    ],
    6 =>
    [
      'oxid' => 'f1e4ab5b03da7f6e68af7922b9ba6',
      'oxprice' => 561.99,
      'oxvat' => 30,
      'amount' => 905,
    ],
    7 =>
    [
      'oxid' => 'be8948a4526bdfc0c87de3974e554',
      'oxprice' => 43.79,
      'oxvat' => 0,
      'amount' => 965,
    ],
    8 =>
    [
      'oxid' => 'ce10e0c925193366b85093eb92c96',
      'oxprice' => 549.36,
      'oxvat' => 30,
      'amount' => 40,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 21,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '4fd047d48fa7cc2d301a5503b99a0',
          1 => 'ceccaa2703a2ca467fbc5e4bffe8e',
          2 => 'e83b485583e37622db972d7998d0c',
          3 => '448b40097e98dec36277b47c12109',
          4 => '90bd4e0a3ee4e0faa75f802f99380',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 77,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '4fd047d48fa7cc2d301a5503b99a0',
          1 => 'ceccaa2703a2ca467fbc5e4bffe8e',
          2 => 'e83b485583e37622db972d7998d0c',
          3 => '448b40097e98dec36277b47c12109',
          4 => '90bd4e0a3ee4e0faa75f802f99380',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 6,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '4fd047d48fa7cc2d301a5503b99a0',
          1 => 'ceccaa2703a2ca467fbc5e4bffe8e',
          2 => 'e83b485583e37622db972d7998d0c',
          3 => '448b40097e98dec36277b47c12109',
          4 => '90bd4e0a3ee4e0faa75f802f99380',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 28,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 94,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 87,
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
        'oxaddsum' => 46,
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
        'oxaddsum' => 76,
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
        'oxaddsum' => 49,
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
      '4fd047d48fa7cc2d301a5503b99a0' =>
      [
        0 => '825,54',
        1 => '513.485,88',
      ],
      'ceccaa2703a2ca467fbc5e4bffe8e' =>
      [
        0 => '256,37',
        1 => '251.498,97',
      ],
      'e83b485583e37622db972d7998d0c' =>
      [
        0 => '683,30',
        1 => '82.679,30',
      ],
      '448b40097e98dec36277b47c12109' =>
      [
        0 => '474,06',
        1 => '309.561,18',
      ],
      '90bd4e0a3ee4e0faa75f802f99380' =>
      [
        0 => '377,90',
        1 => '160.607,50',
      ],
      'c51e696a727d24035333f18b0e8e8' =>
      [
        0 => '296,37',
        1 => '153.519,66',
      ],
      'f1e4ab5b03da7f6e68af7922b9ba6' =>
      [
        0 => '730,59',
        1 => '661.183,95',
      ],
      'be8948a4526bdfc0c87de3974e554' =>
      [
        0 => '43,79',
        1 => '42.257,35',
      ],
      'ce10e0c925193366b85093eb92c96' =>
      [
        0 => '714,17',
        1 => '28.566,80',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        0 => '0,00',
        30 => '324.076,28',
        17 => '12.013,23',
      ],
      'wrapping' =>
      [
        'brutto' => '16.812,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.674.649,05',
        'netto' => '1.288.191,58',
        'vat' => '386.457,47',
      ],
      'payment' =>
      [
        'brutto' => '1.085.842,70',
        'netto' => '835.263,62',
        'vat' => '250.579,08',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.867.271,08',
      'totalBrutto' => '2.203.360,59',
      'grandTotal' => '4.980.664,34',
    ],
  ],
];
