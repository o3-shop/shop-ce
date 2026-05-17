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

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Command\UserCreateCommand;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Repository\AdminUserRepository;
use OxidTestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * End-to-end integration test for `oe:user:create` — proves the row
 * the command writes is actually loginable. Closes the "rights value
 * was wrong but the unit test was tautological" gap that produced
 * the o3-shop/o3-shop#143 oxrights='' regression: a unit test that
 * only asserts the CLI-emitted literal cannot tell whether that
 * literal matches what `User::login()` requires. This test does.
 *
 * Cleanup: each test pushes its generated OXIDs onto $createdOxids;
 * tearDown deletes those rows so repeated runs (and CI) stay stable.
 * Uses real DBAL connection + real PasswordServiceBridge from the DI
 * container — same machinery production uses.
 */
final class UserCreateCommandLoginTest extends OxidTestCase
{
    /** @var array<int,string> */
    private array $createdOxids = [];

    protected function tearDown(): void
    {
        if ($this->createdOxids !== []) {
            $db = DatabaseProvider::getDb();
            foreach ($this->createdOxids as $oxid) {
                $db->execute('DELETE FROM oxuser WHERE oxid = ?', [$oxid]);
            }
            $this->createdOxids = [];
        }
        parent::tearDown();
    }

    public function testRoleCustomerYieldsRowThatStorefrontLoginAccepts(): void
    {
        $username = $this->uniqueUsername('customer');
        $password = 'storefront-test-pw-123';

        $this->createUserViaCommand($username, $password, UserCreateCommand::ROLE_CUSTOMER);

        $user = oxNew(User::class);
        $user->login($username, $password, false);

        $this->assertTrue(
            $user->isLoaded(),
            'storefront User::login() must accept the credentials of a user '
            . 'created with --role=customer'
        );
        $this->assertSame($username, $user->oxuser__oxusername->value);
    }

    public function testRoleAdminYieldsRowThatAdminLoginAccepts(): void
    {
        $username = $this->uniqueUsername('admin');
        $password = 'admin-test-pw-123';

        $this->createUserViaCommand($username, $password, UserCreateCommand::ROLE_ADMIN);

        // Admin login requires admin mode + a cookie (see User::login).
        $oUtilsServer = $this->getMock(\OxidEsales\Eshop\Core\UtilsServer::class, ['getOxCookie']);
        $oUtilsServer->expects($this->any())
            ->method('getOxCookie')
            ->will($this->returnValue('test'));
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\UtilsServer::class, $oUtilsServer);
        $this->getConfig()->setAdminMode(true);

        try {
            $user = oxNew(User::class);
            $user->login($username, $password, false);

            $this->assertTrue(
                $user->isLoaded(),
                'admin User::login() must accept the credentials of a user '
                . 'created with --role=admin'
            );
            $this->assertSame($username, $user->oxuser__oxusername->value);
        } finally {
            // Restore admin mode so subsequent tests (and tearDown) see a clean state.
            $this->getConfig()->setAdminMode(false);
        }
    }

    private function createUserViaCommand(string $username, string $password, string $role): void
    {
        $command = new UserCreateCommand(
            new AdminUserRepository(
                ContainerFactory::getInstance()->getContainer()->get(\Doctrine\DBAL\Connection::class)
            ),
            ContainerFactory::getInstance()->getContainer()->get(PasswordServiceBridgeInterface::class)
        );
        $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));

        $tester = new CommandTester($command);
        $exit = $tester->execute([
            'username'   => $username,
            '--password' => $password,
            '--role'     => $role,
        ]);
        $this->assertSame(UserCreateCommand::EXIT_OK, $exit, $tester->getDisplay());

        // OXID is echoed in the success line as "...(OXID <hex>)."
        if (preg_match('/\(OXID ([0-9a-f]+)\)/', $tester->getDisplay(), $m) === 1) {
            $this->createdOxids[] = $m[1];
        }
    }

    private function uniqueUsername(string $tag): string
    {
        return sprintf('test-%s-%s@example.com', $tag, bin2hex(random_bytes(4)));
    }
}
