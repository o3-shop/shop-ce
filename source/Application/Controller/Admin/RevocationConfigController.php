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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAsset;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAssetHintTranslator;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator;

/**
 * §356a BGB electronic revocation feature — admin configuration page.
 *
 * Owns the four `oxconfig` settings the operator interacts with:
 *   - `blShowRevocationForm`        (bool)  — feature on/off
 *   - `blRevocationRequireLogin`    (bool)  — anonymous access
 *   - `blRevocationNotifyOperator`  (bool)  — operator notifications
 *   - `sRevocationOperatorEmail`    (str)   — recipient (cross-field-validated)
 *
 * Lives as a dedicated page (not a fragment of `ShopConfiguration`) so the
 * cross-field validation rule and the future template-presence gate (phase
 * 8) stay scoped to revocation logic. `RevocationConfigController` shows
 * up under "Customer Info → Revocations" in the admin nav (wired in
 * phase 9, alongside the list/detail views).
 *
 * Save semantics — **all-or-nothing** per spec / D11:
 *   - If `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail`
 *     is empty or fails `FILTER_VALIDATE_EMAIL`, the entire form save is
 *     rejected. No row is updated. The form re-renders with submitted
 *     values pre-filled (per `feedback_form-input-preservation.md`).
 *   - Otherwise all four values are persisted atomically.
 *
 * The runtime-side recipient fallback chain (`oxshops.oxorderemail` when
 * `sRevocationOperatorEmail` is empty) is intentionally NOT applied here:
 * once the operator interacts with the form, we want them to consciously
 * specify the recipient instead of silently relying on the implicit
 * fallback (asymmetric rule documented in design D5).
 */
class RevocationConfigController extends AdminDetailsController
{
    /** @var string */
    protected $_sThisTemplate = 'revocation_config.tpl';

    private const FIELDS_BOOL = [
        'blShowRevocationForm',
        'blRevocationRequireLogin',
        'blRevocationNotifyOperator',
    ];

    private const FIELD_OPERATOR_EMAIL = 'sRevocationOperatorEmail';

    /** @var array<string,string> per-field validation errors set by save() on rejection */
    private array $validationErrors = [];

    /** @var array<string,mixed> values the operator submitted (used to re-render on rejection) */
    private array $submittedValues = [];

    /** @var MissingAsset[] surfaced by the template-presence gate on rejection */
    private array $missingAssets = [];

    /** @var RevocationTemplateValidator|null lazy-resolved; settable for tests */
    private ?RevocationTemplateValidator $templateValidator = null;

    /**
     * @return string admin template name to render
     */
    public function render()
    {
        $config = Registry::getConfig();

        // On the rejection path, the submitted values supplied by save() take
        // precedence so the operator's typing is preserved across the rejection.
        // On a fresh GET, fall back to the persisted oxconfig values.
        $bag = $this->submittedValues !== [] ? $this->submittedValues : [
            'blShowRevocationForm'        => (bool) $config->getConfigParam('blShowRevocationForm', false),
            'blRevocationRequireLogin'    => (bool) $config->getConfigParam('blRevocationRequireLogin', false),
            'blRevocationNotifyOperator'  => (bool) $config->getConfigParam('blRevocationNotifyOperator', true),
            'sRevocationOperatorEmail'    => (string) $config->getConfigParam('sRevocationOperatorEmail', ''),
        ];

        $this->_aViewData['revocation'] = $bag;
        $this->_aViewData['revocationErrors'] = $this->validationErrors;
        $this->_aViewData['revocationMissingAssets'] = array_map(
            static fn (MissingAsset $a) => [
                'type' => $a->getAssetType(),
                'path' => $a->getExpectedPath(),
                'lang' => $a->getLangId(),
                'hint' => $a->getRemediationHint(),
            ],
            $this->missingAssets
        );

        return parent::render();
    }

