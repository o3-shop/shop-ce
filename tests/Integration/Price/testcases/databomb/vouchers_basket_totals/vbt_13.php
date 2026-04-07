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
      'oxid' => '91b4534a2a01917916402498d0156',
      'oxprice' => 291.81,
      'oxvat' => 31,
      'amount' => 816,
    ],
    1 =>
    [
      'oxid' => 'b89b29d487bca22752e4447053892',
      'oxprice' => 850.39,
      'oxvat' => 31,
      'amount' => 108,
    ],
    2 =>
    [
      'oxid' => '5fc521eff9802676dc5c440a1ff81',
      'oxprice' => 233.01,
      'oxvat' => 31,
      'amount' => 886,
    ],
    3 =>
    [
      'oxid' => '3846dc4ee26ddc6e1ff8db30f401b',
      'oxprice' => 478.27,
      'oxvat' => 31,
      'amount' => 251,
    ],
    4 =>
    [
      'oxid' => 'ca4de92a59a176aec8fa4fde578b5',
      'oxprice' => 670.36,
      'oxvat' => 31,
      'amount' => 979,
    ],
    5 =>
    [
      'oxid' => '29cc1b35b209864b48479667e2a5c',
      'oxprice' => 167.65,
      'oxvat' => 31,
      'amount' => 716,
    ],
    6 =>
    [
      'oxid' => '953a46c5ff12de2bf58f04e6b7c3c',
      'oxprice' => 884.28,
      'oxvat' => 31,
      'amount' => 487,
    ],
    7 =>
    [
      'oxid' => '6be997a886c26b08248e37bef137b',
      'oxprice' => 232.77,
      'oxvat' => 31,
      'amount' => 756,
    ],
    8 =>
    [
      'oxid' => '097c1938c4e06f38a315db25be3b8',
      'oxprice' => 9.89,
      'oxvat' => 31,
      'amount' => 390,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 35,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '91b4534a2a01917916402498d0156',
          1 => 'b89b29d487bca22752e4447053892',
          2 => '5fc521eff9802676dc5c440a1ff81',
          3 => '3846dc4ee26ddc6e1ff8db30f401b',
          4 => 'ca4de92a59a176aec8fa4fde578b5',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 56,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '91b4534a2a01917916402498d0156',
          1 => 'b89b29d487bca22752e4447053892',
          2 => '5fc521eff9802676dc5c440a1ff81',
          3 => '3846dc4ee26ddc6e1ff8db30f401b',
          4 => 'ca4de92a59a176aec8fa4fde578b5',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 58,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 78,
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
        'oxaddsum' => 33,
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
        'oxaddsum' => 83,
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
        'oxdiscount' => 7,
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
      '91b4534a2a01917916402498d0156' =>
      [
        0 => '291,81',
        1 => '238.116,96',
      ],
      'b89b29d487bca22752e4447053892' =>
      [
        0 => '850,39',
        1 => '91.842,12',
      ],
      '5fc521eff9802676dc5c440a1ff81' =>
      [
        0 => '233,01',
        1 => '206.446,86',
      ],
      '3846dc4ee26ddc6e1ff8db30f401b' =>
      [
        0 => '478,27',
        1 => '120.045,77',
      ],
      'ca4de92a59a176aec8fa4fde578b5' =>
      [
        0 => '670,36',
        1 => '656.282,44',
      ],
      '29cc1b35b209864b48479667e2a5c' =>
      [
        0 => '167,65',
        1 => '120.037,40',
      ],
      '953a46c5ff12de2bf58f04e6b7c3c' =>
      [
        0 => '884,28',
        1 => '430.644,36',
      ],
      '6be997a886c26b08248e37bef137b' =>
      [
        0 => '232,77',
        1 => '175.974,12',
      ],
      '097c1938c4e06f38a315db25be3b8' =>
      [
        0 => '9,89',
        1 => '3.857,10',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        31 => '388.919,88',
      ],
      'wrapping' =>
      [
        'brutto' => '170.240,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.695.928,12',
        'netto' => '1.294.601,62',
        'vat' => '401.326,50',
      ],
      'payment' =>
      [
        'brutto' => '1.936.868,39',
        'netto' => '1.478.525,49',
        'vat' => '458.342,90',
      ],
      'voucher' =>
      [
        'brutto' => '399.747,00',
      ],
      'totalNetto' => '1.254.580,25',
      'totalBrutto' => '2.043.247,13',
      'grandTotal' => '5.446.536,64',
    ],
  ],
];
