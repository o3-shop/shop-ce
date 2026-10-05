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
 * Module information - the commented things are not working atm or will not be implemented
 */
$aModule = [
    'id'          => 'unifiednamespace_module3',
    'title'       => [
        'de' => 'OXID eSales example module3',
        'en' => 'OXID eSales example module3',
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
        'Test1Content' => 'oeTest/unifiednamespace_module3/Model/Test3Content',
    ],
    'templates'   => [],
    'blocks'      => [],
    'settings'    => [],
    'events'      => [],
];
