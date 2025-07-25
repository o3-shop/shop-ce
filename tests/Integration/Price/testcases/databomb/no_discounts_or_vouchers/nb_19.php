<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_19',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'c46c909b576035aaff669606bb972',
      'oxprice' => 734.76,
      'oxvat' => 28,
      'amount' => 493,
    ],
    1 =>
    [
      'oxid' => 'a8a4b6076f362752adb28d2fc6cc0',
      'oxprice' => 870.26,
      'oxvat' => 28,
      'amount' => 774,
    ],
    2 =>
    [
      'oxid' => '5d43b3337a1b3699eaff7738f6f38',
      'oxprice' => 893.32,
      'oxvat' => 28,
      'amount' => 935,
    ],
    3 =>
    [
      'oxid' => 'd18994c352edd80dfec2bcd50f406',
      'oxprice' => 374.42,
      'oxvat' => 28,
      'amount' => 11,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 25,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c46c909b576035aaff669606bb972',
          1 => 'a8a4b6076f362752adb28d2fc6cc0',
          2 => '5d43b3337a1b3699eaff7738f6f38',
          3 => 'd18994c352edd80dfec2bcd50f406',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 24,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c46c909b576035aaff669606bb972',
          1 => 'a8a4b6076f362752adb28d2fc6cc0',
          2 => '5d43b3337a1b3699eaff7738f6f38',
          3 => 'd18994c352edd80dfec2bcd50f406',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 16,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c46c909b576035aaff669606bb972',
          1 => 'a8a4b6076f362752adb28d2fc6cc0',
          2 => '5d43b3337a1b3699eaff7738f6f38',
          3 => 'd18994c352edd80dfec2bcd50f406',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 59,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 14,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 30,
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
        'oxaddsum' => 61,
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
        'oxaddsum' => 97,
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
        'oxaddsum' => 58,
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
      'c46c909b576035aaff669606bb972' =>
      [
        0 => '940,49',
        1 => '463.661,57',
      ],
      'a8a4b6076f362752adb28d2fc6cc0' =>
      [
        0 => '1.113,93',
        1 => '862.181,82',
      ],
      '5d43b3337a1b3699eaff7738f6f38' =>
      [
        0 => '1.143,45',
        1 => '1.069.125,75',
      ],
      'd18994c352edd80dfec2bcd50f406' =>
      [
        0 => '479,26',
        1 => '5.271,86',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        28 => '525.052,72',
      ],
      'wrapping' =>
      [
        'brutto' => '35.408,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '5.184.520,56',
        'netto' => '4.050.406,69',
        'vat' => '1.134.113,87',
      ],
      'payment' =>
      [
        'brutto' => '59,00',
        'netto' => '46,09',
        'vat' => '12,91',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.875.188,28',
      'totalBrutto' => '2.400.241,00',
      'grandTotal' => '7.620.228,56',
    ],
  ],
];
