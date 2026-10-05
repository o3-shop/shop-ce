<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_bd_databomb_user_7',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'b1672fa55bf14dab2783bdb1e4db6',
      'oxprice' => 39.79,
      'oxvat' => 28,
      'amount' => 284,
    ],
    1 =>
    [
      'oxid' => 'ffd9e1dacaca8cc915df72db26338',
      'oxprice' => 561.99,
      'oxvat' => 28,
      'amount' => 272,
    ],
    2 =>
    [
      'oxid' => 'b0ed1ff73999edc48e2233c8de2d1',
      'oxprice' => 324.07,
      'oxvat' => 28,
      'amount' => 255,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 14,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    1 =>
    [
      'oxaddsum' => 7,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    2 =>
    [
      'oxaddsum' => 2,
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
      'oxaddsum' => 6,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => 'abs',
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
      'oxaddsumtype' => '%',
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
        'oxprice' => 49,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'b1672fa55bf14dab2783bdb1e4db6',
          1 => 'ffd9e1dacaca8cc915df72db26338',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 94,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'b1672fa55bf14dab2783bdb1e4db6',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 69,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'b1672fa55bf14dab2783bdb1e4db6',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 19,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 32,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 24,
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
        'oxaddsum' => 1,
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 8,
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
      'b1672fa55bf14dab2783bdb1e4db6' =>
      [
        0 => '50,93',
        1 => '14.464,12',
      ],
      'ffd9e1dacaca8cc915df72db26338' =>
      [
        0 => '719,35',
        1 => '195.663,20',
      ],
      'b0ed1ff73999edc48e2233c8de2d1' =>
      [
        0 => '414,81',
        1 => '105.776,55',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '14,00',
        'bombDiscount_1' => '22.112,29',
        'bombDiscount_2' => '2,00',
        'bombDiscount_3' => '6,00',
        'bombDiscount_4' => '17.626,17',
      ],
      'vats' =>
      [
        28 => '60.406,37',
      ],
      'wrapping' =>
      [
        'brutto' => '32.924,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '34.758,43',
        'netto' => '27.155,02',
        'vat' => '7.603,41',
      ],
      'payment' =>
      [
        'brutto' => '59.071,35',
        'netto' => '46.149,49',
        'vat' => '12.921,86',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '215.737,04',
      'totalBrutto' => '315.903,87',
      'grandTotal' => '402.897,19',
    ],
  ],
];
