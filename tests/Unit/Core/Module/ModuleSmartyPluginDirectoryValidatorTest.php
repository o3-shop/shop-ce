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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use OxidEsales\Eshop\Core\Exception\ModuleValidationException;
use OxidEsales\Eshop\Core\Module\ModuleSmartyPluginDirectories;
use OxidEsales\EshopCommunity\Core\Module\ModuleSmartyPluginDirectoryValidator;
use PHPUnit\Framework\TestCase;

class ModuleSmartyPluginDirectoryValidatorTest extends TestCase
{
    public function testValidatePassesWhenAllDirectoriesExist(): void
    {
        $directories = $this->createMock(ModuleSmartyPluginDirectories::class);
        $directories->method('getWithFullPath')->willReturn([
            sys_get_temp_dir(), // exists by definition
        ]);

        // Must not throw.
        (new ModuleSmartyPluginDirectoryValidator())->validate($directories);
        $this->assertTrue(true);
    }

    public function testValidateThrowsForMissingDirectory(): void
    {
        $bogus = sys_get_temp_dir() . '/__o3_shop_validator_test_does_not_exist_' . uniqid();

        $directories = $this->createMock(ModuleSmartyPluginDirectories::class);
        $directories->method('getWithFullPath')->willReturn([$bogus]);

        $this->expectException(ModuleValidationException::class);
        $this->expectExceptionMessage('does not exist');
        (new ModuleSmartyPluginDirectoryValidator())->validate($directories);
    }

    public function testValidateThrowsOnFirstMissingDirectory(): void
    {
        $bogus = sys_get_temp_dir() . '/__o3_shop_first_missing_' . uniqid();

        $directories = $this->createMock(ModuleSmartyPluginDirectories::class);
        $directories->method('getWithFullPath')->willReturn([
            $bogus,
            sys_get_temp_dir(), // also a real dir, but loop should break before reaching it
        ]);

        $this->expectException(ModuleValidationException::class);
        $this->expectExceptionMessage($bogus);
        (new ModuleSmartyPluginDirectoryValidator())->validate($directories);
    }
}
