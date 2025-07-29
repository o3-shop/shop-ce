<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_29',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '91e3e17a14b401d5e991b75677147',
      'oxprice' => 920.34,
      'oxvat' => 18,
      'amount' => 711,
    ],
    1 =>
    [
      'oxid' => '31ef5e9ac05a5734807586a8dd279',
      'oxprice' => 953.96,
      'oxvat' => 18,
      'amount' => 785,
    ],
    2 =>
    [
      'oxid' => '54c3cd40bee603c7c9b6b0ba9a0c0',
      'oxprice' => 626.28,
      'oxvat' => 18,
      'amount' => 831,
    ],
    3 =>
    [
      'oxid' => '493a9e4755a175cd4a2e1009ede1a',
      'oxprice' => 296.37,
      'oxvat' => 40,
      'amount' => 379,
    ],
    4 =>
    [
      'oxid' => '6520b367baad1a777c2c67a0ca16b',
      'oxprice' => 978.7,
      'oxvat' => 40,
      'amount' => 201,
    ],
    5 =>
    [
      'oxid' => 'c7a3fef5dc5896001717068526b33',
      'oxprice' => 689.21,
      'oxvat' => 40,
      'amount' => 394,
    ],
    6 =>
    [
      'oxid' => '8b6340781dd925c41eb2e44ed071d',
      'oxprice' => 412.34,
      'oxvat' => 18,
      'amount' => 846,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 36,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '91e3e17a14b401d5e991b75677147',
          1 => '31ef5e9ac05a5734807586a8dd279',
          2 => '54c3cd40bee603c7c9b6b0ba9a0c0',
          3 => '493a9e4755a175cd4a2e1009ede1a',
          4 => '6520b367baad1a777c2c67a0ca16b',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 20,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '91e3e17a14b401d5e991b75677147',
          1 => '31ef5e9ac05a5734807586a8dd279',
          2 => '54c3cd40bee603c7c9b6b0ba9a0c0',
          3 => '493a9e4755a175cd4a2e1009ede1a',
          4 => '6520b367baad1a777c2c67a0ca16b',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 86,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '91e3e17a14b401d5e991b75677147',
          1 => '31ef5e9ac05a5734807586a8dd279',
          2 => '54c3cd40bee603c7c9b6b0ba9a0c0',
          3 => '493a9e4755a175cd4a2e1009ede1a',
          4 => '6520b367baad1a777c2c67a0ca16b',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 19,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 55,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 5,
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
        'oxaddsum' => 58,
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
        'oxaddsum' => 27,
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
        'oxaddsum' => 18,
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
      '91e3e17a14b401d5e991b75677147' =>
      [
        0 => '920,34',
        1 => '654.361,74',
      ],
      '31ef5e9ac05a5734807586a8dd279' =>
      [
        0 => '953,96',
        1 => '748.858,60',
      ],
      '54c3cd40bee603c7c9b6b0ba9a0c0' =>
      [
        0 => '626,28',
        1 => '520.438,68',
      ],
      '493a9e4755a175cd4a2e1009ede1a' =>
      [
        0 => '296,37',
        1 => '112.324,23',
      ],
      '6520b367baad1a777c2c67a0ca16b' =>
      [
        0 => '978,70',
        1 => '196.718,70',
      ],
      'c7a3fef5dc5896001717068526b33' =>
      [
        0 => '689,21',
        1 => '271.548,74',
      ],
      '8b6340781dd925c41eb2e44ed071d' =>
      [
        0 => '412,34',
        1 => '348.839,64',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        18 => '346.652,34',
        40 => '165.883,33',
      ],
      'wrapping' =>
      [
        'brutto' => '250.002,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '2.168.375,65',
        'netto' => '1.837.606,48',
        'vat' => '330.769,17',
      ],
      'payment' =>
      [
        'brutto' => '19,00',
        'netto' => '16,10',
        'vat' => '2,90',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '2.340.554,66',
      'totalBrutto' => '2.853.090,33',
      'grandTotal' => '5.271.486,98',
    ],
  ],
];
