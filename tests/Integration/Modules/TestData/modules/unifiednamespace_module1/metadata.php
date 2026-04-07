<?php

/**
 *
 * @category      module
 * @package       moduleone
 * @author        John Doe
 * @link          www.johndoe.com
 * @copyright (C) John Doe 20162016
 */

/**
 * Metadata version
 */
$sMetadataVersion = '1.1';

/**
 * Module information
 */
$aModule = [
    'id'          => 'unifiednamespace_module1',
    'title'       => [
        'de' => 'OXID eSales example module 1',
        'en' => 'OXID eSales example module 1',
    ],
    'description' => [
        'de' => 'This module overrides ContentController::getTitle()',
        'en' => 'This module overrides ContentController::getTitle()',
    ],
    'version'     => '1.0.0',
    'author'      => 'John Doe',
    'url'         => 'www.johndoe.com',
    'email'       => 'john@doe.com',
    'extend'      => [
        'content' => 'oeTest/unifiednamespace_module1/Controller/Test1ContentController',
    ],
    'files'       => [
        'Test1Content' => 'oeTest/unifiednamespace_module1/Model/Test1Content.php',
    ],
    'templates'   => [],
    'blocks'      => [],
    'settings'    => [],
    'events'      => [],
];
