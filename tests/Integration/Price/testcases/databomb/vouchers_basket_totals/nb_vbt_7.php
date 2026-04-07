<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_vbt_databomb_user_7',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'd7bcd0e6b569d1adf35d59501bd48',
      'oxprice' => 653.38,
      'oxvat' => 28,
      'amount' => 65,
    ],
    1 =>
    [
      'oxid' => '4b4dc681e4f5ea7ebe00e560ea599',
      'oxprice' => 436.49,
      'oxvat' => 42,
      'amount' => 303,
    ],
    2 =>
    [
      'oxid' => '4d442a6eec220f21dc18e8f3dad0c',
      'oxprice' => 605.46,
      'oxvat' => 42,
      'amount' => 829,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 95,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'd7bcd0e6b569d1adf35d59501bd48',
          1 => '4b4dc681e4f5ea7ebe00e560ea599',
          2 => '4d442a6eec220f21dc18e8f3dad0c',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 4,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'd7bcd0e6b569d1adf35d59501bd48',
          1 => '4b4dc681e4f5ea7ebe00e560ea599',
          2 => '4d442a6eec220f21dc18e8f3dad0c',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 9,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 32,
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 16,
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
        'oxdiscount' => 19,
        'oxdiscounttype' => 'absolute',
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
      'd7bcd0e6b569d1adf35d59501bd48' =>
      [
        0 => '836,33',
        1 => '54.361,45',
      ],
      '4b4dc681e4f5ea7ebe00e560ea599' =>
      [
        0 => '619,82',
        1 => '187.805,46',
      ],
      '4d442a6eec220f21dc18e8f3dad0c' =>
      [
        0 => '859,75',
        1 => '712.732,75',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        28 => '11.891,09',
        42 => '266.345,77',
      ],
      'wrapping' =>
      [
        'brutto' => '4.788,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '143.250,95',
        'netto' => '100.880,95',
        'vat' => '42.370,00',
      ],
      'payment' =>
      [
        'brutto' => '9,00',
        'netto' => '6,34',
        'vat' => '2,66',
      ],
      'voucher' =>
      [
        'brutto' => '38,00',
      ],
      'totalNetto' => '676.624,80',
      'totalBrutto' => '954.899,66',
      'grandTotal' => '1.102.909,61',
    ],
  ],
];
