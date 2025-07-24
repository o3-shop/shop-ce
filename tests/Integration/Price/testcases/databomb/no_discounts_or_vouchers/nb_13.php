<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_13',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'f01aa6f6b43333021e8465e304ad1',
      'oxprice' => 435.27,
      'oxvat' => 7,
      'amount' => 338,
    ],
    1 =>
    [
      'oxid' => '54a5e0ee0538b356a977c7e2b3010',
      'oxprice' => 800.82,
      'oxvat' => 7,
      'amount' => 228,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 33,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f01aa6f6b43333021e8465e304ad1',
          1 => '54a5e0ee0538b356a977c7e2b3010',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 56,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f01aa6f6b43333021e8465e304ad1',
          1 => '54a5e0ee0538b356a977c7e2b3010',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 98,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'f01aa6f6b43333021e8465e304ad1',
          1 => '54a5e0ee0538b356a977c7e2b3010',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 4,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 69,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 71,
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
        'oxaddsum' => 32,
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
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 69,
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
      'f01aa6f6b43333021e8465e304ad1' =>
      [
        0 => '465,74',
        1 => '157.420,12',
      ],
      '54a5e0ee0538b356a977c7e2b3010' =>
      [
        0 => '856,88',
        1 => '195.368,64',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        7 => '23.079,64',
      ],
      'wrapping' =>
      [
        'brutto' => '55.468,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '328.125,54',
        'netto' => '306.659,38',
        'vat' => '21.466,16',
      ],
      'payment' =>
      [
        'brutto' => '27.236,57',
        'netto' => '25.454,74',
        'vat' => '1.781,83',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '329.709,12',
      'totalBrutto' => '352.788,76',
      'grandTotal' => '763.618,87',
    ],
  ],
];
