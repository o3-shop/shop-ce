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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\EshopCommunity\Application\Controller\RevocationController;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\AntiSpam\RevocationAntiSpamServiceInterface;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for the {@see RevocationController} submit-flow.
 *
 * The render-path gating (404 / login redirect / form rendering) requires
 * a live shop context to exercise `parent::render()` and is covered by
 * integration tests rather than this file.
 *
 * Each test sets up exactly the singletons it needs via `Registry::set`,
 * which the OXID convention uses for unit-level isolation.
 */
class RevocationControllerTest extends UnitTestCase
{
    /** @var array<string, mixed> request parameters returned by the mocked Request */
    private array $requestParams = [];

    /** @var array<string, mixed> oxconfig values returned by the mocked Config */
    private array $configValues = [];

    /** @var bool whether `Session::checkSessionChallenge()` returns true */
    private bool $tokenValid = true;

    /** @var bool whether `RevocationAntiSpamServiceInterface::verify()` returns true */
    private bool $antiSpamVerifyResult = true;

    /** @var array<string, int> tally of anti-spam record* calls */
    private array $antiSpamCalls = ['recordSuccess' => 0, 'recordFailure' => 0];

    /** @var array<int, array{string, ?string}> redirect calls captured: [url, headerCode] */
    private array $redirects = [];

    /** @var int how many times handlePageNotFoundError was called */
    private int $pageNotFoundCalls = 0;

    /** @var bool|null email-send return value (null means method does not exist) */
    private ?bool $customerEmailReturn = true;
    private ?bool $operatorEmailReturn = true;

    /** @var RevocationAntiSpamServiceInterface|null reused across the makeController() calls in each test */
    private ?RevocationAntiSpamServiceInterface $antiSpamMock = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestParams = [];
        $this->configValues = [
            'blShowRevocationForm' => true,
            'blRevocationRequireLogin' => false,
            'blRevocationNotifyOperator' => true,
            'sRevocationOperatorEmail' => 'ops@example.com',
        ];
        $this->tokenValid = true;
        $this->antiSpamVerifyResult = true;
        $this->antiSpamCalls = ['recordSuccess' => 0, 'recordFailure' => 0];
        $this->redirects = [];
        $this->pageNotFoundCalls = 0;
        $this->customerEmailReturn = true;
        $this->operatorEmailReturn = true;

