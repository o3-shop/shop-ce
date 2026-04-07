<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_18',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'd6da4dbd341e8f6093d3377bbe30d',
      'oxprice' => 596.93,
      'oxvat' => 22,
      'amount' => 764,
    ],
    1 =>
    [
      'oxid' => '80ec9fec40498f5de33cd9ad6ab95',
      'oxprice' => 446.76,
      'oxvat' => 22,
      'amount' => 190,
    ],
    2 =>
    [
      'oxid' => '8e6826830ec0f755dd465531ecd0f',
      'oxprice' => 793.99,
      'oxvat' => 6,
      'amount' => 105,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 38,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'd6da4dbd341e8f6093d3377bbe30d',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 72,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'd6da4dbd341e8f6093d3377bbe30d',
          1 => '80ec9fec40498f5de33cd9ad6ab95',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 15,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 27,
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
        'oxaddsum' => 11,
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
        'oxaddsum' => 24,
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
        'oxdiscount' => 27,
        'oxdiscounttype' => 'percent',
        'oxallowsameseries' => 1,
        'oxallowotherseries' => 1,
        'oxallowuseanother' => 1,
        'voucher_count' => 2,
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
      'd6da4dbd341e8f6093d3377bbe30d' =>
      [
        0 => '728,25',
        1 => '556.383,00',
      ],
      '80ec9fec40498f5de33cd9ad6ab95' =>
      [
        0 => '545,05',
        1 => '103.559,50',
      ],
      '8e6826830ec0f755dd465531ecd0f' =>
      [
        0 => '841,63',
        1 => '88.371,15',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        22 => '63.418,31',
        6 => '2.665,64',
      ],
      'wrapping' =>
      [
        'brutto' => '68.688,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '179.606,28',
        'netto' => '147.218,26',
        'vat' => '32.388,02',
      ],
      'payment' =>
      [
        'brutto' => '86.757,39',
        'netto' => '71.112,61',
        'vat' => '15.644,78',
      ],
      'voucher' =>
      [
        'brutto' => '349.537,31',
      ],
      'totalNetto' => '332.692,39',
      'totalBrutto' => '748.313,65',
      'grandTotal' => '733.828,01',
    ],
  ],
];
