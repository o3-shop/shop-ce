<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_4',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '0580e6521fab1b675d84a4e56c0ae',
      'oxprice' => 287.81,
      'oxvat' => 41,
      'amount' => 554,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 3,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    1 =>
    [
      'oxaddsum' => 2,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    2 =>
    [
      'oxaddsum' => 1,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    3 =>
    [
      'oxaddsum' => 9,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    4 =>
    [
      'oxaddsum' => 6,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 59,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '0580e6521fab1b675d84a4e56c0ae',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 89,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '0580e6521fab1b675d84a4e56c0ae',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 24,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '0580e6521fab1b675d84a4e56c0ae',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 81,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 3,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 22,
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
        'oxaddsum' => 62,
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
        'oxaddsum' => 11,
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
        'oxaddsum' => 4,
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
      '0580e6521fab1b675d84a4e56c0ae' =>
      [
        0 => '287,81',
        1 => '159.446,74',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '4.783,40',
        'bombDiscount_1' => '2,00',
        'bombDiscount_2' => '1,00',
        'bombDiscount_3' => '13.919,43',
        'bombDiscount_4' => '6,00',
      ],
      'vats' =>
      [
        41 => '40.922,92',
      ],
      'wrapping' =>
      [
        'brutto' => '13.296,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '6.450,87',
        'netto' => '4.575,09',
        'vat' => '1.875,78',
      ],
      'payment' =>
      [
        'brutto' => '119.220,48',
        'netto' => '84.553,53',
        'vat' => '34.666,95',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '99.811,99',
      'totalBrutto' => '159.446,74',
      'grandTotal' => '279.702,26',
    ],
  ],
];
