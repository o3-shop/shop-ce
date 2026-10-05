<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_26',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'e246bb734def9abf71289667f6dd2',
      'oxprice' => 706.87,
      'oxvat' => 28,
      'amount' => 442,
    ],
    1 =>
    [
      'oxid' => '42dc9e322e73ba1fa16eaf5f60d1b',
      'oxprice' => 314.05,
      'oxvat' => 28,
      'amount' => 307,
    ],
    2 =>
    [
      'oxid' => '7902876fa6b114ee1a48d88fe7ee3',
      'oxprice' => 964.21,
      'oxvat' => 28,
      'amount' => 273,
    ],
    3 =>
    [
      'oxid' => '25ead0c851875405edca972bc4c9f',
      'oxprice' => 288.26,
      'oxvat' => 28,
      'amount' => 566,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 6,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => 'e246bb734def9abf71289667f6dd2',
      ],
    ],
    1 =>
    [
      'oxaddsum' => 4,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => 'e246bb734def9abf71289667f6dd2',
        1 => '42dc9e322e73ba1fa16eaf5f60d1b',
      ],
    ],
    2 =>
    [
      'oxaddsum' => 11,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => 'e246bb734def9abf71289667f6dd2',
        1 => '42dc9e322e73ba1fa16eaf5f60d1b',
        2 => '7902876fa6b114ee1a48d88fe7ee3',
      ],
    ],
    3 =>
    [
      'oxaddsum' => 3,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => 'e246bb734def9abf71289667f6dd2',
        1 => '42dc9e322e73ba1fa16eaf5f60d1b',
      ],
    ],
    4 =>
    [
      'oxaddsum' => 11,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => 'e246bb734def9abf71289667f6dd2',
      ],
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 41,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'e246bb734def9abf71289667f6dd2',
          1 => '42dc9e322e73ba1fa16eaf5f60d1b',
          2 => '7902876fa6b114ee1a48d88fe7ee3',
          3 => '25ead0c851875405edca972bc4c9f',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 44,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'e246bb734def9abf71289667f6dd2',
          1 => '42dc9e322e73ba1fa16eaf5f60d1b',
          2 => '7902876fa6b114ee1a48d88fe7ee3',
          3 => '25ead0c851875405edca972bc4c9f',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 2,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'e246bb734def9abf71289667f6dd2',
          1 => '42dc9e322e73ba1fa16eaf5f60d1b',
          2 => '7902876fa6b114ee1a48d88fe7ee3',
          3 => '25ead0c851875405edca972bc4c9f',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 79,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 35,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 37,
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
        'oxaddsum' => 42,
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
        'oxaddsum' => 43,
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
        'oxaddsum' => 48,
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
      'e246bb734def9abf71289667f6dd2' =>
      [
        0 => '520,48',
        1 => '230.052,16',
      ],
      '42dc9e322e73ba1fa16eaf5f60d1b' =>
      [
        0 => '272,94',
        1 => '83.792,58',
      ],
      '7902876fa6b114ee1a48d88fe7ee3' =>
      [
        0 => '858,15',
        1 => '234.274,95',
      ],
      '25ead0c851875405edca972bc4c9f' =>
      [
        0 => '288,26',
        1 => '163.155,16',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        28 => '155.591,37',
      ],
      'wrapping' =>
      [
        'brutto' => '3.176,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '640.190,37',
        'netto' => '500.148,73',
        'vat' => '140.041,64',
      ],
      'payment' =>
      [
        'brutto' => '1.067.657,52',
        'netto' => '834.107,44',
        'vat' => '233.550,08',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '555.683,48',
      'totalBrutto' => '711.274,85',
      'grandTotal' => '2.422.298,74',
    ],
  ],
];
