<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_bd_databomb_user_25',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'c99a36d9b1b519590aa33d2f82819',
      'oxprice' => 149.39,
      'oxvat' => 19,
      'amount' => 439,
    ],
    1 =>
    [
      'oxid' => 'e1fbd99264e441a1793d1f8a3d5ce',
      'oxprice' => 947.84,
      'oxvat' => 19,
      'amount' => 969,
    ],
    2 =>
    [
      'oxid' => 'd1d3349e7530f149d3356591ce6c2',
      'oxprice' => 271.37,
      'oxvat' => 19,
      'amount' => 460,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 9,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    1 =>
    [
      'oxaddsum' => 1,
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
      'oxaddsum' => 3,
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
        'oxprice' => 3,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c99a36d9b1b519590aa33d2f82819',
          1 => 'e1fbd99264e441a1793d1f8a3d5ce',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 93,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c99a36d9b1b519590aa33d2f82819',
          1 => 'e1fbd99264e441a1793d1f8a3d5ce',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 34,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c99a36d9b1b519590aa33d2f82819',
          1 => 'e1fbd99264e441a1793d1f8a3d5ce',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 30,
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
        'oxaddsum' => 30,
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
        'oxaddsum' => 2,
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
        'oxaddsum' => 18,
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
        'oxaddsum' => 13,
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
      'c99a36d9b1b519590aa33d2f82819' =>
      [
        0 => '177,77',
        1 => '78.041,03',
      ],
      'e1fbd99264e441a1793d1f8a3d5ce' =>
      [
        0 => '1.127,93',
        1 => '1.092.964,17',
      ],
      'd1d3349e7530f149d3356591ce6c2' =>
      [
        0 => '322,93',
        1 => '148.547,80',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '118.759,77',
        'bombDiscount_1' => '12.007,93',
        'bombDiscount_2' => '2,00',
        'bombDiscount_3' => '6,00',
        'bombDiscount_4' => '35.663,32',
      ],
      'vats' =>
      [
        19 => '184.110,64',
      ],
      'wrapping' =>
      [
        'brutto' => '47.872,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '197.950,95',
        'netto' => '166.345,34',
        'vat' => '31.605,61',
      ],
      'payment' =>
      [
        'brutto' => '405.319,48',
        'netto' => '340.604,61',
        'vat' => '64.714,87',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '969.003,34',
      'totalBrutto' => '1.319.553,00',
      'grandTotal' => '1.804.256,41',
    ],
  ],
];
