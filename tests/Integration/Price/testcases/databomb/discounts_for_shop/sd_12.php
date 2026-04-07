<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_12',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '2a26663de93ef966c175feb2e6939',
      'oxprice' => 805.69,
      'oxvat' => 27,
      'amount' => 459,
    ],
    1 =>
    [
      'oxid' => 'cb6ff498fcb9d2a941f21142a7c49',
      'oxprice' => 572.35,
      'oxvat' => 18,
      'amount' => 966,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 11,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '2a26663de93ef966c175feb2e6939',
        1 => 'cb6ff498fcb9d2a941f21142a7c49',
      ],
    ],
    1 =>
    [
      'oxaddsum' => 6,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '2a26663de93ef966c175feb2e6939',
        1 => 'cb6ff498fcb9d2a941f21142a7c49',
      ],
    ],
    2 =>
    [
      'oxaddsum' => 1,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '2a26663de93ef966c175feb2e6939',
      ],
    ],
    3 =>
    [
      'oxaddsum' => 6,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '2a26663de93ef966c175feb2e6939',
        1 => 'cb6ff498fcb9d2a941f21142a7c49',
      ],
    ],
    4 =>
    [
      'oxaddsum' => 2,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '2a26663de93ef966c175feb2e6939',
        1 => 'cb6ff498fcb9d2a941f21142a7c49',
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
        'oxprice' => 55,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '2a26663de93ef966c175feb2e6939',
          1 => 'cb6ff498fcb9d2a941f21142a7c49',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 56,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '2a26663de93ef966c175feb2e6939',
          1 => 'cb6ff498fcb9d2a941f21142a7c49',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 37,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '2a26663de93ef966c175feb2e6939',
          1 => 'cb6ff498fcb9d2a941f21142a7c49',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 78,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 69,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
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
        'oxaddsumtype' => '%',
        'oxaddsum' => 45,
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
        'oxaddsum' => 56,
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
      '2a26663de93ef966c175feb2e6939' =>
      [
        0 => '689,98',
        1 => '316.700,82',
      ],
      'cb6ff498fcb9d2a941f21142a7c49' =>
      [
        0 => '487,44',
        1 => '470.867,04',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        27 => '67.330,10',
        18 => '71.827,18',
      ],
      'wrapping' =>
      [
        'brutto' => '52.725,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '795.491,54',
        'netto' => '674.145,37',
        'vat' => '121.346,17',
      ],
      'payment' =>
      [
        'brutto' => '78,00',
        'netto' => '66,10',
        'vat' => '11,90',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '648.410,58',
      'totalBrutto' => '787.567,86',
      'grandTotal' => '1.635.862,40',
    ],
  ],
];
