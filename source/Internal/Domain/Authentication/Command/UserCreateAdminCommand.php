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
 * Create a fresh admin (`malladmin`) user from the CLI. Companion to
 * `oe:user:change-password` — together they cover the "I am locked out
 * of the admin panel and there's no one to reset my password" scenario
 * filed at o3-shop/o3-shop#143.
 *
 * Usage:
 *   bin/oe-console oe:user:create-admin <username>
 *   bin/oe-console oe:user:create-admin <username> --password=<plaintext>
 *
 * Inserts the minimum rows required by `oxuser`'s NOT-NULL constraints;
 * everything else (name, address, phone, etc.) is left at the column
 * defaults — those are storefront niceties, not authentication. The new
 * user has `OXRIGHTS = 'malladmin'` and `OXACTIVE = 1`, so they can log
 * into the admin panel immediately. The hash goes through
 * PasswordServiceBridge — same code path as the admin panel.
 *
 * For regular (storefront) user creation, use the storefront
 * registration flow — that path collects the additional profile fields
 * a real customer needs.
 */
final class UserCreateAdminCommand extends Command
{
    public const EXIT_OK = 0;
    public const EXIT_USERNAME_EXISTS = 1;
    public const EXIT_EMPTY_PASSWORD = 2;
    public const EXIT_INSERT_FAILED = 3;

    /** @var string|null */
    protected static $defaultName = 'oe:user:create-admin';

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
            ->setDescription('Create a new admin (malladmin) user.')
            ->setHelp(
                "Inserts a new oxuser row with OXRIGHTS = 'malladmin' and OXACTIVE = 1,\n"
                . "so the user can log into the admin panel immediately. If --password is\n"
                . "omitted, the value is prompted for interactively (input is hidden).\n\n"
                . "Aborts if a user with that username already exists. Use\n"
                . 'oe:user:change-password to reset an existing user\'s password instead.'
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Login name (oxusername) for the new admin.'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Password for the new admin. If omitted, you will be prompted (hidden input).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        $password = $input->getOption('password');

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
        try {
            $oxid = $this->repository->insertAdmin($username, $hash);
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>Failed to create user: %s</error>', $e->getMessage()));
            return self::EXIT_INSERT_FAILED;
        }

        $output->writeln(sprintf(
            '<info>Admin user "%s" created (OXID %s).</info>',
            $username,
            $oxid
        ));
        return self::EXIT_OK;
    }

    private function promptForPassword(InputInterface $input, OutputInterface $output): ?string
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('Password for the new admin: ');
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
