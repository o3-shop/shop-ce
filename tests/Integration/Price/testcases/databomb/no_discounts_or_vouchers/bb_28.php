<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_28',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '99dec338f61c74afd5ae6f239b2a0',
      'oxprice' => 157.75,
      'oxvat' => 31,
      'amount' => 529,
    ],
    1 =>
    [
      'oxid' => 'dc62cd53e55e5ebf2e75fc08ee68a',
      'oxprice' => 853.1,
      'oxvat' => 22,
      'amount' => 359,
    ],
    2 =>
    [
      'oxid' => '793753fb050623cc6ba3e3a16b360',
      'oxprice' => 769,
      'oxvat' => 31,
      'amount' => 832,
    ],
    3 =>
    [
      'oxid' => '4b0d40d33d714df6314441433061b',
      'oxprice' => 146.47,
      'oxvat' => 31,
      'amount' => 402,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 46,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '99dec338f61c74afd5ae6f239b2a0',
          1 => 'dc62cd53e55e5ebf2e75fc08ee68a',
          2 => '793753fb050623cc6ba3e3a16b360',
          3 => '4b0d40d33d714df6314441433061b',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 10,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '99dec338f61c74afd5ae6f239b2a0',
          1 => 'dc62cd53e55e5ebf2e75fc08ee68a',
          2 => '793753fb050623cc6ba3e3a16b360',
          3 => '4b0d40d33d714df6314441433061b',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 97,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '99dec338f61c74afd5ae6f239b2a0',
          1 => 'dc62cd53e55e5ebf2e75fc08ee68a',
          2 => '793753fb050623cc6ba3e3a16b360',
          3 => '4b0d40d33d714df6314441433061b',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 85,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 21,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 55,
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
        'oxaddsum' => 80,
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
        'oxaddsum' => 65,
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
        'oxaddsum' => 93,
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
      'blEnterNetPrice' => false,
      'blShowNetPrice' => false,
    ],
    'activeCurrencyRate' => 1,
  ],
  'expected' =>
  [
    'articles' =>
    [
      '99dec338f61c74afd5ae6f239b2a0' =>
      [
        0 => '157,75',
        1 => '83.449,75',
      ],
      'dc62cd53e55e5ebf2e75fc08ee68a' =>
      [
        0 => '853,10',
        1 => '306.262,90',
      ],
      '793753fb050623cc6ba3e3a16b360' =>
      [
        0 => '769,00',
        1 => '639.808,00',
      ],
      '4b0d40d33d714df6314441433061b' =>
      [
        0 => '146,47',
        1 => '58.880,94',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        31 => '185.086,25',
        22 => '55.227,74',
      ],
      'wrapping' =>
      [
        'brutto' => '205.834,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '2.590.395,78',
        'netto' => '1.977.401,36',
        'vat' => '612.994,42',
      ],
      'payment' =>
      [
        'brutto' => '85,00',
        'netto' => '64,89',
        'vat' => '20,11',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '848.087,60',
      'totalBrutto' => '1.088.401,59',
      'grandTotal' => '3.884.716,37',
    ],
  ],
];
