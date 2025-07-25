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
      'oxid' => '1aaa8bcbee7156fc3678243560608',
      'oxprice' => 68.46,
      'oxvat' => 31,
      'amount' => 132,
    ],
    1 =>
    [
      'oxid' => '7ea0b5a9b79ad4490d655e49467b5',
      'oxprice' => 557.33,
      'oxvat' => 22,
      'amount' => 925,
    ],
    2 =>
    [
      'oxid' => '82ac99f7eaf632b8abd3c04cebdb4',
      'oxprice' => 575.64,
      'oxvat' => 31,
      'amount' => 475,
    ],
    3 =>
    [
      'oxid' => '85ed833ed92b43dd3ea3f9f13bd82',
      'oxprice' => 462.94,
      'oxvat' => 31,
      'amount' => 766,
    ],
    4 =>
    [
      'oxid' => '52f7640fb4cf51175971ff0b3b9d7',
      'oxprice' => 520.43,
      'oxvat' => 31,
      'amount' => 205,
    ],
    5 =>
    [
      'oxid' => '4b7c918db5bc44d914ee6dc9b7904',
      'oxprice' => 693.34,
      'oxvat' => 22,
      'amount' => 543,
    ],
    6 =>
    [
      'oxid' => 'd956adc6eb94534cf5c74f5dd83fd',
      'oxprice' => 473.64,
      'oxvat' => 22,
      'amount' => 286,
    ],
    7 =>
    [
      'oxid' => '2217bc08deebe9ebcfc7565c483fa',
      'oxprice' => 733.17,
      'oxvat' => 22,
      'amount' => 882,
    ],
    8 =>
    [
      'oxid' => 'cb6996ee154191d7b223e419adc92',
      'oxprice' => 450.87,
      'oxvat' => 31,
      'amount' => 336,
    ],
    9 =>
    [
      'oxid' => 'bb379a602596372909acd0ef5824c',
      'oxprice' => 270.38,
      'oxvat' => 22,
      'amount' => 929,
    ],
    10 =>
    [
      'oxid' => '73ee076c830d4c14a2a5c7f8abd0b',
      'oxprice' => 648.2,
      'oxvat' => 31,
      'amount' => 735,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 53,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '1aaa8bcbee7156fc3678243560608',
          1 => '7ea0b5a9b79ad4490d655e49467b5',
          2 => '82ac99f7eaf632b8abd3c04cebdb4',
          3 => '85ed833ed92b43dd3ea3f9f13bd82',
          4 => '52f7640fb4cf51175971ff0b3b9d7',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 76,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '1aaa8bcbee7156fc3678243560608',
          1 => '7ea0b5a9b79ad4490d655e49467b5',
          2 => '82ac99f7eaf632b8abd3c04cebdb4',
          3 => '85ed833ed92b43dd3ea3f9f13bd82',
          4 => '52f7640fb4cf51175971ff0b3b9d7',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 36,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 31,
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
        'oxaddsum' => 98,
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
        'oxaddsum' => 5,
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
        'oxdiscount' => 28,
        'oxdiscounttype' => 'absolute',
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
      '1aaa8bcbee7156fc3678243560608' =>
      [
        0 => '68,46',
        1 => '9.036,72',
      ],
      '7ea0b5a9b79ad4490d655e49467b5' =>
      [
        0 => '557,33',
        1 => '515.530,25',
      ],
      '82ac99f7eaf632b8abd3c04cebdb4' =>
      [
        0 => '575,64',
        1 => '273.429,00',
      ],
      '85ed833ed92b43dd3ea3f9f13bd82' =>
      [
        0 => '462,94',
        1 => '354.612,04',
      ],
      '52f7640fb4cf51175971ff0b3b9d7' =>
      [
        0 => '520,43',
        1 => '106.688,15',
      ],
      '4b7c918db5bc44d914ee6dc9b7904' =>
      [
        0 => '693,34',
        1 => '376.483,62',
      ],
      'd956adc6eb94534cf5c74f5dd83fd' =>
      [
        0 => '473,64',
        1 => '135.461,04',
      ],
      '2217bc08deebe9ebcfc7565c483fa' =>
      [
        0 => '733,17',
        1 => '646.655,94',
      ],
      'cb6996ee154191d7b223e419adc92' =>
      [
        0 => '450,87',
        1 => '151.492,32',
      ],
      'bb379a602596372909acd0ef5824c' =>
      [
        0 => '270,38',
        1 => '251.183,02',
      ],
      '73ee076c830d4c14a2a5c7f8abd0b' =>
      [
        0 => '648,20',
        1 => '476.427,00',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        31 => '324.589,00',
        22 => '347.178,90',
      ],
      'wrapping' =>
      [
        'brutto' => '190.228,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '103,00',
        'netto' => '84,43',
        'vat' => '18,57',
      ],
      'payment' =>
      [
        'brutto' => '1.186.926,52',
        'netto' => '972.890,59',
        'vat' => '214.035,93',
      ],
      'voucher' =>
      [
        'brutto' => '84,00',
      ],
      'totalNetto' => '2.625.147,20',
      'totalBrutto' => '3.296.999,10',
      'grandTotal' => '4.674.172,62',
    ],
  ],
];
