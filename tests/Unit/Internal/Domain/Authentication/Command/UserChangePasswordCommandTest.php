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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Authentication\Command;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Command\UserChangePasswordCommand;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Repository\AdminUserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

final class UserChangePasswordCommandTest extends TestCase
{
    private function makeCommand(
        AdminUserRepositoryInterface $repo,
        PasswordServiceBridgeInterface $service
    ): UserChangePasswordCommand {
        $command = new UserChangePasswordCommand($repo, $service);
        // CommandTester doesn't attach an Application, so the question helper
        // (used for the hidden-input prompt) has no HelperSet. Wire one in.
        $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
        return $command;
    }

    public function testUpdatesPasswordForExistingUser(): void
    {
        $repo = $this->createMock(AdminUserRepositoryInterface::class);
        $repo->method('findIdByUsername')->with('admin')->willReturn('user-oxid-42');
        $repo->expects($this->once())
            ->method('updatePassword')
            ->with('user-oxid-42', 'hashed-value');

        $service = $this->createMock(PasswordServiceBridgeInterface::class);
        $service->expects($this->once())
            ->method('hash')
            ->with('plain-pw')
            ->willReturn('hashed-value');

        $tester = new CommandTester($this->makeCommand($repo, $service));
        $exit = $tester->execute(['username' => 'admin', '--password' => 'plain-pw']);

        $this->assertSame(UserChangePasswordCommand::EXIT_OK, $exit);
        $this->assertStringContainsString('Password updated for user "admin"', $tester->getDisplay());
    }

    public function testExitsUserNotFoundWhenUsernameDoesNotResolve(): void
    {
        $repo = $this->createMock(AdminUserRepositoryInterface::class);
        $repo->method('findIdByUsername')->willReturn(null);
        $repo->expects($this->never())->method('updatePassword');

        $service = $this->createMock(PasswordServiceBridgeInterface::class);
        $service->expects($this->never())->method('hash');

        $tester = new CommandTester($this->makeCommand($repo, $service));
        $exit = $tester->execute(['username' => 'ghost', '--password' => 'whatever']);

        $this->assertSame(UserChangePasswordCommand::EXIT_USER_NOT_FOUND, $exit);
        $this->assertStringContainsString('User "ghost" not found', $tester->getDisplay());
    }

    public function testExitsEmptyPasswordWhenPasswordOptionIsBlank(): void
    {
        $repo = $this->createMock(AdminUserRepositoryInterface::class);
        $repo->expects($this->never())->method('findIdByUsername');

        $service = $this->createMock(PasswordServiceBridgeInterface::class);
        $service->expects($this->never())->method('hash');

        $tester = new CommandTester($this->makeCommand($repo, $service));
        $exit = $tester->execute(['username' => 'admin', '--password' => '']);

        $this->assertSame(UserChangePasswordCommand::EXIT_EMPTY_PASSWORD, $exit);
        $this->assertStringContainsString('Password must not be empty', $tester->getDisplay());
    }

    // Interactive (hidden-input) prompt isn't unit-tested here — Symfony's
    // QuestionHelper interacts with stty / TTY detection in ways that don't
    // reproduce reliably under CommandTester. The empty-password branch is
    // covered above via the explicit --password='' case; both interactive
    // and non-interactive paths funnel through the same empty-string check.
}
