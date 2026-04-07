<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_6',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '6760de02aa356bb4398267716c6e9',
      'oxprice' => 719.24,
      'oxvat' => 19,
      'amount' => 987,
    ],
    1 =>
    [
      'oxid' => '0d34c1348dc410946f51107052c65',
      'oxprice' => 827.52,
      'oxvat' => 19,
      'amount' => 939,
    ],
    2 =>
    [
      'oxid' => 'e1b0f2bb8e99a514841bf94112cc3',
      'oxprice' => 322.78,
      'oxvat' => 19,
      'amount' => 510,
    ],
    3 =>
    [
      'oxid' => 'd8f4b2ee7bed49a19774e4a392301',
      'oxprice' => 588.89,
      'oxvat' => 19,
      'amount' => 370,
    ],
    4 =>
    [
      'oxid' => '22e19fda98880c581d1edbef97546',
      'oxprice' => 40.08,
      'oxvat' => 19,
      'amount' => 626,
    ],
    5 =>
    [
      'oxid' => '368ffb3b0f194e5e531807de13246',
      'oxprice' => 475.44,
      'oxvat' => 19,
      'amount' => 586,
    ],
    6 =>
    [
      'oxid' => '84922b5ef044f719a6041389f651a',
      'oxprice' => 286.22,
      'oxvat' => 19,
      'amount' => 929,
    ],
    7 =>
    [
      'oxid' => 'e2926522b4404049e80849fd30e4a',
      'oxprice' => 739.14,
      'oxvat' => 19,
      'amount' => 600,
    ],
    8 =>
    [
      'oxid' => 'be7ac1ff2727b0eca4f22a4e7324b',
      'oxprice' => 50.92,
      'oxvat' => 19,
      'amount' => 705,
    ],
    9 =>
    [
      'oxid' => 'dc338657c247c79e4a7c791f20dfa',
      'oxprice' => 792.85,
      'oxvat' => 19,
      'amount' => 414,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 69,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '6760de02aa356bb4398267716c6e9',
          1 => '0d34c1348dc410946f51107052c65',
          2 => 'e1b0f2bb8e99a514841bf94112cc3',
          3 => 'd8f4b2ee7bed49a19774e4a392301',
          4 => '22e19fda98880c581d1edbef97546',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 44,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '6760de02aa356bb4398267716c6e9',
          1 => '0d34c1348dc410946f51107052c65',
          2 => 'e1b0f2bb8e99a514841bf94112cc3',
          3 => 'd8f4b2ee7bed49a19774e4a392301',
          4 => '22e19fda98880c581d1edbef97546',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 2,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 29,
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
        'oxaddsum' => 35,
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
        'oxaddsum' => 13,
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
        'oxdiscount' => 18,
        'oxdiscounttype' => 'percent',
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
      '6760de02aa356bb4398267716c6e9' =>
      [
        0 => '719,24',
        1 => '709.889,88',
      ],
      '0d34c1348dc410946f51107052c65' =>
      [
        0 => '827,52',
        1 => '777.041,28',
      ],
      'e1b0f2bb8e99a514841bf94112cc3' =>
      [
        0 => '322,78',
        1 => '164.617,80',
      ],
      'd8f4b2ee7bed49a19774e4a392301' =>
      [
        0 => '588,89',
        1 => '217.889,30',
      ],
      '22e19fda98880c581d1edbef97546' =>
      [
        0 => '40,08',
        1 => '25.090,08',
      ],
      '368ffb3b0f194e5e531807de13246' =>
      [
        0 => '475,44',
        1 => '278.607,84',
      ],
      '84922b5ef044f719a6041389f651a' =>
      [
        0 => '286,22',
        1 => '265.898,38',
      ],
      'e2926522b4404049e80849fd30e4a' =>
      [
        0 => '739,14',
        1 => '443.484,00',
      ],
      'be7ac1ff2727b0eca4f22a4e7324b' =>
      [
        0 => '50,92',
        1 => '35.898,60',
      ],
      'dc338657c247c79e4a7c791f20dfa' =>
      [
        0 => '792,85',
        1 => '328.239,90',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        19 => '285.814,73',
      ],
      'wrapping' =>
      [
        'brutto' => '151.008,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.136.342,97',
        'netto' => '954.910,06',
        'vat' => '181.432,91',
      ],
      'payment' =>
      [
        'brutto' => '2,00',
        'netto' => '1,68',
        'vat' => '0,32',
      ],
      'voucher' =>
      [
        'brutto' => '1.456.554,25',
      ],
      'totalNetto' => '1.504.288,08',
      'totalBrutto' => '3.246.657,06',
      'grandTotal' => '3.077.455,78',
    ],
  ],
];
