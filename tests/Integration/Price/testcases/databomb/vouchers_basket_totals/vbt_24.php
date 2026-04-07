<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_24',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '0bc972bec3771ebe6c2fabcea2f1a',
      'oxprice' => 753.61,
      'oxvat' => 20,
      'amount' => 305,
    ],
    1 =>
    [
      'oxid' => '815e81b14a480db71f26570c29ace',
      'oxprice' => 434.73,
      'oxvat' => 25,
      'amount' => 746,
    ],
    2 =>
    [
      'oxid' => '11e0e9876cc1fd2fc7954bff9e527',
      'oxprice' => 515.62,
      'oxvat' => 25,
      'amount' => 47,
    ],
    3 =>
    [
      'oxid' => '76e1181468a57dbda24673ce8eda7',
      'oxprice' => 916.95,
      'oxvat' => 25,
      'amount' => 156,
    ],
    4 =>
    [
      'oxid' => '5391eefd432dfc9f457a08e368082',
      'oxprice' => 434.08,
      'oxvat' => 20,
      'amount' => 569,
    ],
    5 =>
    [
      'oxid' => 'e0fe5124d50fb49c5e1b4b1a8769d',
      'oxprice' => 656.01,
      'oxvat' => 25,
      'amount' => 35,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 54,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '0bc972bec3771ebe6c2fabcea2f1a',
          1 => '815e81b14a480db71f26570c29ace',
          2 => '11e0e9876cc1fd2fc7954bff9e527',
          3 => '76e1181468a57dbda24673ce8eda7',
          4 => '5391eefd432dfc9f457a08e368082',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 64,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '0bc972bec3771ebe6c2fabcea2f1a',
          1 => '815e81b14a480db71f26570c29ace',
          2 => '11e0e9876cc1fd2fc7954bff9e527',
          3 => '76e1181468a57dbda24673ce8eda7',
          4 => '5391eefd432dfc9f457a08e368082',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 69,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 79,
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
        'oxaddsum' => 94,
        'oxactive' => 1,
        'oxdeltype' => 'p',
        'oxfinalize' => 0,
        'oxparam' => 0,
        'oxparamend' => 999999999,
        'oxfixed' => 0,
      ],
    ],
    'voucherserie' =>
    [
      0 =>
      [
        'oxdiscount' => 1,
        'oxdiscounttype' => 'absolute',
        'oxallowsameseries' => 1,
        'oxallowotherseries' => 1,
        'oxallowuseanother' => 1,
        'voucher_count' => 3,
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
      '0bc972bec3771ebe6c2fabcea2f1a' =>
      [
        0 => '753,61',
        1 => '229.851,05',
      ],
      '815e81b14a480db71f26570c29ace' =>
      [
        0 => '434,73',
        1 => '324.308,58',
      ],
      '11e0e9876cc1fd2fc7954bff9e527' =>
      [
        0 => '515,62',
        1 => '24.234,14',
      ],
      '76e1181468a57dbda24673ce8eda7' =>
      [
        0 => '916,95',
        1 => '143.044,20',
      ],
      '5391eefd432dfc9f457a08e368082' =>
      [
        0 => '434,08',
        1 => '246.991,52',
      ],
      'e0fe5124d50fb49c5e1b4b1a8769d' =>
      [
        0 => '656,01',
        1 => '22.960,35',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        20 => '79.473,52',
        25 => '102.909,14',
      ],
      'wrapping' =>
      [
        'brutto' => '116.672,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '156,00',
        'netto' => '124,80',
        'vat' => '31,20',
      ],
      'payment' =>
      [
        'brutto' => '69,00',
        'netto' => '55,20',
        'vat' => '13,80',
      ],
      'voucher' =>
      [
        'brutto' => '3,00',
      ],
      'totalNetto' => '809.004,18',
      'totalBrutto' => '991.389,84',
      'grandTotal' => '1.108.283,84',
    ],
  ],
];
