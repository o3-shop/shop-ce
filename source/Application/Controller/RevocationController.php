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

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\O3Revocation;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\AntiSpam\RevocationAntiSpamServiceInterface;
use Throwable;

/**
 * §356a BGB electronic revocation form (issue #99).
 *
 * Three actions, exposed under `?cl=revocation`:
 *
 *   - `render()`  (default) — renders the empty form for any visitor with
 *                  access (per the visibility matrix). The form's submit
 *                  button is labelled "Widerruf bestätigen"; clicking it
 *                  IS the legally-effective declaration per § 356a Abs. 3.
 *   - `submit()`  — `fnc=submit`. Single-POST flow: validate, persist,
 *                   send emails, 303-redirect to the receipt. No separate
 *                   confirmation step (D2).
 *   - `receipt()` — `fnc=receipt`. Acknowledgement page after the redirect.
 *
 * Form input preservation: on any rejection, the same form template is
 * re-rendered with the submitted values bound back via the template-getter
 * methods that read from `Registry::getRequest()`. No HTTP redirect on
 * rejection — losing the user's typing would be unacceptable
 * (`feedback_form-input-preservation.md` in shared memory).
 */
class RevocationController extends FrontendController
{
    /** @var string */
    protected $_sThisTemplate = 'page/revocation/revocation.tpl';

    /** @var string template used by the receipt action */
    protected const TEMPLATE_RECEIPT = 'page/revocation/revocationreceipt.tpl';

    /** @var array<string,string> validation errors keyed by field name, set by submit() on rejection */
    private array $validationErrors = [];

    /** @var RevocationAntiSpamServiceInterface|null lazily resolved from the DI container; settable for tests */
    private ?RevocationAntiSpamServiceInterface $antiSpamService = null;

    /**
     * Default render: validate access (visibility matrix), then render
     * either the empty form or a 404 / login redirect.
     *
     * @return string template name to render
     */
    public function render()
    {
        if (!$this->isFeatureEnabled()) {
            // Feature off: same response as for any non-existent page.
            // Per spec — no leak of "this feature exists but is disabled".
            Registry::getUtils()->handlePageNotFoundError(Registry::getRequest()->getRequestUrl());
            return '';
        }

        if ($this->isLoginRequired() && !$this->isUserLoggedIn()) {
            // Anonymous visitor on a login-required shop: bounce through login.
            // The standard login flow returns the user here on success.
            Registry::getUtils()->redirect(
                $this->getViewConfig()->getSelfLink() . 'cl=account&sourcecl=revocation',
                false,
                302
            );
            return '';
        }

        Registry::getLogger()->info(
            __METHOD__ . ' - Rendering revocation form.'
        );
        return parent::render();
    }

    /**
     * Submit action: token check → anti-spam → validate → persist →
     * send emails → 303 to receipt. On any rejection, falls through to
     * a re-render of the same form template with submitted values
     * preserved via the template-getter methods.
     *
     * Return semantics match the OXID convention used elsewhere
     * (e.g. ContactController::send): null on the happy path (the
     * preceding `redirect()` call exits the request); falsy on
     * rejection so the framework re-renders the same controller's
     * default template.
     *
     * @return null|false
     */
    public function submit()
    {
        $session = Registry::getSession();
        $request = Registry::getRequest();

        if (!$session->checkSessionChallenge()) {
            Registry::getLogger()->warning(
                __METHOD__ . ' - Session challenge token mismatch on revocation submit.'
            );
            $this->addError('form', 'O3_REVOCATION_VALIDATION_SESSION_EXPIRED');
            return false;
        }

        $antiSpam = $this->getAntiSpamService();
        $oxidRequest = oxNew(\OxidEsales\Eshop\Core\Request::class);
        if (!$antiSpam->verify($oxidRequest)) {
            Registry::getLogger()->warning(
                __METHOD__ . ' - Anti-spam rejected revocation submit.'
            );
            $this->addError('form', 'O3_REVOCATION_VALIDATION_SPAM');
            return false;
        }

        $name = trim((string) $request->getRequestParameter('o3rev_name', ''));
        $orderIdent = trim((string) $request->getRequestParameter('o3rev_orderident', ''));
        $email = trim((string) $request->getRequestParameter('o3rev_email', ''));
        $freeText = (string) $request->getRequestParameter('o3rev_freetext', '');

        if ($name === '') {
            $this->addError('o3rev_name', 'O3_REVOCATION_VALIDATION_REQUIRED');
        }
        if ($orderIdent === '') {
            $this->addError('o3rev_orderident', 'O3_REVOCATION_VALIDATION_REQUIRED');
        }
        if ($email === '') {
            $this->addError('o3rev_email', 'O3_REVOCATION_VALIDATION_REQUIRED');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('o3rev_email', 'O3_REVOCATION_VALIDATION_EMAIL_FORMAT');
        }

        if ($this->validationErrors !== []) {
            Registry::getLogger()->warning(
                __METHOD__ . ' - Revocation submit rejected by field validation.'
            );
            $antiSpam->recordFailure($oxidRequest);
            return false;
        }

        Registry::getLogger()->info(
            __METHOD__ . ' - Revocation submit passed validation; persisting.'
        );

        $submission = oxNew(O3Revocation::class);
        $submission->assign([
            'oxshopid' => Registry::getConfig()->getShopId(),
            'oxlang' => (int) Registry::getLang()->getBaseLanguage(),
            'oxname' => $name,
            'oxorderident' => $orderIdent,
            'oxemail' => $email,
            'oxfreetext' => $freeText !== '' ? $freeText : null,
        ]);
        $submission->save();

        Registry::getLogger()->notice(
            __METHOD__ . " - Revocation submission persisted, OXID '" . $submission->getId() . "'."
        );

        $this->dispatchEmails($submission);

        $antiSpam->recordSuccess($oxidRequest);

        Registry::getUtils()->redirect(
            $this->getViewConfig()->getSelfLink() . 'cl=revocation&fnc=receipt',
            false,
            303
        );
        return null;
    }

