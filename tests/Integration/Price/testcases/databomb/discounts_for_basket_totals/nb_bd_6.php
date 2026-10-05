<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_bd_databomb_user_6',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '9ecaf97cf602979f8e07de349ebab',
      'oxprice' => 416.17,
      'oxvat' => 26,
      'amount' => 877,
    ],
    1 =>
    [
      'oxid' => 'b53077904ca1712a86053806b741c',
      'oxprice' => 492.59,
      'oxvat' => 26,
      'amount' => 586,
    ],
    2 =>
    [
      'oxid' => '8d0c56f6e32e2ac71c79cb46b9d6d',
      'oxprice' => 95.48,
      'oxvat' => 26,
      'amount' => 154,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 1,
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
      'oxaddsum' => 15,
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
      'oxaddsum' => 3,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    3 =>
    [
      'oxaddsum' => 3,
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
      'oxaddsum' => 13,
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
        'oxprice' => 20,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '9ecaf97cf602979f8e07de349ebab',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 84,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '9ecaf97cf602979f8e07de349ebab',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 39,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '9ecaf97cf602979f8e07de349ebab',
          1 => 'b53077904ca1712a86053806b741c',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 27,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 5,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 29,
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
        'oxaddsum' => 9,
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
        'oxaddsum' => 6,
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
      '9ecaf97cf602979f8e07de349ebab' =>
      [
        0 => '524,37',
        1 => '459.872,49',
      ],
      'b53077904ca1712a86053806b741c' =>
      [
        0 => '620,66',
        1 => '363.706,76',
      ],
      '8d0c56f6e32e2ac71c79cb46b9d6d' =>
      [
        0 => '120,30',
        1 => '18.526,20',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '1,00',
        'bombDiscount_1' => '126.315,67',
        'bombDiscount_2' => '21.473,66',
        'bombDiscount_3' => '3,00',
        'bombDiscount_4' => '90.260,58',
      ],
      'vats' =>
      [
        26 => '124.645,56',
      ],
      'wrapping' =>
      [
        'brutto' => '57.057,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '25.278,16',
        'netto' => '20.062,03',
        'vat' => '5.216,13',
      ],
      'payment' =>
      [
        'brutto' => '27,00',
        'netto' => '21,43',
        'vat' => '5,57',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '479.405,98',
      'totalBrutto' => '842.105,45',
      'grandTotal' => '686.413,70',
    ],
  ],
];
