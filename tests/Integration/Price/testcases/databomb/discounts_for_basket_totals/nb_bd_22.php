<?php

$aData = [
  'user' =>
  [
    'oxactive' => 1,
    'oxusername' => 'nb_bd_databomb_user_22',
  ],
  'articles' =>
  [
    0 =>
    [
      'oxid' => '87725e19dfedbb8a04111c8d97994',
      'oxprice' => 392.37,
      'oxvat' => 11,
      'amount' => 540,
    ],
    1 =>
    [
      'oxid' => '13c2ccd8d9a81e515af2a0307a761',
      'oxprice' => 751.75,
      'oxvat' => 36,
      'amount' => 531,
    ],
    2 =>
    [
      'oxid' => 'fa66714730502d66c8d7f5eb54cf2',
      'oxprice' => 686.78,
      'oxvat' => 11,
      'amount' => 808,
    ],
    3 =>
    [
      'oxid' => '7d1c754321ddd2201525a1a901035',
      'oxprice' => 90.68,
      'oxvat' => 11,
      'amount' => 88,
    ],
  ],
  'discounts' =>
  [
    0 =>
    [
      'oxaddsum' => 2,
      'oxid' => 'bombDiscount_0',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    1 =>
    [
      'oxaddsum' => 1,
      'oxid' => 'bombDiscount_1',
      'oxaddsumtype' => '%',
      'oxamount' => 1,
      'oxamountto' => 9999999,
      'oxprice' => 1,
      'oxpriceto' => 9999999,
      'oxactive' => 1,
    ],
    2 =>
    [
      'oxaddsum' => 15,
      'oxid' => 'bombDiscount_2',
      'oxaddsumtype' => '%',
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
      'oxaddsumtype' => '%',
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
        'oxprice' => 51,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '87725e19dfedbb8a04111c8d97994',
          1 => '13c2ccd8d9a81e515af2a0307a761',
          2 => 'fa66714730502d66c8d7f5eb54cf2',
        ],
      ],
      1 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 70,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '87725e19dfedbb8a04111c8d97994',
          1 => '13c2ccd8d9a81e515af2a0307a761',
          2 => 'fa66714730502d66c8d7f5eb54cf2',
        ],
      ],
      2 =>
      [
        'oxtype' => 'WRAP',
        'oxprice' => 51,
        'oxactive' => 1,
        'oxarticles' =>
        [
          0 => '87725e19dfedbb8a04111c8d97994',
        ],
      ],
    ],
    'payment' =>
    [
      0 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 33,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      1 =>
      [
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 24,
        'oxactive' => 1,
        'oxchecked' => 1,
        'oxfromamount' => 0,
        'oxtoamount' => 1000000,
      ],
      2 =>
      [
        'oxaddsumtype' => '%',
        'oxaddsum' => 14,
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
        'oxaddsumtype' => 'abs',
        'oxaddsum' => 18,
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
        'oxaddsum' => 10,
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
      '87725e19dfedbb8a04111c8d97994' =>
      [
        0 => '435,53',
        1 => '235.186,20',
      ],
      '13c2ccd8d9a81e515af2a0307a761' =>
      [
        0 => '1.022,38',
        1 => '542.883,78',
      ],
      'fa66714730502d66c8d7f5eb54cf2' =>
      [
        0 => '762,33',
        1 => '615.962,64',
      ],
      '7d1c754321ddd2201525a1a901035' =>
      [
        0 => '100,65',
        1 => '8.857,20',
      ],
    ],
    'totals' =>
    [
      'discounts' =>
      [
        'bombDiscount_0' => '28.057,80',
        'bombDiscount_1' => '13.748,32',
        'bombDiscount_2' => '204.162,56',
        'bombDiscount_3' => '92.553,69',
        'bombDiscount_4' => '53.218,37',
      ],
      'vats' =>
      [
        11 => '61.427,50',
        36 => '103.576,70',
      ],
      'wrapping' =>
      [
        'brutto' => '121.270,00',
        'netto' => false,
        'vat' => false,
      ],
      'delivery' =>
      [
        'brutto' => '36,00',
        'netto' => '32,43',
        'vat' => '3,57',
      ],
      'payment' =>
      [
        'brutto' => '333.691,08',
        'netto' => '300.622,59',
        'vat' => '33.068,49',
      ],
      'voucher' =>
      [
        'brutto' => '0,00',
      ],
      'totalNetto' => '846.144,88',
      'totalBrutto' => '1.402.889,82',
      'grandTotal' => '1.466.146,16',
    ],
  ],
];
