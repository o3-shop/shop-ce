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

/**
 * Reset (or change) the password of an existing oxuser. Recovery tool for
 * the "admin locked himself out" case and for scripted password rotation
 * in CI / provisioning — see o3-shop/o3-shop#143.
 *
 * Usage:
 *   bin/oe-console oe:user:change-password <username>
 *   bin/oe-console oe:user:change-password <username> --password=<plaintext>
 *
 * Omitting `--password` triggers a hidden prompt (no echo). The hash goes
 * through PasswordServiceBridge — the same code path the admin panel uses
 * — so any user-facing login form will accept the new password unchanged.
 */
final class UserChangePasswordCommand extends Command
{
    public const EXIT_OK = 0;
    public const EXIT_USER_NOT_FOUND = 1;
    public const EXIT_EMPTY_PASSWORD = 2;

    /** @var string|null */
    protected static $defaultName = 'oe:user:change-password';

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
            ->setDescription('Change or reset the password of an existing oxuser.')
            ->setHelp(
                "Sets a new password for the given oxuser. If --password is omitted, the\n"
                . "value is prompted for interactively (input is hidden).\n\n"
                . "Use this to recover from a lost admin login (the only alternative is\n"
                . "manually UPDATE-ing oxuser, which is error-prone). The hash is produced\n"
                . 'by the same service the admin panel uses, so it is guaranteed compatible.'
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Login name (oxusername) of the user whose password to change.'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'New password. If omitted, you will be prompted interactively (hidden input).'
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

        $userId = $this->repository->findIdByUsername($username);
        if ($userId === null) {
            $output->writeln(sprintf('<error>User "%s" not found.</error>', $username));
            return self::EXIT_USER_NOT_FOUND;
        }

        $hash = $this->passwordService->hash($password);
        $this->repository->updatePassword($userId, $hash);

        $output->writeln(sprintf('<info>Password updated for user "%s".</info>', $username));
        return self::EXIT_OK;
    }

    private function promptForPassword(InputInterface $input, OutputInterface $output): ?string
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('New password: ');
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
