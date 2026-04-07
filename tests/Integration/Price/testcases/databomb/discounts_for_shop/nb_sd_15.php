<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_sd_databomb_user_15',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '266190cc5c579916ad322cb24b241',
      'oxprice' => 43.85,
      'oxvat' => 13,
      'amount' => 292,
    ],
    1 =>
    [
      'oxid' => '4411dbe5e043b2622098f5b14076f',
      'oxprice' => 860.29,
      'oxvat' => 13,
      'amount' => 226,
    ],
    2 =>
    [
      'oxid' => 'fbade63ef95f5d9728c3289288880',
      'oxprice' => 341.4,
      'oxvat' => 13,
      'amount' => 869,
    ],
    3 =>
    [
      'oxid' => '0a9353007179e03ca04682b542b84',
      'oxprice' => 421.09,
      'oxvat' => 13,
      'amount' => 324,
    ],
    4 =>
    [
      'oxid' => 'eaf872b10467543f5567c5e9208ea',
      'oxprice' => 972.44,
      'oxvat' => 13,
      'amount' => 689,
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
        0 => '266190cc5c579916ad322cb24b241',
      ],
    ],
    1 =>
    [
      'oxaddsum' => 10,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '266190cc5c579916ad322cb24b241',
        1 => '4411dbe5e043b2622098f5b14076f',
      ],
    ],
    2 =>
    [
      'oxaddsum' => 6,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '266190cc5c579916ad322cb24b241',
        1 => '4411dbe5e043b2622098f5b14076f',
        2 => 'fbade63ef95f5d9728c3289288880',
        3 => '0a9353007179e03ca04682b542b84',
      ],
    ],
    3 =>
    [
      'oxaddsum' => 14,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '266190cc5c579916ad322cb24b241',
      ],
    ],
    4 =>
    [
      'oxaddsum' => 9,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '266190cc5c579916ad322cb24b241',
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
        'oxprice' => 42,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '266190cc5c579916ad322cb24b241',
          1 => '4411dbe5e043b2622098f5b14076f',
          2 => 'fbade63ef95f5d9728c3289288880',
          3 => '0a9353007179e03ca04682b542b84',
          4 => 'eaf872b10467543f5567c5e9208ea',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 93,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '266190cc5c579916ad322cb24b241',
          1 => '4411dbe5e043b2622098f5b14076f',
          2 => 'fbade63ef95f5d9728c3289288880',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 21,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '266190cc5c579916ad322cb24b241',
          1 => '4411dbe5e043b2622098f5b14076f',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 16,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 17,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 7,
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
        'oxaddsum' => 17,
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
        'oxaddsum' => 5,
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
      '266190cc5c579916ad322cb24b241' =>
      [
        0 => '19,97',
        1 => '5.831,24',
      ],
      '4411dbe5e043b2622098f5b14076f' =>
      [
        0 => '868,92',
        1 => '196.375,92',
      ],
      'fbade63ef95f5d9728c3289288880' =>
      [
        0 => '379,78',
        1 => '330.028,82',
      ],
      '0a9353007179e03ca04682b542b84' =>
      [
        0 => '469,83',
        1 => '152.224,92',
      ],
      'eaf872b10467543f5567c5e9208ea' =>
      [
        0 => '1.098,86',
        1 => '757.114,54',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        13 => '165.844,96',
      ],
      'wrapping' =>
      [
        'brutto' => '134.241,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '302.735,84',
        'netto' => '267.907,82',
        'vat' => '34.828,02',
      ],
      'payment' =>
      [
        'brutto' => '279.089,80',
        'netto' => '246.982,12',
        'vat' => '32.107,68',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.275.730,48',
      'totalBrutto' => '1.441.575,44',
      'grandTotal' => '2.157.642,08',
    ],
  ],
];