    /**
     * Save handler. All-or-nothing per the cross-field rule.
     *
     * @return void
     */
    public function save()
    {
        $request = Registry::getRequest();
        $submitted = [
            'blShowRevocationForm'       => (bool) $request->getRequestParameter('blShowRevocationForm', 0),
            'blRevocationRequireLogin'   => (bool) $request->getRequestParameter('blRevocationRequireLogin', 0),
            'blRevocationNotifyOperator' => (bool) $request->getRequestParameter('blRevocationNotifyOperator', 0),
            'sRevocationOperatorEmail'   => trim((string) $request->getRequestParameter('sRevocationOperatorEmail', '')),
        ];
        $this->submittedValues = $submitted;

        if (!$this->isCrossFieldRuleSatisfied($submitted)) {
            // form-input-preservation.md — render() reads $this->submittedValues.
            return;
        }

        if ($submitted['blShowRevocationForm'] && !$this->templatePresenceGatePasses()) {
            // Same all-or-nothing rule as the cross-field check: nothing
            // is persisted and the form re-renders with submitted values.
            return;
        }

        $config = Registry::getConfig();
        foreach (self::FIELDS_BOOL as $key) {
            $config->saveShopConfVar('bool', $key, $submitted[$key] ? '1' : '');
        }
        $config->saveShopConfVar('str', self::FIELD_OPERATOR_EMAIL, $submitted[self::FIELD_OPERATOR_EMAIL]);
    }

    /**
     * Whether the entered values pass the cross-field rule.
     * Side effect: populates {@see $validationErrors} and adds an admin-facing
     * error display on failure.
     *
     * @param array<string,mixed> $submitted
     */
    private function isCrossFieldRuleSatisfied(array $submitted): bool
    {
        $notify = (bool) $submitted['blRevocationNotifyOperator'];
        $email = trim((string) $submitted[self::FIELD_OPERATOR_EMAIL]);

        if (!$notify) {
            return true;
        }

        if ($email === '') {
            return $this->fail(self::FIELD_OPERATOR_EMAIL, 'O3_REVOCATION_VALIDATION_OPERATOR_EMAIL_REQUIRED');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail(self::FIELD_OPERATOR_EMAIL, 'O3_REVOCATION_VALIDATION_EMAIL_FORMAT');
        }
        return true;
    }

    private function fail(string $field, string $translationKey): bool
    {
        $this->validationErrors[$field] = $translationKey;

        $error = oxNew(DisplayError::class);
        $error->setMessage($translationKey);
        Registry::getUtilsView()->addErrorToDisplay($error);

        return false;
    }

    /**
     * Template-presence gate (spec D11). Runs only when the operator is
     * activating the feature (or saving the form while it is on). Reports
     * each missing template / translation key as an admin error and
     * populates {@see $missingAssets} for the template re-render.
     */
    private function templatePresenceGatePasses(): bool
    {
        $config = Registry::getConfig();
        $shopId = (int) $config->getShopId();
        $themeId = (string) ($config->getConfigParam('sCustomTheme') ?: $config->getConfigParam('sTheme'));
        if ($themeId === '') {
            $themeId = 'wave';
        }

        $activeLangIds = [];
        $params = $config->getConfigParam('aLanguageParams');
        if (is_array($params)) {
            foreach ($params as $entry) {
                if (is_array($entry) && isset($entry['active']) && (int) $entry['active'] === 1) {
                    $activeLangIds[] = isset($entry['baseId']) ? (int) $entry['baseId'] : 0;
                }
            }
        }
        if ($activeLangIds === []) {
            $activeLangIds = [0];
        }

        $this->missingAssets = $this->getTemplateValidator()->validate($shopId, $themeId, $activeLangIds);
        if ($this->missingAssets === []) {
            return true;
        }

        // Surface each missing asset as an admin error so the operator can
        // act on the list. Keep the message short — the template renders
        // the full per-asset list with remediation hints.
        foreach ($this->missingAssets as $asset) {
            $error = oxNew(DisplayError::class);
            $error->setMessage(MissingAssetHintTranslator::translate($asset, Registry::getLang()));
            Registry::getUtilsView()->addErrorToDisplay($error);
        }
        return false;
    }

    private function getTemplateValidator(): RevocationTemplateValidator
    {
        if ($this->templateValidator === null) {
            $this->templateValidator = ContainerFactory::getInstance()
                ->getContainer()
                ->get(RevocationTemplateValidator::class);
        }
        return $this->templateValidator;
    }

    /**
     * Test seam — inject a mocked validator without touching the DI container.
     */
    public function setTemplateValidator(RevocationTemplateValidator $validator): void
    {
        $this->templateValidator = $validator;
    }

    /**
     * Test seam: assert internal state after a save() call without
     * touching the DB.
     *
     * @return array<string,string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Test seam: assert internal state after a save() call.
     *
     * @return array<string,mixed>
     */
    public function getSubmittedValues(): array
    {
        return $this->submittedValues;
    }

    /**
     * @return MissingAsset[]
     */
    public function getMissingAssets(): array
    {
        return $this->missingAssets;
    }
}
