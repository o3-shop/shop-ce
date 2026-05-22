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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use Composer\IO\IOInterface;
use OxidEsales\EshopCommunity\Core\IncenteevScriptHandlerWrapper;

class IncenteevScriptHandlerWrapperTest extends \OxidTestCase
{
    public function testBuildParametersMethodExists(): void
    {
        $this->assertTrue(
            method_exists(IncenteevScriptHandlerWrapper::class, 'buildParameters'),
            'IncenteevScriptHandlerWrapper::buildParameters must exist'
        );
    }

    public function testBuildParametersIsStatic(): void
    {
        $reflection = new \ReflectionMethod(IncenteevScriptHandlerWrapper::class, 'buildParameters');
        $this->assertTrue($reflection->isStatic(), 'buildParameters must be a static method');
    }

    public function testBuildParametersAcceptsComposerEvent(): void
    {
        $reflection = new \ReflectionMethod(IncenteevScriptHandlerWrapper::class, 'buildParameters');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(\Composer\Script\Event::class, $type->getName());
    }

    public function testBuildParametersReturnsEarlyWhenHandlerClassAbsent(): void
    {
        $builder = new class () extends IncenteevScriptHandlerWrapper {
            protected static string $handlerClass = 'NonExistent\\Handler\\ThatDoesNotExist';
        };

        $io = $this->createMock(IOInterface::class);
        $io->expects($this->once())->method('writeError');

        $event = $this->createMock(\Composer\Script\Event::class);
        $event->method('getIO')->willReturn($io);
        $event->expects($this->never())->method('getComposer');

        $builder::buildParameters($event);
    }

    public function testBuildParametersDelegatesToHandlerWhenPresent(): void
    {
        $builder = new class () extends IncenteevScriptHandlerWrapper {
            public static bool $called = false;

            protected static string $handlerClass = IncenteevHandlerDouble::class;
        };

        $event = $this->createMock(\Composer\Script\Event::class);
        $event->expects($this->never())->method('getIO');

        IncenteevHandlerDouble::$called = false;
        $builder::buildParameters($event);

        $this->assertTrue(IncenteevHandlerDouble::$called, 'Handler double must have been called');
    }
}

class IncenteevHandlerDouble
{
    public static bool $called = false;

    public static function buildParameters(\Composer\Script\Event $event): void
    {
        self::$called = true;
    }
}
