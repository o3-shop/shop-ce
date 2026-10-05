<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_sd_databomb_user_23',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '8620c24b066d751d785ffd53ab2e9',
      'oxprice' => 456.88,
      'oxvat' => 27,
      'amount' => 388,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 13,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '8620c24b066d751d785ffd53ab2e9',
      ],
    ],
    1 =>
    [
      'oxaddsum' => 11,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '8620c24b066d751d785ffd53ab2e9',
      ],
    ],
    2 =>
    [
      'oxaddsum' => 7,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '8620c24b066d751d785ffd53ab2e9',
      ],
    ],
    3 =>
    [
      'oxaddsum' => 2,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '8620c24b066d751d785ffd53ab2e9',
      ],
    ],
    4 =>
    [
      'oxaddsum' => 12,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '8620c24b066d751d785ffd53ab2e9',
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
        'oxprice' => 18,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8620c24b066d751d785ffd53ab2e9',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 64,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8620c24b066d751d785ffd53ab2e9',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 98,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '8620c24b066d751d785ffd53ab2e9',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 29,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 18,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 17,
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 19,
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
        'oxaddsum' => 24,
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
      '8620c24b066d751d785ffd53ab2e9' =>
      [
        0 => '436,34',
        1 => '169.299,92',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        27 => '35.992,90',
      ],
      'wrapping' =>
      [
        'brutto' => '38.024,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '74.510,96',
        'netto' => '58.670,05',
        'vat' => '15.840,91',
      ],
      'payment' =>
      [
        'brutto' => '70.705,16',
        'netto' => '55.673,35',
        'vat' => '15.031,81',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '133.307,02',
      'totalBrutto' => '169.299,92',
      'grandTotal' => '352.540,04',
    ],
  ],
];
