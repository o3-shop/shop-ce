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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

/**
 * Default anti-spam implementation: a two-mode IP rate limit, no CAPTCHA.
 * Issue #113 will replace the DI binding with `AltchaAntiSpamService` for
 * scaled-bot resistance — the controller does not change.
 *
 * Two independent counters live in the OXID file cache (per design D8):
 *
 *   - **Failed-submission counter** — at most {@see FAILED_LIMIT} failed
 *     attempts per IP within a {@see FAILED_WINDOW_SECONDS}-second sliding
 *     window. "Failed" = anything that reaches the controller and is
 *     rejected past `verify()` (validation, token mismatch, etc.).
 *
 *   - **Successful-submission lockout** — once a submission persists,
 *     the same IP is rejected for {@see SUCCESS_LOCKOUT_SECONDS} seconds.
 *     A real human does not legitimately submit two revocations from the
 *     same IP within five minutes; the second attempt is overwhelmingly
 *     abuse. The trade-off — a household with two orders to revoke from
 *     the same router waits 5 minutes between submissions — is
 *     deliberate (see design D8).
 *
 * Cache keys hash the IP so IPv6 colons and other special characters
 * cannot collide with the file-cache filename rules.
 */
class NoopAntiSpamService implements RevocationAntiSpamServiceInterface
{
    public const FAILED_LIMIT = 3;
    public const FAILED_WINDOW_SECONDS = 60;
    public const SUCCESS_LOCKOUT_SECONDS = 300;

    private const KEY_PREFIX_FAILED = 'o3rev_antispam_f_';
    private const KEY_PREFIX_SUCCESS = 'o3rev_antispam_s_';

    public function verify(Request $request): bool
    {
        $ip = $this->getClientIp();
        if ($ip === '') {
            // No identifiable source — let the request through; downstream
            // layers (CSRF token, validation) still apply.
            return true;
        }

        $utils = Registry::getUtils();

        // Mode 1: post-success lockout dominates.
        if ($utils->fromFileCache(self::KEY_PREFIX_SUCCESS . $ip) !== null) {
            return false;
        }

        // Mode 2: failed-attempt counter.
        $failedCount = (int) $utils->fromFileCache(self::KEY_PREFIX_FAILED . $ip);
        if ($failedCount >= self::FAILED_LIMIT) {
            return false;
        }

        return true;
    }

    public function recordSuccess(Request $request): void
    {
        $ip = $this->getClientIp();
        if ($ip === '') {
            return;
        }
        Registry::getUtils()->toFileCache(
            self::KEY_PREFIX_SUCCESS . $ip,
            '1',
            self::SUCCESS_LOCKOUT_SECONDS
        );
    }

    public function recordFailure(Request $request): void
    {
        $ip = $this->getClientIp();
        if ($ip === '') {
            return;
        }
        $utils = Registry::getUtils();
        $current = (int) $utils->fromFileCache(self::KEY_PREFIX_FAILED . $ip);
        $utils->toFileCache(
            self::KEY_PREFIX_FAILED . $ip,
            (string) ($current + 1),
            self::FAILED_WINDOW_SECONDS
        );
    }

    /**
     * Resolve and normalise the client IP. Returns an empty string when
     * no identifiable source IP is available — the rate limit then no-ops
     * for that request (CSRF and validation still gate the request).
     */
    private function getClientIp(): string
    {
        $raw = (string) Registry::getUtilsServer()->getRemoteAddress();
        if ($raw === '' || $raw === '0.0.0.0') {
            return '';
        }
        // Hash so the resulting cache key is filename-safe regardless of
        // IPv4 dots or IPv6 colons. md5 is enough — we only need uniqueness.
        return md5($raw);
    }
}
