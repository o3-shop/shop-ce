<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_28',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'c7874f4fdbfad9dd34df208d7f463',
      'oxprice' => 678.46,
      'oxvat' => 20,
      'amount' => 307,
    ],
    1 =>
    [
      'oxid' => 'a2e8ac7e0b473ca4fde78d54fb04a',
      'oxprice' => 491.12,
      'oxvat' => 35,
      'amount' => 684,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 70,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c7874f4fdbfad9dd34df208d7f463',
          1 => 'a2e8ac7e0b473ca4fde78d54fb04a',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 12,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c7874f4fdbfad9dd34df208d7f463',
          1 => 'a2e8ac7e0b473ca4fde78d54fb04a',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 16,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'c7874f4fdbfad9dd34df208d7f463',
          1 => 'a2e8ac7e0b473ca4fde78d54fb04a',
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
        'oxaddsum' => 98,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 9,
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
        'oxaddsum' => 80,
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
        'oxaddsum' => 47,
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
        'oxaddsum' => 49,
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
      'c7874f4fdbfad9dd34df208d7f463' =>
      [
        0 => '814,15',
        1 => '249.944,05',
      ],
      'a2e8ac7e0b473ca4fde78d54fb04a' =>
      [
        0 => '663,01',
        1 => '453.498,84',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        20 => '41.657,34',
        35 => '117.573,77',
      ],
      'wrapping' =>
      [
        'brutto' => '15.856,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '176,00',
        'netto' => '130,37',
        'vat' => '45,63',
      ],
      'payment' =>
      [
        'brutto' => '133.687,59',
        'netto' => '99.027,84',
        'vat' => '34.659,75',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '544.211,78',
      'totalBrutto' => '703.442,89',
      'grandTotal' => '853.162,48',
    ],
  ],
];
