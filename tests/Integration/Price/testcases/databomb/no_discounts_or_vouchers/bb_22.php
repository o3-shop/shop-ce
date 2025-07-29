<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_22',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '402346f1058bf466db98c10004587',
      'oxprice' => 702.59,
      'oxvat' => 43,
      'amount' => 902,
    ],
    1 =>
    [
      'oxid' => '5159ae17ce6246d4988a9ab1a4fd2',
      'oxprice' => 324.75,
      'oxvat' => 43,
      'amount' => 757,
    ],
    2 =>
    [
      'oxid' => '33937de9dc2fac37533993a77911b',
      'oxprice' => 264.99,
      'oxvat' => 43,
      'amount' => 353,
    ],
    3 =>
    [
      'oxid' => '5cd3679dfb2ed6f5c25c4bbc08592',
      'oxprice' => 382.32,
      'oxvat' => 43,
      'amount' => 697,
    ],
    4 =>
    [
      'oxid' => '24a1faba2b3dcab2c79e94d18de05',
      'oxprice' => 359.33,
      'oxvat' => 43,
      'amount' => 315,
    ],
    5 =>
    [
      'oxid' => 'db64c802b551a803eca11a1371655',
      'oxprice' => 575.45,
      'oxvat' => 43,
      'amount' => 745,
    ],
    6 =>
    [
      'oxid' => '4f0df56c2c80efca0264f040e7488',
      'oxprice' => 709.63,
      'oxvat' => 43,
      'amount' => 146,
    ],
    7 =>
    [
      'oxid' => '27b3ac3c190c28063a3ed7058c821',
      'oxprice' => 164.83,
      'oxvat' => 43,
      'amount' => 973,
    ],
    8 =>
    [
      'oxid' => '89f63609bce9c7b2a4160579e0706',
      'oxprice' => 104.63,
      'oxvat' => 43,
      'amount' => 528,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 92,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '402346f1058bf466db98c10004587',
          1 => '5159ae17ce6246d4988a9ab1a4fd2',
          2 => '33937de9dc2fac37533993a77911b',
          3 => '5cd3679dfb2ed6f5c25c4bbc08592',
          4 => '24a1faba2b3dcab2c79e94d18de05',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 44,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '402346f1058bf466db98c10004587',
          1 => '5159ae17ce6246d4988a9ab1a4fd2',
          2 => '33937de9dc2fac37533993a77911b',
          3 => '5cd3679dfb2ed6f5c25c4bbc08592',
          4 => '24a1faba2b3dcab2c79e94d18de05',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 39,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '402346f1058bf466db98c10004587',
          1 => '5159ae17ce6246d4988a9ab1a4fd2',
          2 => '33937de9dc2fac37533993a77911b',
          3 => '5cd3679dfb2ed6f5c25c4bbc08592',
          4 => '24a1faba2b3dcab2c79e94d18de05',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 45,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 34,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 84,
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
        'oxaddsum' => 55,
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
        'oxaddsum' => 30,
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
        'oxaddsum' => 54,
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
      '402346f1058bf466db98c10004587' =>
      [
        0 => '702,59',
        1 => '633.736,18',
      ],
      '5159ae17ce6246d4988a9ab1a4fd2' =>
      [
        0 => '324,75',
        1 => '245.835,75',
      ],
      '33937de9dc2fac37533993a77911b' =>
      [
        0 => '264,99',
        1 => '93.541,47',
      ],
      '5cd3679dfb2ed6f5c25c4bbc08592' =>
      [
        0 => '382,32',
        1 => '266.477,04',
      ],
      '24a1faba2b3dcab2c79e94d18de05' =>
      [
        0 => '359,33',
        1 => '113.188,95',
      ],
      'db64c802b551a803eca11a1371655' =>
      [
        0 => '575,45',
        1 => '428.710,25',
      ],
      '4f0df56c2c80efca0264f040e7488' =>
      [
        0 => '709,63',
        1 => '103.605,98',
      ],
      '27b3ac3c190c28063a3ed7058c821' =>
      [
        0 => '164,83',
        1 => '160.379,59',
      ],
      '89f63609bce9c7b2a4160579e0706' =>
      [
        0 => '104,63',
        1 => '55.244,64',
      ],
    ],
    'totals' =>
    [
      'vats' =>
      [
        43 => '631.684,99',
      ],
      'wrapping' =>
      [
        'brutto' => '117.936,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '630.324,96',
        'netto' => '440.786,69',
        'vat' => '189.538,27',
      ],
      'payment' =>
      [
        'brutto' => '45,00',
        'netto' => '31,47',
        'vat' => '13,53',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '1.469.034,86',
      'totalBrutto' => '2.100.719,85',
      'grandTotal' => '2.849.025,81',
    ],
  ],
];