    /**
     * Receipt page (step 3 of the §356a flow). Renders a generic
     * acknowledgement; safe to navigate to directly (no PII exposed).
     *
     * Action methods called via fnc= must NOT return a template path —
     * the OXID dispatcher (BaseController::_executeNewAction) parses the
     * return value as a "<class>?<params>" redirect target and would try
     * to oxNew("page") here. Just set the active template; the normal
     * render() pass picks it up.
     */
    public function receipt(): void
    {
        $this->_sThisTemplate = self::TEMPLATE_RECEIPT;
    }

    public function getName(): string
    {
        return (string) Registry::getRequest()->getRequestEscapedParameter('o3rev_name', '');
    }

    public function getOrderIdent(): string
    {
        return (string) Registry::getRequest()->getRequestEscapedParameter('o3rev_orderident', '');
    }

    public function getEmail(): string
    {
        return (string) Registry::getRequest()->getRequestEscapedParameter('o3rev_email', '');
    }

    public function getFreeText(): string
    {
        return (string) Registry::getRequest()->getRequestEscapedParameter('o3rev_freetext', '');
    }

    /**
     * @return array<string, string> field-name => translation-key map
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function isLoginRequired(): bool
    {
        return (bool) Registry::getConfig()->getConfigParam('blRevocationRequireLogin', false);
    }

    public function isFeatureEnabled(): bool
    {
        return (bool) Registry::getConfig()->getConfigParam('blShowRevocationForm', false);
    }

    private function isUserLoggedIn(): bool
    {
        $user = $this->getUser();
        return $user !== false && $user !== null && $user->getId() !== null;
    }

    private function addError(string $field, string $translationKey): void
    {
        $this->validationErrors[$field] = $translationKey;
    }

    /**
     * Send the customer confirmation email (legally required) and the
     * operator notification email (best-effort). Failures are surfaced
     * via the persistence flag and the log; the consumer-facing flow
     * is never blocked on a synchronous email failure.
     *
     * The actual `Core\Email::sendRevocationEmailTo*()` methods are
     * delivered in phase 5; this helper isolates the call site so
     * phase 4 has a stable controller surface to test against.
     */
    private function dispatchEmails(O3Revocation $submission): void
    {
        try {
            $customerSent = $this->sendCustomerEmail($submission);
            if (!$customerSent) {
                $submission->markSendFailed();
                $submission->save();
                Registry::getLogger()->error(
                    __METHOD__ . " - Customer revocation email send failed for submission OXID '"
                    . $submission->getId() . "'."
                );
            }
        } catch (Throwable $e) {
            Registry::getLogger()->error(
                __METHOD__ . " - Customer revocation email threw for submission OXID '"
                . $submission->getId() . "': '" . $e->getMessage() . "'."
            );
        }

        if (!$this->isOperatorNotificationEnabled()) {
            return;
        }

        try {
            $operatorSent = $this->sendOperatorEmail($submission);
            if (!$operatorSent) {
                Registry::getLogger()->error(
                    __METHOD__ . " - Operator revocation email send failed for submission OXID '"
                    . $submission->getId() . "'."
                );
            }
        } catch (Throwable $e) {
            Registry::getLogger()->error(
                __METHOD__ . " - Operator revocation email threw for submission OXID '"
                . $submission->getId() . "': '" . $e->getMessage() . "'."
            );
        }
    }

    private function isOperatorNotificationEnabled(): bool
    {
        return (bool) Registry::getConfig()->getConfigParam('blRevocationNotifyOperator', true);
    }

    /**
     * Phase-5 seam. Delegates to `Core\Email::sendRevocationEmailToCustomer()`
     * which is added in that phase; the controller surface stays stable.
     */
    private function sendCustomerEmail(O3Revocation $submission): bool
    {
        $mailer = Registry::get(\OxidEsales\Eshop\Core\Email::class);
        if (method_exists($mailer, 'sendRevocationEmailToCustomer')) {
            return (bool) $mailer->sendRevocationEmailToCustomer($submission);
        }
        return false;
    }

    /**
     * Phase-5 seam. Delegates to `Core\Email::sendRevocationEmailToOperator()`.
     */
    private function sendOperatorEmail(O3Revocation $submission): bool
    {
        $mailer = Registry::get(\OxidEsales\Eshop\Core\Email::class);
        if (method_exists($mailer, 'sendRevocationEmailToOperator')) {
            return (bool) $mailer->sendRevocationEmailToOperator($submission);
        }
        return false;
    }

    /**
     * Setter for tests / future ad-hoc overrides. Production code goes
     * through the lazy container resolution in {@see getAntiSpamService()}.
     */
    public function setAntiSpamService(RevocationAntiSpamServiceInterface $service): void
    {
        $this->antiSpamService = $service;
    }

    private function getAntiSpamService(): RevocationAntiSpamServiceInterface
    {
        if ($this->antiSpamService === null) {
            $this->antiSpamService = $this->getContainer()->get(RevocationAntiSpamServiceInterface::class);
        }
        return $this->antiSpamService;
    }
}
