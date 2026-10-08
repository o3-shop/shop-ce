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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Logger\Wrapper;

use Psr\Log\LoggerInterface;

class LoggerWrapper implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LoggerWrapper constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function emergency($message, array $context = [])
    {
        $this->writeSafely('emergency', $message, function () use ($message, $context) {
            $this->logger->emergency($message, $context);
        });
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function alert($message, array $context = [])
    {
        $this->writeSafely('alert', $message, function () use ($message, $context) {
            $this->logger->alert($message, $context);
        });
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function critical($message, array $context = [])
    {
        $this->writeSafely('critical', $message, function () use ($message, $context) {
            $this->logger->critical($message, $context);
        });
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function error($message, array $context = [])
    {
        $this->writeSafely('error', $message, function () use ($message, $context) {
            $this->logger->error($message, $context);
        });
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function warning($message, array $context = [])
    {
        $this->writeSafely('warning', $message, function () use ($message, $context) {
            $this->logger->warning($message, $context);
        });
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function notice($message, array $context = [])
    {
        $this->writeSafely('notice', $message, function () use ($message, $context) {
            $this->logger->notice($message, $context);
        });
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function info($message, array $context = [])
    {
        $this->writeSafely('info', $message, function () use ($message, $context) {
            $this->logger->info($message, $context);
        });
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     */
    public function debug($message, array $context = [])
    {
        $this->writeSafely('debug', $message, function () use ($message, $context) {
            $this->logger->debug($message, $context);
        });
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     */
    public function log($level, $message, array $context = [])
    {
        $this->writeSafely((string) $level, $message, function () use ($level, $message, $context) {
            $this->logger->log($level, $message, $context);
        });
    }

    /**
     * Logging is secondary: a failing log write (unwritable or missing log
     * dir, full disk) must not change the outcome of the request that tried
     * to log. Falls back to PHP's error_log(), never to this logger again.
     *
     * @param string $level
     * @param mixed  $message
     */
    private function writeSafely(string $level, $message, callable $write): void
    {
        try {
            $write();
        } catch (\Throwable $exception) {
            error_log(
                __METHOD__ . " - Writing a '$level' log entry failed: '"
                . $this->toSingleLine($exception->getMessage()) . "'. Original message: '"
                . $this->toSingleLine($this->messageToString($message)) . "'."
            );
        }
    }

    /**
     * @param mixed $message
     */
    private function messageToString($message): string
    {
        if (is_scalar($message) || $message === null || (is_object($message) && method_exists($message, '__toString'))) {
            return (string) $message;
        }

        return '(' . gettype($message) . ')';
    }

    private function toSingleLine(string $text): string
    {
        return trim((string) preg_replace('/\s*[\r\n]+\s*/', ' ', $text));
    }
}
