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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Theme;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAssetHintTranslator;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator;

/**
 * Admin article main deliveryset manager.
 * There is possibility to change deliveryset name, article, user etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main Sets.
 */
class ThemeMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates deliveryset category tree,
     * passes data to Smarty engine and returns name of template file "deliveryset_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        $soxId = $this->getEditObjectId();

        $oTheme = oxNew(Theme::class);

        if (!$soxId) {
            $soxId = $oTheme->getActiveThemeId();
        }

        if ($oTheme->load($soxId)) {
            $this->_aViewData['oTheme'] = $oTheme;
        } else {
            Registry::getUtilsView()->addErrorToDisplay(oxNew(StandardException::class, 'EXCEPTION_THEME_NOT_LOADED'));
        }

        parent::render();

        if ($this->themeInConfigFile()) {
            Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_THEME_SHOULD_BE_ONLY_IN_DATABASE');
        }

        return 'theme_main.tpl';
    }

    /**
     * Check if theme config is in config file.
     *
     * @return bool
     */
    public function themeInConfigFile()
    {
        $blThemeSet = isset(Registry::getConfig()->sTheme);
        $blCustomThemeSet = isset(Registry::getConfig()->sCustomTheme);

        return ($blThemeSet || $blCustomThemeSet);
    }

    /**
     * Set theme
     *
     * @return void
     */
    public function setTheme()
    {
        $sTheme = $this->getEditObjectId();
        /** @var Theme $oTheme */
        $oTheme = oxNew(Theme::class);
        if (!$oTheme->load($sTheme)) {
            Registry::getUtilsView()->addErrorToDisplay(oxNew(StandardException::class, 'EXCEPTION_THEME_NOT_LOADED'));

            return;
        }

        // §356a BGB template-presence gate (issue #99). When the operator
        // switches to a different theme while the revocation form is
        // enabled, refuse the activation if the prospective theme is
        // missing revocation assets (templates / translations) — sibling
        // guard to phase 8.1 (revocation config screen) and 8.2
        // (LanguageMain). Each missing asset's remediation hint surfaces
        // through the standard admin error display so the operator sees
        // the concrete list of files to add to the new theme.
        if (!$this->revocationActivationGatePasses($sTheme)) {
            return;
        }

        try {
            $oTheme->activate();
            $this->resetContentCache();
        } catch (StandardException $oEx) {
            Registry::getUtilsView()->addErrorToDisplay($oEx);
            $oEx->debugOut();
        }
    }

    /**
     * §356a template-presence gate for theme activation (phase 8.3).
     *
     * Returns true (proceed with activation) when:
     *   - the revocation feature is off, OR
     *   - the validator finds no missing revocation assets in the
     *     prospective theme for any currently-active language.
     *
     * Returns false (reject) when missing assets exist. Side effect on
     * rejection: each missing asset's remediation hint is pushed to the
     * admin error display.
     */
    protected function revocationActivationGatePasses(string $themeId): bool
    {
        $config = Registry::getConfig();
        if (!$config->getConfigParam('blShowRevocationForm', false)) {
            return true;
        }

        $shopId = (int) $config->getShopId();
        $activeLangIds = [];
        $params = $config->getConfigParam('aLanguageParams');
        if (is_array($params)) {
            foreach ($params as $entry) {
                if (is_array($entry) && !empty($entry['active'])) {
                    $activeLangIds[] = isset($entry['baseId']) ? (int) $entry['baseId'] : 0;
                }
            }
        }
        if ($activeLangIds === []) {
            $activeLangIds = [0];
        }

        $missing = $this->getRevocationTemplateValidator()->validate($shopId, $themeId, $activeLangIds);
        if ($missing === []) {
            return true;
        }

        foreach ($missing as $asset) {
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('§356a — ' . MissingAssetHintTranslator::translate($asset, Registry::getLang()));
            Registry::getUtilsView()->addErrorToDisplay($oEx);
        }
        return false;
    }

    /** @var RevocationTemplateValidator|null lazy-resolved; settable for tests */
    protected ?RevocationTemplateValidator $revocationTemplateValidator = null;

    /**
     * Test seam — inject a mocked validator without driving the DI container.
     */
    public function setRevocationTemplateValidator(RevocationTemplateValidator $validator): void
    {
        $this->revocationTemplateValidator = $validator;
    }

    protected function getRevocationTemplateValidator(): RevocationTemplateValidator
    {
        if ($this->revocationTemplateValidator === null) {
            $this->revocationTemplateValidator = ContainerFactory::getInstance()
                ->getContainer()
                ->get(RevocationTemplateValidator::class);
        }
        return $this->revocationTemplateValidator;
    }
}
