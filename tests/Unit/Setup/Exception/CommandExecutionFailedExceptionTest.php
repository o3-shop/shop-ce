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

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup\Exception;

use OxidEsales\EshopCommunity\Setup\Exception\CommandExecutionFailedException;

class CommandExecutionFailedExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testCanCreateSut()
    {
        new CommandExecutionFailedException('command');
    }

    public function testProvidesInformationAboutFailedCommand()
    {
        $this->expectException(
            CommandExecutionFailedException::class,
            "There was an error while executing 'test_string'."
        );

        throw new CommandExecutionFailedException('test_string');
    }

    public function testIsAbleToReturnCommand()
    {
        $sut = new CommandExecutionFailedException('command_name');

        $this->assertSame('command_name', $sut->getCommand());
    }

    public function testIsAbleToReturnTheReturnCode()
    {
        $sut = new CommandExecutionFailedException('command_name');
        $sut->setReturnCode(5);

        $this->assertSame(5, $sut->getReturnCode());
    }

    public function testReturnCodeIsZeroAsDefault()
    {
        $sut = new CommandExecutionFailedException('command_name');

        $this->assertSame(0, $sut->getReturnCode());
    }

    public function testIsAbleToReturnTheCommandErrorOutput()
    {
        $sut = new CommandExecutionFailedException('command_name');
        $sut->setCommandOutput(['line_1', 'line_2']);

        $this->assertSame("line_1\nline_2", $sut->getCommandOutput());
    }

    public function testErrorOutputNullAsDefault()
    {
        $sut = new CommandExecutionFailedException('command_name');

        $this->assertSame(null, $sut->getCommandOutput());
    }
}
