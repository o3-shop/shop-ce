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
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Command\UserCreateAdminCommand;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Repository\AdminUserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

final class UserCreateAdminCommandTest extends TestCase
{
    private function makeCommand(
        AdminUserRepositoryInterface $repo,
        PasswordServiceBridgeInterface $service
    ): UserCreateAdminCommand {
        $command = new UserCreateAdminCommand($repo, $service);
        $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
        return $command;
    }

    public function testCreatesNewAdminUserAndReportsOxid(): void
    {
        $repo = $this->createMock(AdminUserRepositoryInterface::class);
        $repo->method('findIdByUsername')->with('newadmin')->willReturn(null);
        $repo->expects($this->once())
            ->method('insertAdmin')
            ->with('newadmin', 'hashed-value')
            ->willReturn('generated-oxid-abc');

        $service = $this->createMock(PasswordServiceBridgeInterface::class);
        $service->method('hash')->with('plain-pw')->willReturn('hashed-value');

        $tester = new CommandTester($this->makeCommand($repo, $service));
        $exit = $tester->execute(['username' => 'newadmin', '--password' => 'plain-pw']);

        $this->assertSame(UserCreateAdminCommand::EXIT_OK, $exit);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Admin user "newadmin" created', $display);
        $this->assertStringContainsString('generated-oxid-abc', $display);
    }

    public function testExitsUsernameExistsWhenUserAlreadyPresent(): void
    {
        $repo = $this->createMock(AdminUserRepositoryInterface::class);
        $repo->method('findIdByUsername')->willReturn('existing-oxid');
        $repo->expects($this->never())->method('insertAdmin');

        $service = $this->createMock(PasswordServiceBridgeInterface::class);
        $service->expects($this->never())->method('hash');

        $tester = new CommandTester($this->makeCommand($repo, $service));
        $exit = $tester->execute(['username' => 'admin', '--password' => 'anything']);

        $this->assertSame(UserCreateAdminCommand::EXIT_USERNAME_EXISTS, $exit);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('already exists', $display);
        $this->assertStringContainsString('oe:user:change-password', $display);
    }

    public function testExitsEmptyPasswordWhenPasswordOptionIsBlank(): void
    {
        $repo = $this->createMock(AdminUserRepositoryInterface::class);
        $repo->expects($this->never())->method('findIdByUsername');
        $repo->expects($this->never())->method('insertAdmin');

        $service = $this->createMock(PasswordServiceBridgeInterface::class);

        $tester = new CommandTester($this->makeCommand($repo, $service));
        $exit = $tester->execute(['username' => 'newadmin', '--password' => '']);

        $this->assertSame(UserCreateAdminCommand::EXIT_EMPTY_PASSWORD, $exit);
        $this->assertStringContainsString('Password must not be empty', $tester->getDisplay());
    }

    public function testExitsInsertFailedWhenRepositoryThrows(): void
    {
        $repo = $this->createMock(AdminUserRepositoryInterface::class);
        $repo->method('findIdByUsername')->willReturn(null);
        $repo->method('insertAdmin')->willThrowException(new RuntimeException('DB exploded'));

        $service = $this->createMock(PasswordServiceBridgeInterface::class);
        $service->method('hash')->willReturn('hashed-value');

        $tester = new CommandTester($this->makeCommand($repo, $service));
        $exit = $tester->execute(['username' => 'newadmin', '--password' => 'plain-pw']);

        $this->assertSame(UserCreateAdminCommand::EXIT_INSERT_FAILED, $exit);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Failed to create user', $display);
        $this->assertStringContainsString('DB exploded', $display);
    }

    // Interactive (hidden-input) prompt isn't unit-tested here — Symfony's
    // QuestionHelper interacts with stty / TTY detection in ways that don't
    // reproduce reliably under CommandTester. The empty-password branch is
    // covered above via the explicit --password='' case; both interactive
    // and non-interactive paths funnel through the same empty-string check.
}
