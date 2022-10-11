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

namespace OxidEsales\EshopCommunity\Core;

/**
 * Generates view name for given table name.
 */
class TableViewNameGenerator
{
    /** @var \OxidEsales\Eshop\Core\Config */
    private $config;

    /** @var \OxidEsales\Eshop\Core\Language */
    private $language;

    /**
     * @param \OxidEsales\Eshop\Core\Config   $config
     * @param \OxidEsales\Eshop\Core\Language $language
     */
    public function __construct($config = null, $language = null)
    {
        if (!$config) {
            $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        }
        $this->config = $config;

        if (!$language) {
            $language = \OxidEsales\Eshop\Core\Registry::getLang();
        }
        $this->language = $language;
    }

    /**
     * Return the view name of the given table if a view exists, otherwise the table name itself.
     * Views usage can be disabled with blSkipViewUsage config option in case admin area is not reachable
     * due to broken views, so that they could be regenerated.
     *
     * @param string $table      Table name
     * @param int    $languageId Language id [optional]
     * @param int    $shopId     Shop id, otherwise config->getShopId() is used [optional]
     *
     * @return string
     */
    public function getViewName($table, $languageId = null, $shopId = null)
    {
        $config = $this->getConfig();

        if (!$config->getConfigParam('blSkipViewUsage')) {
            $language = $this->getLanguage();
            $languageId = $languageId !== null ? $languageId : $language->getBaseLanguage();
            $shopId = $shopId !== null ? $shopId : $config->getShopId();
            $isMultiLang = in_array($table, $language->getMultiLangTables());
            $viewSuffix = $this->getViewSuffix($table, $languageId, $shopId, $isMultiLang);

            if ($viewSuffix || (($languageId == -1 || $shopId == -1) && $isMultiLang)) {
                return "oxv_{$table}{$viewSuffix}";
            }
        }

        return $table;
    }

    /**
     * Generates view suffix.
     *
     * @param string $table
     * @param int    $languageId
     * @param int    $shopId
     * @param bool   $isMultiLang
     *
     * @return string
     */
    protected function getViewSuffix($table, $languageId, $shopId, $isMultiLang)
    {
        $viewSuffix = '';
        if ($languageId != -1 && $isMultiLang) {
            $languageAbbreviation = $this->getLanguage()->getLanguageAbbr($languageId);
            $viewSuffix .= "_{$languageAbbreviation}";
        }

        return $viewSuffix;
    }

    /**
     * @return \OxidEsales\Eshop\Core\Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \OxidEsales\Eshop\Core\Language
     */
    protected function getLanguage()
    {
        return $this->language;
    }
}
