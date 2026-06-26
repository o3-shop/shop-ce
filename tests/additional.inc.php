<?php

$serviceCaller = new \OxidEsales\TestingLibrary\ServiceCaller();
$testConfig = new \OxidEsales\TestingLibrary\TestConfig();

$testDirectory = $testConfig->getEditionTestsPath($testConfig->getShopEdition());
$serviceCaller->setParameter('importSql', '@' . $testDirectory . '/Fixtures/testdata.sql');
$serviceCaller->callService('ShopPreparation', 1);

// Issue #122 phase 3: theme default settings now live only in theme.php and are
// written to the DB by Theme::activate() (the install-time mechanism) — they are
// no longer seeded by initial_data.sql. The testing-library ShopInstaller imports
// only database_schema.sql + initial_data.sql, so activate the configured default
// theme here to seed the theme:<id> oxconfig / oxconfigdisplay rows that tests rely
// on (e.g. aDetailImageSizes in ArticleDetailsTest::testGetPictureGallery). This
// runs once during bootstrap, before the DB baseline dump, so it persists for all
// tests; the bootstrap reinitialises config immediately afterwards.
$theme = oxNew(\OxidEsales\Eshop\Core\Theme::class);
$activeThemeId = $theme->getActiveThemeId() ?: 'o3-theme';
if ($theme->load($activeThemeId)) {
    $theme->activate();
}

define('oxADMIN_LOGIN', oxDb::getDb()->getOne("select OXUSERNAME from oxuser where oxid='oxdefaultadmin'"));
define('oxADMIN_PASSWD', getenv('oxADMIN_PASSWD') ? getenv('oxADMIN_PASSWD') : 'admin');
