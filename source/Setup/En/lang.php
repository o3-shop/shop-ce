<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

$aLang = [

'charset'                                       => 'UTF-8',
'HEADER_META_MAIN_TITLE'                        => 'O3-Shop installation wizard',
'HEADER_TEXT_SETUP_NOT_RUNS_AUTOMATICLY'        => 'If setup does not continue in a few seconds, please click ',
'FOOTER_OXID_ESALES'                            => '&copy; O3-Shop 2022 - '.@date("Y").', &copy; OXID eSales AG 2003 - 2022',

'TAB_0_TITLE'                                   => 'System Requirements',
'TAB_1_TITLE'                                   => 'Welcome',
'TAB_2_TITLE'                                   => 'License conditions',
'TAB_3_TITLE'                                   => 'Database',
'TAB_4_TITLE'                                   => 'Directory & login',
'TAB_5_TITLE'                                   => 'License',
'TAB_6_TITLE'                                   => 'Finish',

'TAB_0_DESC'                                    => 'Checking if your system fits the requirements',
'TAB_1_DESC'                                    => 'Welcome to O3-Shop installation wizard',
'TAB_2_DESC'                                    => 'Confirm license conditions',
'TAB_3_DESC'                                    => 'Enter database connection details, test database connection',
'TAB_4_DESC'                                    => 'Configure directories and admin login, update database, run migrations',
'TAB_5_DESC'                                    => 'Apply license key',
'TAB_6_DESC'                                    => 'Installation succeeded',

'HERE'                                          => 'here',

'ERROR_NOT_AVAILABLE'                           => 'ERROR: %s not found!',
'ERROR_NOT_WRITABLE'                            => 'ERROR: %s not writeable!',
'ERROR_DB_CONNECT'                              => 'ERROR: No database connection possible!',
'ERROR_OPENING_SQL_FILE'                        => 'ERROR: Cannot open SQL file %s!',
'ERROR_FILL_ALL_FIELDS'                         => 'ERROR: Please fill in all needed fields!',
'ERROR_COULD_NOT_CREATE_DB'                     => 'ERROR: Database not available and also cannot be created!',
'ERROR_DB_ALREADY_EXISTS'                       => 'ERROR: Seems there is already O3-Shop installed in database %s. Please delete it prior continuing!',
'ERROR_BAD_SQL'                                 => 'ERROR: Issue while inserting this SQL statements: ',
'ERROR_BAD_DEMODATA'                            => 'ERROR: Issue while inserting this SQL statements: ',
'ERROR_NO_DEMODATA_INSTALLED'                   => 'ERROR: Demo data package not installed. Please install the demo data first.',
'NOTICE_NO_DEMODATA_INSTALLED'                  => 'Demo data package not installed. Please install the demo data first. See the Installation section in the README.md file for details.',
'ERROR_CONFIG_FILE_IS_NOT_WRITABLE'             => 'ERROR: %s/config.inc.php' . ' not writeable!',
'ERROR_COULD_NOT_OPEN_CONFIG_FILE'              => 'Could not open %s for reading! Please consult our FAQ, forum or contact o3 Support staff!',
'ERROR_COULD_NOT_FIND_FILE'                     => 'Setup could not find %s !',
'ERROR_COULD_NOT_READ_FILE'                     => 'Setup could not open %s for reading!',
'ERROR_COULD_NOT_WRITE_TO_FILE'                 => 'Setup could not write to %s!',
'ERROR_PASSWORD_TOO_SHORT'                      => 'Password is too short!',
'ERROR_PASSWORDS_DO_NOT_MATCH'                  => 'Passwords do not match!',
'ERROR_USER_NAME_DOES_NOT_MATCH_PATTERN'        => 'Please enter a valid e-mail address!',
'ERROR_MYSQL_VERSION_DOES_NOT_FIT_REQUIREMENTS' => 'The installed database version does not fit system requirements!',

'ERROR_VIEWS_CANT_CREATE'                       => 'ERROR: Can\'t create views. Please check your database user privileges.',
'ERROR_VIEWS_CANT_SELECT'                       => 'ERROR: Can\'t select from views. Please check your database user privileges.',
'ERROR_VIEWS_CANT_DROP'                         => 'ERROR: Can\'t drop views. Please check your database user privileges.',

'MOD_PHP_EXTENNSIONS'                           => 'PHP extensions',
'MOD_PHP_CONFIG'                                => 'PHP configuration',
'MOD_SERVER_CONFIG'                             => 'Server configuration',

'MOD_MOD_REWRITE'                               => 'Apache mod_rewrite module',
'MOD_SERVER_PERMISSIONS'                        => 'Files/folders access rights',
'MOD_ALLOW_URL_FOPEN'                           => 'allow_url_fopen and fsockopen to port 80',
'MOD_PHP4_COMPAT'                               => 'Zend compatibility mode must be off',
// @deprecated since v.6.5.1 (2020-02-12);
'MOD_PHP_VERSION'                               => 'PHP version from 7.4 to 8.2',
// END deprecated
'MOD_REQUEST_URI'                               => 'REQUEST_URI set',
'MOD_LIB_XML2'                                  => 'LIB XML2',
'MOD_PHP_XML'                                   => 'DOM',
'MOD_J_SON'                                     => 'JSON',
'MOD_I_CONV'                                    => 'ICONV',
'MOD_TOKENIZER'                                 => 'Tokenizer',
'MOD_BC_MATH'                                   => 'BCMath',
'MOD_MYSQL_CONNECT'                             => 'PDO_MySQL',
'MOD_MYSQL_VERSION'                             => 'MySQL version 5.5, 5.7, 8.0 or MariaDB 10',
'MOD_GD_INFO'                                   => 'GDlib v2 incl. JPEG support',
'MOD_INI_SET'                                   => 'ini_set allowed',
'MOD_REGISTER_GLOBALS'                          => 'register_globals must be off',
'MOD_MAGIC_QUOTES_GPC'                          => 'magic_quotes_gpc must be off',
'MOD_ZEND_OPTIMIZER'                            => 'Zend Guard Loader installed',
'MOD_ZEND_PLATFORM_OR_SERVER'                   => 'Zend Platform or Zend Server installed',
'MOD_MB_STRING'                                 => 'mbstring',
'MOD_CURL'                                      => 'cURL',
'MOD_OPEN_SSL'                                  => 'OpenSSL',
'MOD_SOAP'                                      => 'SOAP',
'MOD_UNICODE_SUPPORT'                           => 'UTF-8 support',
'MOD_FILE_UPLOADS'                              => 'File uploads are enabled (file_uploads)',
'MOD_BUG53632'                                  => 'Possible issues on server due to PHP Bugs',
'MOD_SESSION_AUTOSTART'                         => 'session.auto_start must be off',
'MOD_MEMORY_LIMIT'                              => 'PHP Memory limit (min. 32MB, 60MB recommended)',

'STEP_0_ERROR_TEXT'                             => 'Your system does not fit system requirements',
'STEP_0_ERROR_URL'                              => 'https://docs.o3-shop.com/eshop/en/latest/installation/new-installation/server-and-system-requirements.html',
'STEP_0_TEXT'                                   => '<ul class="req">' .
                                                   '<li class="pass"> - Your system fits the requirement.</li>' .
                                                   '<li class="pmin"> - The requirement is not or only partly fit. The O3-Shop will work anyway and can be installed.</li>' .
                                                   '<li class="fail"> - Your system doesn\'t fit the requirement. The O3-Shop will not work without it and cannot be installed.</li>' .
                                                   '<li class="null"> - The requirement could  not be checked.' .
                                                   '</ul>',
'STEP_0_DESC'                                   => 'In this step we check if your system fits the requirements:',
'STEP_0_TITLE'                                  => 'System requirements check',

'STEP_1_TITLE'                                  => 'Welcome',
'STEP_1_DESC'                                   => 'Welcome to installation wizard of O3-Shop',
'STEP_1_TEXT'                                   => 'Please read carefully the following instructions to guarantee a smooth installation.
                                                    Wishes for best success in using your O3-Shop by',
'STEP_1_ADDRESS'                                => 'your O3-Shop community<br>',
'BUTTON_BEGIN_INSTALL'                          => 'Start installation',
'BUTTON_PROCEED_INSTALL'                        => 'Proceed with setup',

'STEP_2_TITLE'                                  => 'License conditions',
'BUTTON_RADIO_LICENCE_ACCEPT'                   => 'I accept license conditions.',
'BUTTON_RADIO_LICENCE_NOT_ACCEPT'               => 'I do not accept license conditions.',
'BUTTON_LICENCE'                                => 'Continue',

'STEP_3_TITLE'                                  => 'Database',
'STEP_3_DESC'                                   => 'Database is going to be created and needed tables are written. Please provide some information:',
'STEP_3_DB_HOSTNAME'                            => 'Database server hostname or IP address',
'STEP_3_DB_PORT'                                => 'Database server TCP Port',
'STEP_3_DB_USER_NAME'                           => 'Database username',
'STEP_3_DB_PASSWORD'                            => 'Database password',
'STEP_3_DB_PASSWORD_SHOW'                       => 'Show password',
'STEP_3_DB_DATABSE_NAME'                        => 'Database name',
'STEP_3_DB_DEMODATA'                            => 'Demodata',
'STEP_3_CREATE_DB_WHEN_NO_DB_FOUND'             => 'If database does not exist, it\'s going to be created',
'BUTTON_RADIO_INSTALL_DB_DEMO'                  => 'Install demodata',
'BUTTON_RADIO_NOT_INSTALL_DB_DEMO'              => 'Do <strong>not</strong> install demodata',
'BUTTON_DB_CREATE'                              => 'Create database now',

'STEP_3_1_TITLE'                                => 'Database - being created ...',
'STEP_3_1_DB_CONNECT_IS_OK'                     => 'Database connection successfully tested ...',
'STEP_3_1_DB_CREATE_IS_OK'                      => 'Database %s successfully created ...',

'STEP_4_TITLE'                                  => 'Setting up O3-Shop directories and URL',
'STEP_4_DESC'                                   => 'Please provide necessary data for running O3-Shop:',
'STEP_4_SHOP_URL'                               => 'Shop URL',
'STEP_4_SHOP_DIR'                               => 'Directory for O3-Shop',
'STEP_4_SHOP_TMP_DIR'                           => 'Directory for temporary data',
'STEP_4_ADMIN_LOGIN_NAME'                       => 'Administrator e-mail (used as login name)',
'STEP_4_ADMIN_PASS'                             => 'Administrator password',
'STEP_4_ADMIN_PASS_CONFIRM'                     => 'Confirm Administrator password',
'STEP_4_ADMIN_PASS_MINCHARS'                    => 'freely selectable, min. 6 chars',

'STEP_4_1_TITLE'                                => 'Directories - being created ...',
'STEP_4_1_DATA_WAS_WRITTEN'                     => 'Check and writing data successful. Please wait ...',
'BUTTON_WRITE_DATA'                             => 'Save and continue',

'STEP_4_2_TITLE'                                => 'Creating database tables ...',
'STEP_4_2_OVERWRITE_DB'                         => 'If you want to overwrite all existing data and install anyway click ',
'STEP_4_2_NOT_RECOMMENDED_MYSQL_VERSION'        => 'If you want to install anyway click ',
'STEP_4_2_UPDATING_DATABASE'                    => 'Database successfully updated. Please wait ...',

'STEP_6_TITLE'                                  => 'O3-Shop successfully installed',
'STEP_6_DESC'                                   => 'Your O3-Shop has been installed successfully.',
'STEP_6_LINK_TO_SHOP'                           => 'Continue to your O3-Shop',
'STEP_6_LINK_TO_SHOP_ADMIN_AREA'                => 'Continue to your O3-Shop admin interface',
'STEP_6_TO_SHOP'                                => 'To Shop',
'STEP_6_TO_SHOP_ADMIN'                          => 'To admin interface',

'ATTENTION'                                     => 'Attention, important',
'SETUP_DIR_DELETE_NOTICE'                       => 'Due to security reasons remove setup directory if not yet done during installation.',
'SETUP_CONFIG_PERMISSIONS'                      => 'Due to security reasons put your config.inc.php file to read-only mode!',

'SELECT_SETUP_LANG'                             => 'Installation language',
'SELECT_PLEASE_CHOOSE'                          => 'Please choose',
'SELECT_DELIVERY_COUNTRY'                       => 'Main delivery country',
'SELECT_DELIVERY_COUNTRY_HINT'                  => 'If needed, activate easily more delivery countries in admin.',
'SELECT_SHOP_LANG'                              => 'Shop language',
'SELECT_SHOP_LANG_HINT'                         => 'If needed, activate easily more languages in admin.',
'SELECT_SETUP_LANG_SUBMIT'                      => 'Select',
'PRIVACY_POLICY'                                => 'privacy statements',

'ERROR_SETUP_CANCELLED'                         => 'Setup has been cancelled because you didn\'t accept the license conditions.',
'BUTTON_START_INSTALL'                          => 'Restart setup',

'EXTERNAL_COMMAND_ERROR_1'                      => 'Error while executing command \'%s\'. Return code: \'%d\'.',
'EXTERNAL_COMMAND_ERROR_2'                      => 'The command returns the following message:',
];
