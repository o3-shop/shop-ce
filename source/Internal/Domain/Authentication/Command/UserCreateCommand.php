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

namespace OxidEsales\EshopCommunity\Internal\Domain\Authentication\Command;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Repository\AdminUserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

/**
 * Create a fresh oxuser from the CLI. Mirrors the two-option role
 * selector the admin panel exposes ("Kunde" / "Admin") via the
 * `--role={admin|customer}` flag — `admin` writes `OXRIGHTS='malladmin'`,
 * `customer` writes the empty-string rights value the storefront uses.
 *
 * The lockout-recovery path filed at o3-shop/o3-shop#143 (no admin
 * left, need a new one from the shell) is the default: omit `--role`
 * and you get a malladmin in one line. The `--role=customer` form
 * matches the admin panel's "Rechte = Kunde" choice — note that a
 * storefront customer created via CLI carries empty profile fields
 * (name, address, etc.); use the storefront registration flow when
 * those matter.
 *
 * Usage:
 *   bin/oe-console oe:user:create <username> [--password=...]
 *   bin/oe-console oe:user:create <username> --role=customer --password=...
 *
 * Omitting `--password` triggers a hidden prompt (no echo, with a
 * non-TTY fallback for CI / piped-input use). Hash goes through
 * PasswordServiceBridge — same code path the admin panel uses.
 */
final class UserCreateCommand extends Command
{
    public const EXIT_OK = 0;
    public const EXIT_USERNAME_EXISTS = 1;
    public const EXIT_EMPTY_PASSWORD = 2;
    public const EXIT_INSERT_FAILED = 3;
    public const EXIT_UNKNOWN_ROLE = 4;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CUSTOMER = 'customer';

    /**
     * Map from CLI-facing role name to the OXRIGHTS value stored in
     * oxuser. The two named values OXID accepts in the rights column
     * are 'malladmin' (admin-panel login) and 'user' (storefront
     * login) — the storefront query explicitly filters
     * `AND ( oxrights = 'user' )` (User::formQueryPartForAdminView),
     * so empty rights does NOT yield a loginable storefront customer.
     * Confirmed by the registration path (User.php:1766), which also
     * writes the literal 'user'.
     */
    private const ROLE_TO_OXRIGHTS = [
        self::ROLE_ADMIN    => 'malladmin',
        self::ROLE_CUSTOMER => 'user',
    ];

    /** @var string|null */
    protected static $defaultName = 'oe:user:create';

    private AdminUserRepositoryInterface $repository;
    private PasswordServiceBridgeInterface $passwordService;

    public function __construct(
        AdminUserRepositoryInterface $repository,
        PasswordServiceBridgeInterface $passwordService
    ) {
        parent::__construct(null);
        $this->repository = $repository;
        $this->passwordService = $passwordService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new oxuser (admin or storefront customer).')
            ->setHelp(
                "Inserts a new oxuser row with OXACTIVE = 1. The --role flag selects\n"
                . "the OXRIGHTS value, mirroring the two-option dropdown in admin → Users:\n"
                . "  --role=admin    (default)  OXRIGHTS = 'malladmin'   (logs into admin)\n"
                . "  --role=customer            OXRIGHTS = 'user'        (storefront customer)\n\n"
                . "If --password is omitted, the value is prompted for interactively\n"
                . "(input is hidden, with a visible-input fallback for non-TTY contexts).\n\n"
                . "Aborts if a user with that username already exists. Use\n"
                . "oe:user:change-password to reset an existing user's password instead.\n\n"
                . "Note: a storefront customer created via this command has empty profile\n"
                . "fields (name, address, etc.). Use the storefront registration flow when\n"
                . 'those matter.'
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Login name (oxusername) for the new user.'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Password for the new user. If omitted, you will be prompted (hidden input).'
            )
            ->addOption(
                'role',
                null,
                InputOption::VALUE_REQUIRED,
                'Role: "admin" (malladmin, default) or "customer" (storefront).',
                self::ROLE_ADMIN
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        $password = $input->getOption('password');
        $role = (string) $input->getOption('role');

        if (!isset(self::ROLE_TO_OXRIGHTS[$role])) {
            $output->writeln(sprintf(
                '<error>Unknown role "%s". Expected one of: %s.</error>',
                $role,
                implode(', ', array_keys(self::ROLE_TO_OXRIGHTS))
            ));
            return self::EXIT_UNKNOWN_ROLE;
        }

        if ($password === null) {
            $password = $this->promptForPassword($input, $output);
        }
        if (!is_string($password) || $password === '') {
            $output->writeln('<error>Password must not be empty.</error>');
            return self::EXIT_EMPTY_PASSWORD;
        }

        if ($this->repository->findIdByUsername($username) !== null) {
            $output->writeln(sprintf(
                '<error>User "%s" already exists. Use oe:user:change-password to reset the password.</error>',
                $username
            ));
            return self::EXIT_USERNAME_EXISTS;
        }

        $hash = $this->passwordService->hash($password);
        $oxrights = self::ROLE_TO_OXRIGHTS[$role];
        try {
            $oxid = $this->repository->insertUser($username, $hash, $oxrights);
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>Failed to create user: %s</error>', $e->getMessage()));
            return self::EXIT_INSERT_FAILED;
        }

        $output->writeln(sprintf(
            '<info>User "%s" created with role "%s" (OXID %s).</info>',
            $username,
            $role,
            $oxid
        ));
        return self::EXIT_OK;
    }

    private function promptForPassword(InputInterface $input, OutputInterface $output): ?string
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('Password for the new user: ');
        $question->setHidden(true);
        // Allow visible-input fallback when stty isn't available (CI, piped
        // input, test runners). At a real interactive terminal stty works
        // and the prompt stays hidden; everywhere else the input still gets
        // through instead of silently returning null.
        $question->setHiddenFallback(true);
        $value = $helper->ask($input, $output, $question);
        return is_string($value) ? $value : null;
    }
}
