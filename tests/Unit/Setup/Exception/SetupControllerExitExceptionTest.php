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

use OxidEsales\EshopCommunity\Setup\Exception\SetupControllerExitException;

class SetupControllerExitExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testCanCreateSut()
    {
        new SetupControllerExitException();
    }

    public function testIsThrowable()
    {
        $this->expectException(SetupControllerExitException::class);

        throw new SetupControllerExitException();
    }

    public function testIndicatesWasThrownWithoutArgumentsOfTemplateFile()
    {
        $expectedTemplateFileName = null;

        try {
            throw new SetupControllerExitException();
        } catch (SetupControllerExitException $exception) {
            $actualTemplateFileName = $exception->getTemplateFileName();
        }

        $this->assertSame($expectedTemplateFileName, $actualTemplateFileName);
    }

    public function testIndicatesWasThrownWithArgumentOfTemplateFile()
    {
        $expectedTemplateFileName = 'test.php';

        try {
            throw new SetupControllerExitException($expectedTemplateFileName);
        } catch (SetupControllerExitException $exception) {
            $actualTemplateFileName = $exception->getTemplateFileName();
        }

        $this->assertSame($expectedTemplateFileName, $actualTemplateFileName);
    }
}
