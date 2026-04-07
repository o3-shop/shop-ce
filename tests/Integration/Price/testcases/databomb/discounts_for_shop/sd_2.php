<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_2',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '23fcdb3146c06af51542cce15a10b',
      'oxprice' => 401.41,
      'oxvat' => 13,
      'amount' => 735,
    ],
    1 =>
    [
      'oxid' => '2e2e8744bb85cc0c3f20d68930438',
      'oxprice' => 681.69,
      'oxvat' => 13,
      'amount' => 310,
    ],
    2 =>
    [
      'oxid' => '9a767ed7a0930250ee0d0e9b616d2',
      'oxprice' => 632.32,
      'oxvat' => 40,
      'amount' => 667,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 10,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '23fcdb3146c06af51542cce15a10b',
      ],
    ],
    1 =>
    [
      'oxaddsum' => 11,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '23fcdb3146c06af51542cce15a10b',
        1 => '2e2e8744bb85cc0c3f20d68930438',
        2 => '9a767ed7a0930250ee0d0e9b616d2',
      ],
    ],
    2 =>
    [
      'oxaddsum' => 7,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => '%',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '23fcdb3146c06af51542cce15a10b',
      ],
    ],
    3 =>
    [
      'oxaddsum' => 7,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '23fcdb3146c06af51542cce15a10b',
        1 => '2e2e8744bb85cc0c3f20d68930438',
        2 => '9a767ed7a0930250ee0d0e9b616d2',
      ],
    ],
    4 =>
    [
      'oxaddsum' => 10,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => 'abs',
      'oxamount' => 0,
      'oxamountto' => 9999999,
      'oxprice' => 0,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
      'oxarticles' =>
      [
        0 => '23fcdb3146c06af51542cce15a10b',
        1 => '2e2e8744bb85cc0c3f20d68930438',
        2 => '9a767ed7a0930250ee0d0e9b616d2',
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
        'oxprice' => 20,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '23fcdb3146c06af51542cce15a10b',
          1 => '2e2e8744bb85cc0c3f20d68930438',
          2 => '9a767ed7a0930250ee0d0e9b616d2',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 94,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '23fcdb3146c06af51542cce15a10b',
          1 => '2e2e8744bb85cc0c3f20d68930438',
          2 => '9a767ed7a0930250ee0d0e9b616d2',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 98,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '23fcdb3146c06af51542cce15a10b',
          1 => '2e2e8744bb85cc0c3f20d68930438',
          2 => '9a767ed7a0930250ee0d0e9b616d2',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 6,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 84,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 11,
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
        'oxaddsum' => 27,
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
        'oxaddsum' => 71,
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
        'oxaddsum' => 79,
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
      '23fcdb3146c06af51542cce15a10b' =>
      [
        0 => '308,75',
        1 => '226.931,25',
      ],
      '2e2e8744bb85cc0c3f20d68930438' =>
      [
        0 => '653,69',
        1 => '202.643,90',
      ],
      '9a767ed7a0930250ee0d0e9b616d2' =>
      [
        0 => '604,32',
        1 => '403.081,44',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        13 => '49.420,15',
        40 => '115.166,13',
      ],
      'wrapping' =>
      [
        'brutto' => '167.776,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.249.011,89',
        'netto' => '1.105.320,26',
        'vat' => '143.691,63',
      ],
      'payment' =>
      [
        'brutto' => '6,00',
        'netto' => '5,31',
        'vat' => '0,69',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '668.070,31',
      'totalBrutto' => '832.656,59',
      'grandTotal' => '2.249.450,48',
    ],
  ],
];
