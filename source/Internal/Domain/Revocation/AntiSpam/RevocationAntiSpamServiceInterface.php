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

namespace OxidEsales\EshopCommunity\Internal\Domain\Revocation\AntiSpam;

use OxidEsales\Eshop\Core\Request;

/**
 * Anti-spam decision-point for the §356a revocation form.
 *
 * The default implementation in this repository is the {@see NoopAntiSpamService}
 * (a small IP rate limit; no CAPTCHA). Issue #113 will introduce
 * `AltchaAntiSpamService` as a drop-in replacement bound by the DI container —
 * the controller does not need to change.
 *
 * The contract has three hooks rather than just one verify() so the service
 * can implement the **two-mode rate limit** documented in design D8:
 *
 *   - `verify()` is called BEFORE field validation. It says "allow this
 *     request to proceed into the controller" or "reject early with a
 *     generic error".
 *   - `recordFailure()` is called when the controller rejects the
 *     submission for any reason past `verify()` — validation error,
 *     session-token mismatch, anything. Lets the service track repeated
 *     near-misses from the same source.
 *   - `recordSuccess()` is called immediately after a successful persist.
 *     Lets the service apply a post-success lockout (real users do not
 *     legitimately submit two revocations from the same IP within seconds).
 */
interface RevocationAntiSpamServiceInterface
{
    /**
     * Decide whether the inbound request should be allowed past the
     * anti-spam gate. Returns true to proceed, false to reject early.
     */
    public function verify(Request $request): bool;

    /**
     * Record that a submission successfully reached persist (i.e. a row
     * was written to `o3revocation`). Implementations may use this to
     * activate a post-success lockout for the source IP.
     */
    public function recordSuccess(Request $request): void;

    /**
     * Record that a submission was rejected at any step after `verify()`
     * returned true (validation error, token mismatch, etc.). Implementations
     * may use this to count repeated near-misses and tighten the gate.
     */
    public function recordFailure(Request $request): void;
}