        $this->mockRegistry();
    }

    protected function tearDown(): void
    {
        // Best-effort cleanup of any DB rows the happy-path tests inserted.
        try {
            $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $database->execute("DELETE FROM o3revocation WHERE OXID LIKE '_revctrl_%'");
        } catch (\Throwable $e) {
            // ignore — DB may not be available in pure unit tests
        }
        parent::tearDown();
    }

    public function testSubmitRejectsOnTokenMismatch(): void
    {
        $this->tokenValid = false;

        $controller = $this->makeController();
        $result = $controller->submit();

        $this->assertFalse($result);
        $this->assertSame(
            ['form' => 'O3_REVOCATION_VALIDATION_SESSION_EXPIRED'],
            $controller->getValidationErrors()
        );
        // Token mismatch happens before anti-spam verify — neither recorded.
        $this->assertSame(0, $this->antiSpamCalls['recordFailure']);
        $this->assertSame(0, $this->antiSpamCalls['recordSuccess']);
    }

    public function testSubmitRejectsOnAntiSpamFailure(): void
    {
        $this->antiSpamVerifyResult = false;

        $controller = $this->makeController();
        $result = $controller->submit();

        $this->assertFalse($result);
        $this->assertSame(
            ['form' => 'O3_REVOCATION_VALIDATION_SPAM'],
            $controller->getValidationErrors()
        );
    }

    public function testSubmitRejectsWhenAllMandatoryFieldsAreEmpty(): void
    {
        $controller = $this->makeController();
        $result = $controller->submit();

        $this->assertFalse($result);
        $errors = $controller->getValidationErrors();
        $this->assertSame('O3_REVOCATION_VALIDATION_REQUIRED', $errors['o3rev_name'] ?? null);
        $this->assertSame('O3_REVOCATION_VALIDATION_REQUIRED', $errors['o3rev_orderident'] ?? null);
        $this->assertSame('O3_REVOCATION_VALIDATION_REQUIRED', $errors['o3rev_email'] ?? null);
        // Anti-spam recordFailure was called for the rejected submission.
        $this->assertSame(1, $this->antiSpamCalls['recordFailure']);
    }

    public function testSubmitTreatsWhitespaceOnlyValuesAsEmpty(): void
    {
        $this->requestParams = [
            'o3rev_name' => '   ',
            'o3rev_orderident' => "\t",
            'o3rev_email' => 'ops@example.com',
        ];

        $controller = $this->makeController();
        $controller->submit();

        $errors = $controller->getValidationErrors();
        $this->assertSame('O3_REVOCATION_VALIDATION_REQUIRED', $errors['o3rev_name'] ?? null);
        $this->assertSame('O3_REVOCATION_VALIDATION_REQUIRED', $errors['o3rev_orderident'] ?? null);
        $this->assertArrayNotHasKey('o3rev_email', $errors);
    }

    public function testSubmitFlagsSyntacticallyInvalidEmail(): void
    {
        $this->requestParams = [
            'o3rev_name' => 'Maria Schmidt',
            'o3rev_orderident' => 'ORDER-1',
            'o3rev_email' => 'foo@', // fails FILTER_VALIDATE_EMAIL
        ];

        $controller = $this->makeController();
        $controller->submit();

        $errors = $controller->getValidationErrors();
        $this->assertSame('O3_REVOCATION_VALIDATION_EMAIL_FORMAT', $errors['o3rev_email'] ?? null);
        $this->assertArrayNotHasKey('o3rev_name', $errors);
        $this->assertArrayNotHasKey('o3rev_orderident', $errors);
    }

    public function testSubmitHappyPathPersistsAndRedirectsToReceipt(): void
    {
        $this->requestParams = [
            'o3rev_name' => 'Maria Schmidt',
            'o3rev_orderident' => '_revctrl_ORDER1',
            'o3rev_email' => 'maria@example.com',
            'o3rev_freetext' => 'Partial revocation only.',
        ];

        $controller = $this->makeController();
        $result = $controller->submit();

        // Successful path returns null; redirect was issued.
        $this->assertNull($result);
        $this->assertSame([], $controller->getValidationErrors());
        $this->assertSame(1, $this->antiSpamCalls['recordSuccess']);
        $this->assertSame(0, $this->antiSpamCalls['recordFailure']);
        $this->assertCount(1, $this->redirects);
        $this->assertSame(303, $this->redirects[0][1]);
        $this->assertStringContainsString('cl=revocation&fnc=receipt', $this->redirects[0][0]);

        // The row hit the DB (cleaned up in tearDown).
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $count = (int) $database->getOne(
            "SELECT COUNT(*) FROM o3revocation WHERE OXORDERIDENT = '_revctrl_ORDER1'"
        );
        $this->assertSame(1, $count);
    }

    public function testGettersReturnSubmittedValuesForReRender(): void
    {
        $this->requestParams = [
            'o3rev_name' => 'Maria',
            'o3rev_orderident' => 'ORDER-X',
            'o3rev_email' => 'foo@', // intentionally invalid so the form re-renders
            'o3rev_freetext' => 'Optional note',
        ];

        $controller = $this->makeController();
        $controller->submit();

        // After rejection, the template-getter methods return what the user
        // typed so the form re-renders with their input preserved.
        $this->assertSame('Maria', $controller->getName());
        $this->assertSame('ORDER-X', $controller->getOrderIdent());
        $this->assertSame('foo@', $controller->getEmail());
        $this->assertSame('Optional note', $controller->getFreeText());
    }

    public function testFeatureFlagAndLoginFlagHelpersReadConfig(): void
    {
        $controller = $this->makeController();

        $this->configValues['blShowRevocationForm'] = false;
        $this->configValues['blRevocationRequireLogin'] = true;
        $this->assertFalse($controller->isFeatureEnabled());
        $this->assertTrue($controller->isLoginRequired());

        $this->configValues['blShowRevocationForm'] = true;
        $this->configValues['blRevocationRequireLogin'] = false;
        $this->assertTrue($controller->isFeatureEnabled());
        $this->assertFalse($controller->isLoginRequired());
    }

    private function makeController(): RevocationController
    {
        $controller = new RevocationController();
        $controller->setAntiSpamService($this->antiSpamMock);
        return $controller;
    }

    private function mockRegistry(): void
    {
        // Logger — silence runtime log calls; the unit test isn't asserting on log content.
        Registry::set('logger', new NullLogger());

        // Session
        $session = $this->createMock(Session::class);
        $session->method('checkSessionChallenge')->willReturnCallback(fn () => $this->tokenValid);
        Registry::set(Session::class, $session);

        // Request
        $request = $this->createMock(Request::class);
        $request->method('getRequestParameter')->willReturnCallback(
            fn ($name, $default = null) => $this->requestParams[$name] ?? $default
        );
        $request->method('getRequestEscapedParameter')->willReturnCallback(
            fn ($name, $default = null) => $this->requestParams[$name] ?? $default
        );
        $request->method('getRequestUrl')->willReturn('?cl=revocation&fnc=submit');
        Registry::set(Request::class, $request);

        // Config
        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(
            fn ($name, $default = null) => $this->configValues[$name] ?? $default
        );
        $config->method('getShopId')->willReturn(1);
        Registry::set(Config::class, $config);

        // Utils — capture redirect + 404 calls instead of executing them
        $utils = $this->createMock(Utils::class);
        $utils->method('redirect')->willReturnCallback(
            function (string $url, $blAddRedirectParam = true, $iHeaderCode = 302) {
                $this->redirects[] = [$url, $iHeaderCode];
                // do NOT exit (real redirect calls exit) — let the controller
                // method return naturally so the test can assert state.
                return null;
            }
        );
        $utils->method('handlePageNotFoundError')->willReturnCallback(
            function () {
                $this->pageNotFoundCalls++;
            }
        );
        Registry::set(Utils::class, $utils);

        // Anti-spam service — DI container is compiled and won't accept
        // runtime overrides; we instead inject via the controller's
        // setAntiSpamService() seam (production code resolves from the
        // container exactly as before).
        $antiSpam = $this->createMock(RevocationAntiSpamServiceInterface::class);
        $antiSpam->method('verify')->willReturnCallback(fn () => $this->antiSpamVerifyResult);
        $antiSpam->method('recordSuccess')->willReturnCallback(
            function () {
                $this->antiSpamCalls['recordSuccess']++;
            }
        );
        $antiSpam->method('recordFailure')->willReturnCallback(
            function () {
                $this->antiSpamCalls['recordFailure']++;
            }
        );
        $this->antiSpamMock = $antiSpam;

        // Mailer (Core\Email) — phase 4 doesn't need to assert anything
        // about the email send; we just need the call site to not crash.
        // Use addMethods() for the not-yet-existing methods (delivered in phase 5).
        $mailer = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendRevocationEmailToCustomer', 'sendRevocationEmailToOperator'])
            ->getMock();
        $mailer->method('sendRevocationEmailToCustomer')->willReturnCallback(
            fn () => $this->customerEmailReturn
        );
        $mailer->method('sendRevocationEmailToOperator')->willReturnCallback(
            fn () => $this->operatorEmailReturn
        );
        Registry::set(Email::class, $mailer);
    }
}
