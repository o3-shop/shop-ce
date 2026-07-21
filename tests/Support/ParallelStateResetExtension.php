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

namespace OxidEsales\EshopCommunity\Tests\Support;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Config;
use OxidEsales\EshopCommunity\Core\Language;
use OxidEsales\EshopCommunity\Core\Utils;
use OxidEsales\EshopCommunity\Core\UtilsView;
use PHPUnit\Runner\BeforeTestHook;

/**
 * Central reset of process-global state that leaks BETWEEN test classes and
 * breaks the parallel (ParaTest WrapperRunner) run.
 *
 * A worker runs many test files back-to-back in ONE long-lived PHP process. The
 * DatabaseRestorer only restores the DB and UnitTestCase::tearDown() only resets
 * the Registry — neither touches the STATIC caches below, so a value set by one
 * test class silently poisons a later one. Which class ends up next to which
 * varies by worker distribution, hence the nondeterministic "straggler" failures.
 *
 * This hook resets those statics before every test so each test starts from a
 * clean-shop baseline (a test that needs a specific currency / SEO prefix sets it
 * itself in setUp/the test body). It is GUARDED to ParaTest workers (TEST_TOKEN
 * set) so the sequential runs — the deterministic default and the coverage gate —
 * behave exactly as before. No production code path uses this class.
 */
final class ParallelStateResetExtension implements BeforeTestHook
{
    public function executeBeforeTest(string $test): void
    {
        // Only inside ParaTest workers; leave sequential runs untouched.
        if (getenv('TEST_TOKEN') === false) {
            return;
        }

        // The shop must be bootstrapped (Registry available). Guard defensively so
        // a reset problem can never itself fail the suite.
        if (!class_exists(Registry::class, false)) {
            return;
        }

        try {
            $this->resetActiveCurrency();
            $this->resetCurrencyPrecision();
            $this->resetLanguageAbbreviations();
            $this->resetSmarty();
        } catch (\Throwable $e) {
            // Best effort — never let the reset itself break a test run.
        }

        // NOTE: SeoEncoder::$_sPrefix/$_sSeparator are intentionally NOT reset
        // here. Nulling them mid-suite re-inits the encoder from config and
        // corrupts in-flight SEO URL generation (regressed ArticleTest::
        // testGetLinkSeoEng). The one known prefix victim
        // (SeoEncoderCategoryTest::testAncodingCategoryNamedAdmin) sets its own
        // prefix instead, which is the correct, localized fix.
    }

    /**
     * Config caches the active currency object in the protected static-per-instance
     * property $_oActCurrencyObject; a leaked non-default currency changes rate and
     * decimal/thousands separators for later tests (ArticleTest::testApplyCurrency,
     * SelectlistTest, the Smarty price/number-format tests).
     */
    private function resetActiveCurrency(): void
    {
        $config = Registry::getConfig();

        $this->nullProperty($config, Config::class, '_oActCurrencyObject');

        // Back to the base currency (also clears the cache and the session var).
        $config->setActShopCurrency(0);
        Registry::getSession()->setVariable('currency', 0);
    }

    /**
     * Utils caches the currency precision in the per-instance property
     * $_iCurPrecision on the Utils SINGLETON, the first time fRound() runs. Every
     * later fRound() then ignores the currency passed to it and rounds to the
     * cached precision — so a test that first rounds with a 2-decimal currency
     * poisons a later test that formats a 3-decimal currency
     * (LangTest::testFormatsCurrencyUsingSimulatedCurrencyObject: 10322.326 came
     * out '10#322~330' instead of '10#322~326'). Nulling it forces fRound() to
     * recompute from the currency in play on the next call.
     */
    private function resetCurrencyPrecision(): void
    {
        $this->nullProperty(Registry::getUtils(), Utils::class, '_iCurPrecision');
    }

    /**
     * Language caches the id=>abbreviation map in the per-instance $_aLangAbbr the
     * first time getLanguageAbbr() runs. If a leaked/empty map is left behind,
     * getLanguageAbbr(0) returns the numeric id '0' instead of 'de', so
     * TableViewNameGenerator::getViewName() builds names like 'oxv_oxshops_0'
     * that don't exist (the real views are 'oxv_oxshops' / 'oxv_oxshops_de') —
     * producing whole-class "view doesn't exist" cascades (ArticleMainTest,
     * VendorTest). Nulling the cache forces a fresh, correct lookup from the
     * current language config on the next call.
     */
    private function resetLanguageAbbreviations(): void
    {
        $this->nullProperty(Registry::getLang(), Language::class, '_aLangAbbr');
    }

    /**
     * UtilsView::$_oSmarty is a STATIC cached Smarty instance. If a test built it
     * without the block-plugin dirs (e.g. the 'oxcontent' block), later tests fail
     * with "unrecognized tag" (EmailUtf8Test). Nulling it forces a clean, lazy
     * rebuild via UtilsView::getSmarty() on next use.
     */
    private function resetSmarty(): void
    {
        $this->nullStaticProperty(UtilsView::class, '_oSmarty');
    }

    private function nullProperty(object $object, string $class, string $property): void
    {
        if (!property_exists($class, $property)) {
            return;
        }
        $reflection = new \ReflectionProperty($class, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, null);
    }

    private function nullStaticProperty(string $class, string $property): void
    {
        if (!property_exists($class, $property)) {
            return;
        }
        $reflection = new \ReflectionProperty($class, $property);
        $reflection->setAccessible(true);
        $reflection->setValue(null);
    }
}
