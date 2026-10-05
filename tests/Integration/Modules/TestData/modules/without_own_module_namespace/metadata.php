<?php

$sMetadataVersion = '1.1';

/**
 * Module information
 */
$aModule = [
    'id'           => 'without_own_module_namespace',
    'title'        => 'O3-Shop not namespaced test module',
    'description'  => 'Double the price. Show payment error message during checkout.',
    'thumbnail'    => 'module.png',
    'version'      => '1.0.0',
    'author'       => 'OXID eSales AG',
    'extend'      => [
       'payment' => 'oeTest/without_own_module_namespace/Application/Controller/TestModuleTwoPaymentController',
       'oxprice' => 'oeTest/without_own_module_namespace/Application/Model/TestModuleTwoPrice',
    ],
    'files' => [
        'TestModuleTwoModel'  => 'without_own_module_namespace/Application/Model/TestModuleTwoModel.php',
    ],
    'settings' => [
    ],
];
