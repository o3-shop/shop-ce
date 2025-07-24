<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_bd_databomb_user_16',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'bd6a5a7f9729bb62b590428dc7681',
      'oxprice' => 102.5,
      'oxvat' => 27,
      'amount' => 426,
    ],
    1 =>
    [
      'oxid' => '54e976b14fa71f25a303b33939d54',
      'oxprice' => 310.68,
      'oxvat' => 27,
      'amount' => 36,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 4,
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
      'oxaddsum' => 12,
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
      'oxaddsum' => 9,
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
      'oxaddsum' => 8,
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
      'oxaddsum' => 9,
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
        'oxprice' => 13,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'bd6a5a7f9729bb62b590428dc7681',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 95,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'bd6a5a7f9729bb62b590428dc7681',
          1 => '54e976b14fa71f25a303b33939d54',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 56,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'bd6a5a7f9729bb62b590428dc7681',
          1 => '54e976b14fa71f25a303b33939d54',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 5,
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
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 31,
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
        'oxaddsum' => 12,
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
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 21,
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
      'bd6a5a7f9729bb62b590428dc7681' =>
      [
        0 => '130,18',
        1 => '55.456,68',
      ],
      '54e976b14fa71f25a303b33939d54' =>
      [
        0 => '394,56',
        1 => '14.204,16',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '4,00',
        'bombDiscount_1' => '12,00',
        'bombDiscount_2' => '9,00',
        'bombDiscount_3' => '8,00',
        'bombDiscount_4' => '6.266,51',
      ],
      'vats' =>
      [
        27 => '13.470,52',
      ],
      'wrapping' =>
      [
        'brutto' => '25.872,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '2.819,43',
        'netto' => '2.220,02',
        'vat' => '599,41',
      ],
      'payment' =>
      [
        'brutto' => '3.309,04',
        'netto' => '2.605,54',
        'vat' => '703,50',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '49.890,81',
      'totalBrutto' => '69.660,84',
      'grandTotal' => '95.361,80',
    ],
  ],
];
