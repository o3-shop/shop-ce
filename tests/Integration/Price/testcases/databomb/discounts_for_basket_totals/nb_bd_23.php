<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_bd_databomb_user_23',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'ac79c98e4e4faee4e0e9f803881fd',
      'oxprice' => 980.61,
      'oxvat' => 5,
      'amount' => 412,
    ],
    1 =>
    [
      'oxid' => '77e3c62c268ce2f50068c7422496e',
      'oxprice' => 284.78,
      'oxvat' => 43,
      'amount' => 709,
    ],
    2 =>
    [
      'oxid' => '44357025bb40a164e4aae57fa9162',
      'oxprice' => 471.17,
      'oxvat' => 34,
      'amount' => 620,
    ],
    3 =>
    [
      'oxid' => '35fd6b516a4b27efae5f69a2ac6c6',
      'oxprice' => 369.15,
      'oxvat' => 34,
      'amount' => 745,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 10,
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
      'oxaddsum' => 10,
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
      'oxaddsum' => 10,
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
      'oxaddsum' => 1,
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
      'oxaddsum' => 8,
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
        'oxprice' => 81,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'ac79c98e4e4faee4e0e9f803881fd',
          1 => '77e3c62c268ce2f50068c7422496e',
          2 => '44357025bb40a164e4aae57fa9162',
          3 => '35fd6b516a4b27efae5f69a2ac6c6',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 80,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'ac79c98e4e4faee4e0e9f803881fd',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 50,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'ac79c98e4e4faee4e0e9f803881fd',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 2,
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
        'oxaddsumtype' => '%',
        'oxaddsum' => 10,
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
        'oxaddsum' => 2,
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
        'oxaddsum' => 3,
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
      'ac79c98e4e4faee4e0e9f803881fd' =>
      [
        0 => '1.029,64',
        1 => '424.211,68',
      ],
      '77e3c62c268ce2f50068c7422496e' =>
      [
        0 => '407,24',
        1 => '288.733,16',
      ],
      '44357025bb40a164e4aae57fa9162' =>
      [
        0 => '631,37',
        1 => '391.449,40',
      ],
      '35fd6b516a4b27efae5f69a2ac6c6' =>
      [
        0 => '494,66',
        1 => '368.521,70',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '10,00',
        'bombDiscount_1' => '10,00',
        'bombDiscount_2' => '10,00',
        'bombDiscount_3' => '14.728,86',
        'bombDiscount_4' => '116.652,57',
      ],
      'vats' =>
      [
        5 => '18.398,29',
        43 => '79.075,74',
        34 => '175.624,61',
      ],
      'wrapping' =>
      [
        'brutto' => '188.594,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '294.583,19',
        'netto' => '219.838,20',
        'vat' => '74.744,99',
      ],
      'payment' =>
      [
        'brutto' => '32.721,75',
        'netto' => '24.419,22',
        'vat' => '8.302,53',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.068.405,87',
      'totalBrutto' => '1.472.915,94',
      'grandTotal' => '1.857.403,45',
    ],
  ],
];
