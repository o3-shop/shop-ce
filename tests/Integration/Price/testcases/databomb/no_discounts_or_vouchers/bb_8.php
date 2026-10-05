<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_8',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '1c1cec881c6f1c8447c6a8d9ce0bc',
      'oxprice' => 167.68,
      'oxvat' => 41,
      'amount' => 56,
    ],
    1 =>
    [
      'oxid' => 'ff52a57e985d509a3592c8f0b6069',
      'oxprice' => 850.56,
      'oxvat' => 41,
      'amount' => 315,
    ],
    2 =>
    [
      'oxid' => '3515c6b263937f1cd898fbd69fcd9',
      'oxprice' => 495.6,
      'oxvat' => 41,
      'amount' => 405,
    ],
    3 =>
    [
      'oxid' => '02afbc095d28d23e55644454b6f1a',
      'oxprice' => 111.86,
      'oxvat' => 41,
      'amount' => 696,
    ],
    4 =>
    [
      'oxid' => '5ed416daa3352d706ca1625f67768',
      'oxprice' => 673.65,
      'oxvat' => 41,
      'amount' => 996,
    ],
    5 =>
    [
      'oxid' => '240a980d0d4ba8fe547bc03fe0f02',
      'oxprice' => 260.92,
      'oxvat' => 41,
      'amount' => 235,
    ],
    6 =>
    [
      'oxid' => '29574d48d8f87281ebf0506d5e454',
      'oxprice' => 80.06,
      'oxvat' => 41,
      'amount' => 149,
    ],
    7 =>
    [
      'oxid' => '1037ae340b89026615116bc4ffc30',
      'oxprice' => 455.42,
      'oxvat' => 41,
      'amount' => 795,
    ],
    8 =>
    [
      'oxid' => '8c2bc5448ce64a90488583cbf7e24',
      'oxprice' => 128.49,
      'oxvat' => 41,
      'amount' => 74,
    ],
    9 =>
    [
      'oxid' => '6479c17e2003bd713bab748ac442f',
      'oxprice' => 271.81,
      'oxvat' => 41,
      'amount' => 323,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 63,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '1c1cec881c6f1c8447c6a8d9ce0bc',
          1 => 'ff52a57e985d509a3592c8f0b6069',
          2 => '3515c6b263937f1cd898fbd69fcd9',
          3 => '02afbc095d28d23e55644454b6f1a',
          4 => '5ed416daa3352d706ca1625f67768',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 10,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '1c1cec881c6f1c8447c6a8d9ce0bc',
          1 => 'ff52a57e985d509a3592c8f0b6069',
          2 => '3515c6b263937f1cd898fbd69fcd9',
          3 => '02afbc095d28d23e55644454b6f1a',
          4 => '5ed416daa3352d706ca1625f67768',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 58,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '1c1cec881c6f1c8447c6a8d9ce0bc',
          1 => 'ff52a57e985d509a3592c8f0b6069',
          2 => '3515c6b263937f1cd898fbd69fcd9',
          3 => '02afbc095d28d23e55644454b6f1a',
          4 => '5ed416daa3352d706ca1625f67768',
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 38,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 13,
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
        'oxaddsum' => 8,
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
        'oxaddsum' => 82,
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
        'oxaddsum' => 88,
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
      '1c1cec881c6f1c8447c6a8d9ce0bc' =>
      [
        0 => '167,68',
        1 => '9.390,08',
      ],
      'ff52a57e985d509a3592c8f0b6069' =>
      [
        0 => '850,56',
        1 => '267.926,40',
      ],
      '3515c6b263937f1cd898fbd69fcd9' =>
      [
        0 => '495,60',
        1 => '200.718,00',
      ],
      '02afbc095d28d23e55644454b6f1a' =>
      [
        0 => '111,86',
        1 => '77.854,56',
      ],
      '5ed416daa3352d706ca1625f67768' =>
      [
        0 => '673,65',
        1 => '670.955,40',
      ],
      '240a980d0d4ba8fe547bc03fe0f02' =>
      [
        0 => '260,92',
        1 => '61.316,20',
      ],
      '29574d48d8f87281ebf0506d5e454' =>
      [
        0 => '80,06',
        1 => '11.928,94',
      ],
      '1037ae340b89026615116bc4ffc30' =>
      [
        0 => '455,42',
        1 => '362.058,90',
      ],
      '8c2bc5448ce64a90488583cbf7e24' =>
      [
        0 => '128,49',
        1 => '9.508,26',
      ],
      '6479c17e2003bd713bab748ac442f' =>
      [
        0 => '271,81',
        1 => '87.794,63',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        41 => '511.613,52',
      ],
      'wrapping' =>
      [
        'brutto' => '143.144,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '2.991.075,33',
        'netto' => '2.121.330,02',
        'vat' => '869.745,31',
      ],
      'payment' =>
      [
        'brutto' => '6,00',
        'netto' => '4,26',
        'vat' => '1,74',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.247.837,85',
      'totalBrutto' => '1.759.451,37',
      'grandTotal' => '4.893.676,70',
    ],
  ],
];
