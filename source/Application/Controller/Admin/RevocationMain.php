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
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\O3Revocation;
use Throwable;

/**
 * §356a BGB electronic revocation feature — admin detail view.
 *
 * Read-only display of one submission's persisted fields, plus two
 * admin actions:
 *
 *   - **Resend confirmation** — re-attempts the customer email. The
 *     `OXSUBMITTED` (legal time of receipt) is left untouched per
 *     the spec immutability invariant; only `OXTIMESTAMP` (housekeeping)
 *     and the `OXSENDFAILED` flag move. Successful resend clears the
 *     "send failed" indicator surfaced in the list.
 *
 *   - **Manual delete** — single-row delete with one `NOTICE` audit
 *     log entry naming the submission OXID and the admin user OXID.
 *     Operators perform retention manually under their own privacy
 *     policy; the codebase ships nothing scheduled or auto-purging
 *     (spec "No automatic deletion of submissions").
 */
class RevocationMain extends AdminDetailsController
{
    /** @var string */
    protected $_sThisTemplate = 'revocation_main.tpl';

    /**
     * Default render: load the submission identified by `oxid` query
     * param into view data so the template can display the persisted
     * fields read-only.
     *
     * @return string template name
     */
    public function render()
    {
        $oxid = $this->getEditObjectId();
        if ($oxid && $oxid !== '-1') {
            $submission = oxNew(O3Revocation::class);
            if ($submission->load($oxid)) {
                $this->_aViewData['edit'] = $submission;
            }
        }
        return parent::render();
    }

    /**
     * Resend the customer confirmation email. OXSUBMITTED stays frozen
     * (write-once invariant); OXSENDFAILED is cleared on success.
     *
     * @return void
     */
    public function resend(): void
    {
        $submission = $this->loadEditedSubmission();
        if ($submission === null) {
            return;
        }

        try {
            $sent = Registry::get(Email::class)->sendRevocationEmailToCustomer($submission);
        } catch (Throwable $e) {
            $sent = false;
            Registry::getLogger()->error(
                __METHOD__ . " - Resend threw for submission OXID '" . $submission->getId()
                . "': '" . $e->getMessage() . "'."
            );
        }

        if ($sent) {
            $submission->markSendSucceeded();
            Registry::getLogger()->notice(
                __METHOD__ . " - Customer revocation email resent for submission OXID '"
                . $submission->getId() . "'."
            );
        } else {
            $submission->markSendFailed();
            Registry::getLogger()->error(
                __METHOD__ . " - Customer revocation email resend failed for submission OXID '"
                . $submission->getId() . "'."
            );
        }
        $submission->save();
    }

    private function loadEditedSubmission(): ?O3Revocation
    {
        $oxid = $this->getEditObjectId();
        if (!$oxid || $oxid === '-1') {
            return null;
        }
        $submission = oxNew(O3Revocation::class);
        if (!$submission->load($oxid)) {
            return null;
        }
        return $submission;
    }
}
