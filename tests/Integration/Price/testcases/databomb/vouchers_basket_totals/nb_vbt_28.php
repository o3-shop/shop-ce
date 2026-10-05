<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_28',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'd722dc963f371956d634df7a285be',
      'oxprice' => 77.12,
      'oxvat' => 30,
      'amount' => 663,
    ],
    1 =>
    [
      'oxid' => 'c3025e24e018f5f72c44131596d08',
      'oxprice' => 950.17,
      'oxvat' => 30,
      'amount' => 33,
    ],
    2 =>
    [
      'oxid' => '84a3b056d27bdbc1dc5238d4c090e',
      'oxprice' => 457.27,
      'oxvat' => 30,
      'amount' => 277,
    ],
    3 =>
    [
      'oxid' => '1a7a05ccfad455a896b59410644ff',
      'oxprice' => 898.79,
      'oxvat' => 30,
      'amount' => 783,
    ],
    4 =>
    [
      'oxid' => 'e9ab9afaa1bf9407cfa6518b4dbed',
      'oxprice' => 340.91,
      'oxvat' => 30,
      'amount' => 115,
    ],
    5 =>
    [
      'oxid' => '86580393f19c45977704d3fb4963e',
      'oxprice' => 811.48,
      'oxvat' => 30,
      'amount' => 603,
    ],
    6 =>
    [
      'oxid' => 'da77613d0c7a54869f5d1e8e2760b',
      'oxprice' => 299.76,
      'oxvat' => 30,
      'amount' => 83,
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
          0 => 'd722dc963f371956d634df7a285be',
          1 => 'c3025e24e018f5f72c44131596d08',
          2 => '84a3b056d27bdbc1dc5238d4c090e',
          3 => '1a7a05ccfad455a896b59410644ff',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 92,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'd722dc963f371956d634df7a285be',
          1 => 'c3025e24e018f5f72c44131596d08',
          2 => '84a3b056d27bdbc1dc5238d4c090e',
          3 => '1a7a05ccfad455a896b59410644ff',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 26,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 5,
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
        'oxdiscount' => 30,
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
      'd722dc963f371956d634df7a285be' =>
      [
        0 => '100,26',
        1 => '66.472,38',
      ],
      'c3025e24e018f5f72c44131596d08' =>
      [
        0 => '1.235,22',
        1 => '40.762,26',
      ],
      '84a3b056d27bdbc1dc5238d4c090e' =>
      [
        0 => '594,45',
        1 => '164.662,65',
      ],
      '1a7a05ccfad455a896b59410644ff' =>
      [
        0 => '1.168,43',
        1 => '914.880,69',
      ],
      'e9ab9afaa1bf9407cfa6518b4dbed' =>
      [
        0 => '443,18',
        1 => '50.965,70',
      ],
      '86580393f19c45977704d3fb4963e' =>
      [
        0 => '1.054,92',
        1 => '636.116,76',
      ],
      'da77613d0c7a54869f5d1e8e2760b' =>
      [
        0 => '389,69',
        1 => '32.344,27',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        30 => '215.547,76',
      ],
      'wrapping' =>
      [
        'brutto' => '161.552,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '76.263,19',
        'netto' => '58.663,99',
        'vat' => '17.599,20',
      ],
      'payment' =>
      [
        'brutto' => '262.678,91',
        'netto' => '202.060,70',
        'vat' => '60.618,21',
      ],
      'voucher' =>
      [
        'brutto' => '972.164,40',
      ],
      'totalNetto' => '718.492,55',
      'totalBrutto' => '1.906.204,71',
      'grandTotal' => '1.434.534,41',
    ],
  ],
];
