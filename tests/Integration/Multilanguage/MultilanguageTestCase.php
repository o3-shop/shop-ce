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
namespace OxidEsales\EshopCommunity\Tests\Integration\Multilanguage;

use oxRegistry;

require_once __DIR__ . '/Helpers/LanguageMainHelper.php';

abstract class MultilanguageTestCase extends \OxidTestCase
{
    protected $originalLanguageArray = null;
    protected $originalBaseLanguageId = null;
    protected $languageMain = null;

    /**
     * Fixture setUp.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalLanguageArray = $this->getLanguageMain()->_getLanguages();
        $this->originalBaseLanguageId = oxRegistry::getLang()->getBaseLanguage();
    }

    /**
    * Fixture tearDown.
    */
    protected function tearDown(): void
    {
        oxRegistry::getLang()->setBaseLanguage($this->originalBaseLanguageId);
        $this->storeLanguageConfiguration($this->originalLanguageArray);
        $this->updateViews();

        parent::tearDown();
    }

    /**
     * Test helper for test preparation.
     * Add given count of new languages.
     *
     * @param int $count
     *
     * @return int
     */
    protected function prepare($count = 9)
    {
        $languageId = 0;
        for ($i=0;$i<$count;$i++) {
            $languageName = chr(97+$i) . chr(97+$i);
            $languageId = $this->insertLanguage($languageName);
        }
        //we need a fresh instance of language object in registry,
        //otherwise stale data is used for language abbreviations.
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Language::class, null);
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\TableViewNameGenerator::class, null);

        $this->updateViews();

        return $languageId;
    }

    /**
     * Test helper to insert a new language with given id.
     *
     * @param int $languageId
     *
     * @return integer
     */
    protected function insertLanguage($languageId)
    {
        $languages = $this->getLanguageMain()->_getLanguages();
        $baseId = $this->getLanguageMain()->_getAvailableLangBaseId();
        $sort = $baseId*100;

        $languages['params'][$languageId] = array('baseId' => $baseId,
                                                  'active' => 1,
                                                  'sort'   => $sort);

        $languages['lang'][$languageId] = $languageId;
        $languages['urls'][$baseId]     = '';
        $languages['sslUrls'][$baseId]  = '';
        $this->getLanguageMain()->setLanguageData($languages);

        $this->storeLanguageConfiguration($languages);

        if (!$this->getLanguageMain()->_checkMultilangFieldsExistsInDb($languageId)) {
            $this->getLanguageMain()->_addNewMultilangFieldsToDb();
        }

        return $baseId;
    }

    /**
     * Test helper for saving language configuration.
     *
     * @param array $languages
     */
    protected function storeLanguageConfiguration($languages)
    {
        $this->getConfig()->saveShopConfVar('aarr', 'aLanguageParams', $languages['params']);
        $this->getConfig()->saveShopConfVar('aarr', 'aLanguages', $languages['lang']);
        $this->getConfig()->saveShopConfVar('arr', 'aLanguageURLs', $languages['urls']);
        $this->getConfig()->saveShopConfVar('arr', 'aLanguageSSLURLs', $languages['sslUrls']);
    }

    /**
     * Test helper to trigger view update.
     */
    protected function updateViews()
    {
        $oMeta = oxNew('oxDbMetaDataHandler');
        $oMeta->updateViews();
    }

    /**
     * Getter for LanguageMainHelper proxy class.
     *
     * @return object
     */
    protected function getLanguageMain()
    {
        if (is_null($this->languageMain)) {
            $this->languageMain = $this->getProxyClass('LanguageMainHelper');
            $this->languageMain->render();
        }
        return $this->languageMain;
    }
}
