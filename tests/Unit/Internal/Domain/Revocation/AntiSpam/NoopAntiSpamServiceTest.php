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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Revocation\AntiSpam;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\AntiSpam\NoopAntiSpamService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the {@see NoopAntiSpamService}.
 *
 * Mocks the OXID `Utils` (file cache) and `UtilsServer` (client IP) singletons
 * via the Registry to keep the test isolated from disk and global request
 * state. Each test sets up exactly the cache snapshot it needs and never
 * touches `source/tmp/`.
 */
class NoopAntiSpamServiceTest extends TestCase
{
    /** @var array<string, mixed> in-memory cache snapshot used by the Utils mock */
    private array $cache = [];
    private string $clientIp = '203.0.113.7';

    protected function setUp(): void
    {
        $this->cache = [];
        $this->clientIp = '203.0.113.7';

        // In-memory replacement for Utils::fromFileCache / toFileCache.
        // We do NOT model TTL here — the tests that need TTL semantics
        // simulate them explicitly via clearCacheKey() / setCacheKey().
        $utils = $this->createMock(Utils::class);
        $utils->method('fromFileCache')->willReturnCallback(
            fn (string $key) => $this->cache[$key] ?? null
        );
        $utils->method('toFileCache')->willReturnCallback(
            function (string $key, $value, int $ttl = 0) {
                $this->cache[$key] = $value;
                return true;
            }
        );

        $utilsServer = $this->createMock(UtilsServer::class);
        $utilsServer->method('getRemoteAddress')->willReturnCallback(
            fn () => $this->clientIp
        );

        Registry::set(Utils::class, $utils);
        Registry::set(UtilsServer::class, $utilsServer);
    }

    protected function tearDown(): void
    {
        Registry::set(Utils::class, null);
        Registry::set(UtilsServer::class, null);
    }

    public function testFreshIpIsAllowed(): void
    {
        $service = new NoopAntiSpamService();
        $this->assertTrue($service->verify(new Request()));
    }

    public function testThreeFailedAttemptsAreAllowedToReachTheController(): void
    {
        $service = new NoopAntiSpamService();
        $request = new Request();

        // First three submissions reach the controller (verify returns true)
        // — within the failed-counter window, the controller is responsible
        // for the rejection itself (validation, token mismatch, etc.).
        for ($i = 0; $i < NoopAntiSpamService::FAILED_LIMIT; $i++) {
            $this->assertTrue($service->verify($request), 'attempt #' . ($i + 1) . ' must reach the controller');
            $service->recordFailure($request);
        }
    }

    public function testFourthFailedAttemptIsRejected(): void
    {
        $service = new NoopAntiSpamService();
        $request = new Request();

        for ($i = 0; $i < NoopAntiSpamService::FAILED_LIMIT; $i++) {
            $service->verify($request);
            $service->recordFailure($request);
        }

        $this->assertFalse(
            $service->verify($request),
            'Fourth attempt within the failed-counter window must be rejected.'
        );
    }

    public function testSuccessfulSubmitTriggersLockoutForTheSameIp(): void
    {
        $service = new NoopAntiSpamService();
        $request = new Request();

        // Initial submit succeeds.
        $this->assertTrue($service->verify($request));
        $service->recordSuccess($request);

        // Subsequent attempts from the same IP are rejected regardless of
        // the failed counter.
        $this->assertFalse(
            $service->verify($request),
            'Post-success lockout must reject subsequent attempts from the same IP.'
        );
    }

    public function testSuccessLockoutDominatesFailedCounter(): void
    {
        $service = new NoopAntiSpamService();
        $request = new Request();

        // Even with zero recorded failures, the success lockout rejects.
        $service->recordSuccess($request);
        $this->assertFalse($service->verify($request));
    }

    public function testFreshIpFromDifferentClientIsUnaffectedByAnotherIpLockout(): void
    {
        $service = new NoopAntiSpamService();
        $request = new Request();

        // IP A locks itself out.
        $this->clientIp = '198.51.100.1';
        $service->recordSuccess($request);
        $this->assertFalse($service->verify($request));

        // IP B — different cache keys, no shared state.
        $this->clientIp = '198.51.100.2';
        $this->assertTrue(
            $service->verify($request),
            'Lockout for one IP must not bleed into other IPs.'
        );
    }

    public function testNoIdentifiableIpAllowsTheRequestThrough(): void
    {
        $service = new NoopAntiSpamService();
        $this->clientIp = '';

        $this->assertTrue(
            $service->verify(new Request()),
            'When the IP cannot be determined the rate limit no-ops; CSRF and validation still gate.'
        );
    }

    public function testRecordingMethodsNoOpWhenIpIsEmpty(): void
    {
        $service = new NoopAntiSpamService();
        $this->clientIp = '';

        $service->recordFailure(new Request());
        $service->recordSuccess(new Request());

        $this->assertSame(
            [],
            $this->cache,
            'No cache writes should happen when the IP cannot be determined.'
        );
    }
}
