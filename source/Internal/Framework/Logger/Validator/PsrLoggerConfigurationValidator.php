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

namespace OxidEsales\EshopCommunity\Internal\Framework\Logger\Validator;

use OxidEsales\EshopCommunity\Internal\Framework\Logger\Configuration\PsrLoggerConfigurationInterface;
use Psr\Log\LogLevel;

class PsrLoggerConfigurationValidator implements LoggerConfigurationValidatorInterface
{
    /**
     * @var array
     */
    private $validLogLevels = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY,
    ];

    /**
     * @param PsrLoggerConfigurationInterface $configuration
     */
    public function validate(PsrLoggerConfigurationInterface $configuration)
    {
        $this->validateLogLevel($configuration);
    }

    /**
     * @param PsrLoggerConfigurationInterface $configuration
     *
     * @throws \InvalidArgumentException if log level is not valid
     */
    private function validateLogLevel(PsrLoggerConfigurationInterface $configuration)
    {
        $logLevel = $configuration->getLogLevel();

        if (!in_array($logLevel, $this->validLogLevels, true)) {
            throw new \InvalidArgumentException(
                'Log level "' . var_export($logLevel, true) . '" is not a PSR-3 compliant log level'
            );
        }
    }
}
