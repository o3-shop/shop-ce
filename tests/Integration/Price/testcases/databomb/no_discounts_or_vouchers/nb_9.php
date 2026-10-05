<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_9',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '6a9a6e3f680f9b747ac36863b7752',
      'oxprice' => 755.38,
      'oxvat' => 29,
      'amount' => 795,
    ],
    1 =>
    [
      'oxid' => '09bd8260ce43ba510e592ea80d6a8',
      'oxprice' => 84.3,
      'oxvat' => 42,
      'amount' => 429,
    ],
    2 =>
    [
      'oxid' => '382722b3ad841f56770ef81353bda',
      'oxprice' => 791.13,
      'oxvat' => 29,
      'amount' => 634,
    ],
    3 =>
    [
      'oxid' => '6cca4757ea3f96d359b2c942e8820',
      'oxprice' => 31.82,
      'oxvat' => 42,
      'amount' => 181,
    ],
    4 =>
    [
      'oxid' => '44bf410906f81f9c26e79177360ef',
      'oxprice' => 344.33,
      'oxvat' => 42,
      'amount' => 548,
    ],
    5 =>
    [
      'oxid' => '809fed271f8d1cf3197b8e6f4b324',
      'oxprice' => 557.57,
      'oxvat' => 32,
      'amount' => 212,
    ],
    6 =>
    [
      'oxid' => '60b5bdb5d40e54ef7f94d7f34e2a2',
      'oxprice' => 125.33,
      'oxvat' => 32,
      'amount' => 196,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 71,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '6a9a6e3f680f9b747ac36863b7752',
          1 => '09bd8260ce43ba510e592ea80d6a8',
          2 => '382722b3ad841f56770ef81353bda',
          3 => '6cca4757ea3f96d359b2c942e8820',
          4 => '44bf410906f81f9c26e79177360ef',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 43,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '6a9a6e3f680f9b747ac36863b7752',
          1 => '09bd8260ce43ba510e592ea80d6a8',
          2 => '382722b3ad841f56770ef81353bda',
          3 => '6cca4757ea3f96d359b2c942e8820',
          4 => '44bf410906f81f9c26e79177360ef',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 23,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '6a9a6e3f680f9b747ac36863b7752',
          1 => '09bd8260ce43ba510e592ea80d6a8',
          2 => '382722b3ad841f56770ef81353bda',
          3 => '6cca4757ea3f96d359b2c942e8820',
          4 => '44bf410906f81f9c26e79177360ef',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 42,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 98,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 49,
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
        'oxaddsum' => 18,
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
        'oxaddsum' => 20,
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
        'oxaddsum' => 87,
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
      '6a9a6e3f680f9b747ac36863b7752' =>
      [
        0 => '974,44',
        1 => '774.679,80',
      ],
      '09bd8260ce43ba510e592ea80d6a8' =>
      [
        0 => '119,71',
        1 => '51.355,59',
      ],
      '382722b3ad841f56770ef81353bda' =>
      [
        0 => '1.020,56',
        1 => '647.035,04',
      ],
      '6cca4757ea3f96d359b2c942e8820' =>
      [
        0 => '45,18',
        1 => '8.177,58',
      ],
      '44bf410906f81f9c26e79177360ef' =>
      [
        0 => '488,95',
        1 => '267.944,60',
      ],
      '809fed271f8d1cf3197b8e6f4b324' =>
      [
        0 => '735,99',
        1 => '156.029,88',
      ],
      '60b5bdb5d40e54ef7f94d7f34e2a2' =>
      [
        0 => '165,44',
        1 => '32.426,24',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        29 => '319.610,31',
        42 => '96.859,62',
        32 => '45.686,33',
      ],
      'wrapping' =>
      [
        'brutto' => '59.501,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '736.393,52',
        'netto' => '570.847,69',
        'vat' => '165.545,83',
      ],
      'payment' =>
      [
        'brutto' => '1.123.097,75',
        'netto' => '870.618,41',
        'vat' => '252.479,34',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.475.492,47',
      'totalBrutto' => '1.937.648,73',
      'grandTotal' => '3.856.641,00',
    ],
  ],
];
