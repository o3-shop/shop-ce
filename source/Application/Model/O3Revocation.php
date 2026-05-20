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

namespace OxidEsales\EshopCommunity\Application\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;

/**
 * §356a BGB electronic revocation submission.
 *
 * Persists a single consumer revocation declaration captured by the public
 * `?cl=revocation` form (see issue #99). One row per submission in the
 * `o3revocation` table.
 *
 * Two invariants are enforced at the model layer:
 *
 *   - **`OXSUBMITTED` is write-once.** It is the legal "time of receipt" per
 *     § 356a Abs. 4 BGB — it MUST stay frozen across any later UPDATE
 *     (e.g. flagging a "send failed" status, clearing it on resend,
 *     admin manually editing other fields). Enforced by appending
 *     `oxsubmitted` to `_aSkipSaveFields` inside `_update()`.
 *
 *   - **`OXTIMESTAMP` is auto-maintained by the DB engine.** The base model
 *     already excludes `oxtimestamp` from saves; the column carries
 *     `ON UPDATE CURRENT_TIMESTAMP` in the schema.
 *
 * Send-failure tracking uses a dedicated `OXSENDFAILED` boolean column
 * rather than deriving from a "last error" column, because the admin
 * list view filters on this flag and a tinyint check is cheaper than
 * a NULL/non-NULL check on a TEXT column.
 */
class O3Revocation extends BaseModel
{
    public const TABLE = 'o3revocation';

    /** @var string */
    protected $_sClassName = 'o3revocation';

    public function __construct()
    {
        parent::__construct();
        $this->init(self::TABLE);
    }

    public function getId(): string
    {
        return (string) $this->getFieldData('oxid');
    }

    public function getShopId(): int
    {
        return (int) $this->getFieldData('oxshopid');
    }

    public function getLang(): int
    {
        return (int) $this->getFieldData('oxlang');
    }

    public function getName(): string
    {
        return (string) $this->getFieldData('oxname');
    }

    public function getOrderIdent(): string
    {
        return (string) $this->getFieldData('oxorderident');
    }

    public function getEmail(): string
    {
        return (string) $this->getFieldData('oxemail');
    }

    public function getFreeText(): ?string
    {
        $value = $this->getFieldData('oxfreetext');
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }

    /**
     * Legal time of receipt. Returned as an immutable point-in-time so
     * callers cannot mutate the model's notion of when it was submitted.
     *
     * @throws Exception when the persisted OXSUBMITTED value is not a
     *                   parseable date — should never happen for rows we
     *                   wrote ourselves (we always format `Y-m-d H:i:s`),
     *                   so an exception here means data corruption.
     */
    public function getSubmittedAt(): DateTimeInterface
    {
        $raw = (string) $this->getFieldData('oxsubmitted');
        return new DateTimeImmutable($raw);
    }

    public function hasSendFailed(): bool
    {
        return (bool) ((int) $this->getFieldData('oxsendfailed'));
    }

    /**
     * Flag this submission's customer-confirmation send as failed.
     * Admin-side resend can clear it via {@see markSendSucceeded()}.
     * Caller is responsible for `save()` afterwards.
     */
    public function markSendFailed(): void
    {
        $this->assign(['oxsendfailed' => 1]);
    }

    /**
     * Clear the "send failed" flag (called after a successful resend).
     * Caller is responsible for `save()` afterwards.
     */
    public function markSendSucceeded(): void
    {
        $this->assign(['oxsendfailed' => 0]);
    }

    /**
     * Enforces both persistence invariants in a single override on the
     * public, non-deprecated `save()` entry point — instead of overriding
     * the deprecated `_insert()` / `_update()` hooks (those carry an
     * "underscore prefix violates PSR12, will be renamed in next major"
     * deprecation note in the BaseModel).
     *
     *   - **First save (insert):** if the caller has not supplied a value
     *     for OXSUBMITTED, fall in `now()` here so the invariant ("written
     *     ONCE on persist", per the column COMMENT) holds even when the
     *     controller forgets.
     *   - **Subsequent saves (update):** lock OXSUBMITTED against being
     *     re-written by appending it to `_aSkipSaveFields`. Any explicit
     *     value the caller assigns to `o3revocation__oxsubmitted` between
     *     loads is silently dropped from the UPDATE statement, preserving
     *     the legal time-of-receipt.
     *
     * Signature is `mixed` (no return type hint) to match the parent
     * declaration — required by LSP.
     *
     * @return false|string false on failure, the OXID on success.
     * @throws Exception when DateTimeImmutable cannot parse `now` — should
     *                   never happen on a sane PHP runtime.
     */
    public function save()
    {
        if ($this->exists()) {
            if (!in_array('oxsubmitted', $this->_aSkipSaveFields, true)) {
                $this->_aSkipSaveFields[] = 'oxsubmitted';
            }
        } else {
            $field = $this->{self::TABLE . '__oxsubmitted'} ?? null;
            if (!($field instanceof Field) || $field->value === null || $field->value === '') {
                $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
                $this->{self::TABLE . '__oxsubmitted'} = new Field($now, Field::T_RAW);
            }
        }

        return parent::save();
    }
}
