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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\RevocationMain;
use OxidEsales\EshopCommunity\Application\Model\O3Revocation;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

/**
 * Stub submission: load() return is steered per-test, mark/save calls
 * are recorded so the resend() flow can be inspected without DB.
 */
class RevocationMainTest_StubSubmission extends O3Revocation
{
    public static bool $loadReturns = true;
    public static string $oxid = 'rev-oxid-007';

    public ?string $loadedWith = null;
    public bool $markedSucceeded = false;
    public bool $markedFailed = false;
    public bool $saved = false;

    public function __construct()
    {
        // Skip parent::__construct so init() (DB metadata) does not run.
    }

    public function load($oxid)
    {
        $this->loadedWith = (string) $oxid;
        return self::$loadReturns;
    }

    public function getId(): string
    {
        return self::$oxid;
    }

    public function markSendSucceeded(): void
    {
        $this->markedSucceeded = true;
    }

    public function markSendFailed(): void
    {
        $this->markedFailed = true;
    }

    public function save()
    {
        $this->saved = true;
        return true;
    }
}

class RevocationMainTest_StubMailer
{
    public static bool $sentReturns = true;
    public static ?\Throwable $throws = null;
    public static int $callCount = 0;
    public static ?\stdClass $lastArg = null;

    public function sendRevocationEmailToCustomer($submission)
    {
        self::$callCount++;
        if (self::$throws !== null) {
            throw self::$throws;
        }
        return self::$sentReturns;
    }
}

class RevocationMainTest extends \OxidTestCase
{
    /** @var array<int,array{level:string,message:string}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->logged = [];
        $sink = &$this->logged;
        Registry::set('logger', new class ($sink) extends AbstractLogger {
            /** @var array<int,array{level:string,message:string}> */
            private array $sink;
            /** @param array<int,array{level:string,message:string}> $sink */
            public function __construct(array &$sink)
            {
                $this->sink = &$sink;
            }
            public function log($level, $message, array $context = []): void
            {
                $this->sink[] = ['level' => (string) $level, 'message' => (string) $message];
            }
        });

        RevocationMainTest_StubSubmission::$loadReturns = true;
        RevocationMainTest_StubMailer::$sentReturns = true;
        RevocationMainTest_StubMailer::$throws = null;
        RevocationMainTest_StubMailer::$callCount = 0;
    }

    protected function tearDown(): void
    {
        Registry::set('logger', new NullLogger());
        parent::tearDown();
    }

    public function testTemplateNameIsRevocationMain(): void
    {
        $controller = $this->getProxyClass(RevocationMain::class);
        $this->assertSame('revocation_main.tpl', $controller->getNonPublicVar('_sThisTemplate'));
    }

    public function testRenderStashesLoadedSubmissionInViewData(): void
    {
        $stub = new RevocationMainTest_StubSubmission();
        \oxTestModules::addModuleObject(O3Revocation::class, $stub);

        $controller = $this->getMock(RevocationMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('rev-oxid-007'));

        $controller->render();

        $this->assertSame('rev-oxid-007', $stub->loadedWith);
        $viewData = $controller->getViewData();
        $this->assertSame($stub, $viewData['edit'] ?? null);
    }

    public function testRenderSkipsLoadForResetSentinelOxid(): void
    {
        $stub = new RevocationMainTest_StubSubmission();
        \oxTestModules::addModuleObject(O3Revocation::class, $stub);

        $controller = $this->getMock(RevocationMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('-1'));

        $controller->render();

        $this->assertNull($stub->loadedWith, 'render() must not load() for the -1 reset sentinel.');
    }

    public function testResendOnSuccessClearsSendFailedAndLogsNotice(): void
    {
        $stub = new RevocationMainTest_StubSubmission();
        \oxTestModules::addModuleObject(O3Revocation::class, $stub);

        $mailer = new RevocationMainTest_StubMailer();
        RevocationMainTest_StubMailer::$sentReturns = true;
        Registry::set(Email::class, $mailer);

        $controller = $this->getMock(RevocationMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('rev-oxid-007'));

        $controller->resend();

        $this->assertSame(1, RevocationMainTest_StubMailer::$callCount);
        $this->assertTrue($stub->markedSucceeded);
        $this->assertFalse($stub->markedFailed);
        $this->assertTrue($stub->saved);

        $notices = array_filter($this->logged, static fn ($e) => $e['level'] === 'notice');
        $this->assertCount(1, $notices, 'A NOTICE must be logged on successful resend.');
    }

    public function testResendOnFailureMarksSendFailedAndLogsError(): void
    {
        $stub = new RevocationMainTest_StubSubmission();
        \oxTestModules::addModuleObject(O3Revocation::class, $stub);

        $mailer = new RevocationMainTest_StubMailer();
        RevocationMainTest_StubMailer::$sentReturns = false;
        Registry::set(Email::class, $mailer);

        $controller = $this->getMock(RevocationMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('rev-oxid-007'));

        $controller->resend();

        $this->assertFalse($stub->markedSucceeded);
        $this->assertTrue($stub->markedFailed);
        $this->assertTrue($stub->saved);

        $errors = array_filter($this->logged, static fn ($e) => $e['level'] === 'error');
        $this->assertCount(1, $errors);
    }

    public function testResendCatchesMailerExceptionAndLogsErrorTwice(): void
    {
        $stub = new RevocationMainTest_StubSubmission();
        \oxTestModules::addModuleObject(O3Revocation::class, $stub);

        $mailer = new RevocationMainTest_StubMailer();
        RevocationMainTest_StubMailer::$throws = new \RuntimeException('SMTP unreachable');
        Registry::set(Email::class, $mailer);

        $controller = $this->getMock(RevocationMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('rev-oxid-007'));

        $controller->resend();

        $this->assertTrue($stub->markedFailed);
        $this->assertTrue($stub->saved);

        $errors = array_filter($this->logged, static fn ($e) => $e['level'] === 'error');
        // One error for the throw, one for "resend failed".
        $this->assertCount(2, $errors);
        $this->assertStringContainsString('SMTP unreachable', $errors[array_key_first($errors)]['message']);
    }

    public function testResendIsNoopWhenSubmissionCannotBeLoaded(): void
    {
        $stub = new RevocationMainTest_StubSubmission();
        RevocationMainTest_StubSubmission::$loadReturns = false;
        \oxTestModules::addModuleObject(O3Revocation::class, $stub);

        $mailer = new RevocationMainTest_StubMailer();
        Registry::set(Email::class, $mailer);

        $controller = $this->getMock(RevocationMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('rev-oxid-007'));

        $controller->resend();

        $this->assertSame(0, RevocationMainTest_StubMailer::$callCount, 'Mailer must not be called when load() fails.');
        $this->assertFalse($stub->markedSucceeded);
        $this->assertFalse($stub->markedFailed);
        $this->assertFalse($stub->saved);
    }

    public function testResendIsNoopForResetSentinelOxid(): void
    {
        $stub = new RevocationMainTest_StubSubmission();
        \oxTestModules::addModuleObject(O3Revocation::class, $stub);

        $mailer = new RevocationMainTest_StubMailer();
        Registry::set(Email::class, $mailer);

        $controller = $this->getMock(RevocationMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('-1'));

        $controller->resend();

        $this->assertSame(0, RevocationMainTest_StubMailer::$callCount);
        $this->assertNull($stub->loadedWith);
    }
}
