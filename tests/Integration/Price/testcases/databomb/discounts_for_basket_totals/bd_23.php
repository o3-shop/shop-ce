<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'databomb_user_23',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => 'a6700d664aaa6da0caec44b9a0f54',
      'oxprice' => 317.67,
      'oxvat' => 22,
      'amount' => 834,
    ],
    1 =>
    [
      'oxid' => '5c04c8ef0baf04f38ae3985059a91',
      'oxprice' => 41.44,
      'oxvat' => 22,
      'amount' => 58,
    ],
    2 =>
    [
      'oxid' => '1037e21102e909f69e1706f07d5a7',
      'oxprice' => 405.06,
      'oxvat' => 27,
      'amount' => 350,
    ],
    3 =>
    [
      'oxid' => 'f51810bd7f4b89977f009ba3a5509',
      'oxprice' => 659.69,
      'oxvat' => 27,
      'amount' => 719,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 4,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    1 =>
    [
      'oxaddsum' => 4,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    2 =>
    [
      'oxaddsum' => 2,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    3 =>
    [
      'oxaddsum' => 8,
      'oxid' => 'bombDiscount_3',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    4 =>
    [
      'oxaddsum' => 5,
      'oxid' => 'bombDiscount_4',
      'oxaddsumtype' => 'abs',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
  ],
  'costs' =>
  [
    'wrapping' =>
    [
      0 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 47,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'a6700d664aaa6da0caec44b9a0f54',
          1 => '5c04c8ef0baf04f38ae3985059a91',
          2 => '1037e21102e909f69e1706f07d5a7',
          3 => 'f51810bd7f4b89977f009ba3a5509',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 18,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'a6700d664aaa6da0caec44b9a0f54',
          1 => '5c04c8ef0baf04f38ae3985059a91',
          2 => '1037e21102e909f69e1706f07d5a7',
          3 => 'f51810bd7f4b89977f009ba3a5509',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 2,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => 'a6700d664aaa6da0caec44b9a0f54',
          1 => '5c04c8ef0baf04f38ae3985059a91',
          2 => '1037e21102e909f69e1706f07d5a7',
          3 => 'f51810bd7f4b89977f009ba3a5509',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 94,
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
        'oxaddsum' => 70,
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
        'oxaddsum' => 66,
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
        'oxaddsum' => 45,
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
        'oxaddsum' => 92,
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
      'a6700d664aaa6da0caec44b9a0f54' =>
      [
        0 => '317,67',
        1 => '264.936,78',
      ],
      '5c04c8ef0baf04f38ae3985059a91' =>
      [
        0 => '41,44',
        1 => '2.403,52',
      ],
      '1037e21102e909f69e1706f07d5a7' =>
      [
        0 => '405,06',
        1 => '141.771,00',
      ],
      'f51810bd7f4b89977f009ba3a5509' =>
      [
        0 => '659,69',
        1 => '474.317,11',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '4,00',
        'bombDiscount_1' => '4,00',
        'bombDiscount_2' => '2,00',
        'bombDiscount_3' => '70.673,47',
        'bombDiscount_4' => '5,00',
      ],
      'vats' =>
      [
        22 => '44.351,42',
        27 => '120.498,91',
      ],
      'wrapping' =>
      [
        'brutto' => '3.922,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '1.793.359,67',
        'netto' => '1.412.094,23',
        'vat' => '381.265,44',
      ],
      'payment' =>
      [
        'brutto' => '2.449.733,63',
        'netto' => '1.928.924,12',
        'vat' => '520.809,51',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '647.889,61',
      'totalBrutto' => '883.428,41',
      'grandTotal' => '5.059.755,24',
    ],
  ],
];
