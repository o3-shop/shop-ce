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

// get configuration data from environment variables (../.env in the default)
require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();
$dotenv->required([
     'O3SHOP_CONF_DBHOST',
     'O3SHOP_CONF_DBPORT',
     'O3SHOP_CONF_DBNAME',
     'O3SHOP_CONF_DBUSER',
     'O3SHOP_CONF_DBPWD',
     'O3SHOP_CONF_SHOPURL',
     'O3SHOP_CONF_SHOPDIR',
     'O3SHOP_CONF_COMPILEDIR'
])->notEmpty();

// Database connection information
$this->dbType    = 'pdo_mysql';
$this->dbCharset = 'utf8';
$this->dbHost    = $_ENV['O3SHOP_CONF_DBHOST'];     // database host name
$this->dbPort    = $_ENV['O3SHOP_CONF_DBPORT'];     // tcp port to which the database is bound
$this->dbName    = $_ENV['O3SHOP_CONF_DBNAME'];     // database name
$this->dbUser    = $_ENV['O3SHOP_CONF_DBUSER'];     // database user name
$this->dbPwd     = $_ENV['O3SHOP_CONF_DBPWD'];      // database user password
$this->dbDriverOptions = [];                        // database driver options
$this->dbUnixSocket = null;                         // unix domain socket, optional
$this->sShopURL     = $_ENV['O3SHOP_CONF_SHOPURL']; // Shop base url, required
$this->sSSLShopURL  = $_ENV['O3SHOP_CONF_SSLSHOPURL'];  // Shop SSL url, optional
$this->sAdminSSLURL = $_ENV['O3SHOP_CONF_ADMINSSLURL']; // Shop Admin SSL url, optional
$this->sShopDir     = $_ENV['O3SHOP_CONF_SHOPDIR'];
$this->sCompileDir  = $_ENV['O3SHOP_CONF_COMPILEDIR'];

/**
 * Force shop edition. Shop edition can still be forced here.
 * Possible options: CE or left empty (will be determined automatically).
 */
$this->edition = '';

// File type whitelist for file upload
$this->aAllowedUploadTypes = array('jpg', 'gif', 'png', 'pdf', 'mp3', 'avi', 'mpg', 'mpeg', 'doc', 'xls', 'ppt');

// Timezone information
date_default_timezone_set('Europe/Berlin');

/**
 * Search engine friendly URL processor.
 * After changing this value, you should rename oxid.php file as well
 * Always leave .php extension here unless you know what you are doing
 *
 * @deprecated (2018-03-05);
 */
$this->sO3SHOPPHP = "o3shop.php";

/**
 * String PSR3 log level Psr\Log\LogLevel
 */
$this->sLogLevel = 'error';

/**
 * Log all modifications performed in Admin
 */
$this->blLogChangesInAdmin = false;

/**
 * Should requests, coming via stdurl and not redirected to seo url be logged to seologs db table?
 * Note: only active if in productive mode, as the Shop in non productive more will always log such urls
 */
$this->blSeoLogging = false;

/**
 * Enable debug mode for template development or bugfixing
 * -1 = Log more messages and throw exceptions on errors (not recommended for production)
 * 0 = off
 * 1 = smarty
 * 3 = smarty
 * 4 = smarty + shoptemplate data
 * 5 = Delivery Cost calculation info
 * 6 = SMTP Debug Messages
 * 8 = display smarty template names (requires /tmp cleanup)
 */
$this->iDebug = $_ENV['O3SHOP_CONF_DEBUG'];

/**
 * Should template blocks be highlighted in frontend?
 * This is mainly intended for module writers in non productive environment
 */
$this->blDebugTemplateBlocks = false;

// Force admin email. Offline warnings are sent with high priority to this address.
$this->sAdminEmail = $_ENV['O3SHOP_CONF_ADMINEMAIL'];

// Defines the time interval in seconds warnings are sent during the shop is offline.
$this->offlineWarningInterval = 60 * 5;

// In case session must be started on the very first user page visit (not only on session required action).
$this->blForceSessionStart = false;

// Use browser cookies to store session id (no sid parameter in URL)
$this->blSessionUseCookies = true;

/**
 * The domain that the cookie is available: array(_SHOP_ID_ => _DOMAIN_);
 * Check setcookie() documentation for more details: http://php.net/manual/de/function.setcookie.php
 */
$this->aCookieDomains = null;

/**
 * The path on the server in which the cookie will be available on: array(_SHOP_ID_ => _PATH_);
 * Check setcookie() documentation for more details: http://php.net/manual/de/function.setcookie.php
 */
$this->aCookiePaths = null;

// List of all Search-Engine Robots
$this->aRobots = array(
    'googlebot',
    'ultraseek',
    'crawl',
    'spider',
    'fireball',
    'robot',
    'slurp',
    'fast',
    'altavista',
    'teoma',
    'msnbot',
    'bingbot',
    'yandex',
    'gigabot',
    'scrubby'
);

// Deactivate Static URL's for these Robots
$this->aRobotsExcept = array();

// IP addresses for which session/cookie id match and user agent change checks are off
$this->aTrustedIPs = array();

/**
 * Works only if basket reservations feature is enabled in admin.
 *
 * The number specifies how many expired basket reservations are
 * cleaned per one request (to the Shop).
 * Cleaning a reservation basically means returning the reserved
 * stock to the articles.
 *
 * Keeping this number too low may cause article stock being returned too
 * slowly, while too high value may have spiking impact on the performance.
 */
$this->iBasketReservationCleanPerRequest = 200;

/**
 * To override FrontendController::$_aUserComponentNames use this array option:
 * array keys are component(class) names and array values defines if component is cacheable (true/false)
 * E.g. array('user_class' => false);
 */
$this->aUserComponentNames = null;

// Additional multi language tables
$this->aMultiLangTables = null;

// Instructs shop that price update is performed by cron (time based job sheduler)
$this->blUseCron = false;

// Do not disable module if class from extension path does not exist.
// @deprecated since v6.3.2 (2018-12-19); This method and config variable will be removed completely.
$this->blDoNotDisableModuleOnError = false;

// Enable temporarily in case you can't access the backend due to broken views
$this->blSkipViewUsage = $_ENV['O3SHOP_CONF_SKIPVIEWUSAGE'];

//Time limit in ms to be notified about slow queries
$this->iDebugSlowQueryTime = 20;

/**
 * Enables Rights and Roles engine
 * 0 - off,
 * 1 - only in admin,
 * 2 - only in shop,
 * 3 - both
 */
$this->blUseRightsRoles = 3;

// Show "Update Views" button in admin
$this->blShowUpdateViews = true;

// If default 30 seconds is not enougth
// @set_time_limit(3000);

/**
 * Database master-slave configuration:
 * aSlaveHosts - array of slave hosts: array('localhost', '10.2.3.12')
 */
$this->aSlaveHosts = null;

// Control the removal of Setup directory
$this->blDelSetupDir = $_ENV['O3SHOP_CONF_DELSETUPDIR'];

/**
 * Needed for backwards compatibility. Do not change the value of this property.
 *
 * @deprecated since v6.0 (2017-05-15); This property will be removed in the future as the shop will always use UTF-8.
 */
$this->iUtfMode = 1;
