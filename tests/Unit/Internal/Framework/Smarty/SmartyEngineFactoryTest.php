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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Smarty;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration\SmartyConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyEngine;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyEngineFactory;
use PHPUnit\Framework\TestCase;
use Smarty;

class SmartyEngineFactoryTest extends TestCase
{
    public function testGetTemplateEngineWiresBuilderConfigurationStepsAndReturnsSmartyEngine(): void
    {
        // Spy builder records the order in which configuration setters are called.
        $callOrder = [];
        $smarty = new Smarty();

        $builder = $this->getMockBuilder(SmartyBuilder::class)
            ->onlyMethods([
                'setSettings',
                'setSecuritySettings',
                'registerPlugins',
                'registerPrefilters',
                'registerResources',
                'getSmarty',
            ])
            ->getMock();
        $recordCall = static function (string $name) use (&$callOrder) {
            return static function () use ($name, &$callOrder) {
                $callOrder[] = $name;
                // Return a mock that supports chaining — return $builder via late binding.
                return func_get_arg(0); // unused; real chain uses returnSelf below
            };
        };
        $builder->method('setSettings')->willReturnCallback(static function () use (&$callOrder, $builder) {
            $callOrder[] = 'setSettings';
            return $builder;
        });
        $builder->method('setSecuritySettings')->willReturnCallback(static function () use (&$callOrder, $builder) {
            $callOrder[] = 'setSecuritySettings';
            return $builder;
        });
        $builder->method('registerPlugins')->willReturnCallback(static function () use (&$callOrder, $builder) {
            $callOrder[] = 'registerPlugins';
            return $builder;
        });
        $builder->method('registerPrefilters')->willReturnCallback(static function () use (&$callOrder, $builder) {
            $callOrder[] = 'registerPrefilters';
            return $builder;
        });
        $builder->method('registerResources')->willReturnCallback(static function () use (&$callOrder, $builder) {
            $callOrder[] = 'registerResources';
            return $builder;
        });
        $builder->method('getSmarty')->willReturnCallback(static function () use (&$callOrder, $smarty) {
            $callOrder[] = 'getSmarty';
            return $smarty;
        });

        $config = $this->createMock(SmartyConfigurationInterface::class);
        $config->method('getSettings')->willReturn(['k' => 'v']);
        $config->method('getSecuritySettings')->willReturn(['secure_dir' => ['/x']]);
        $config->method('getPlugins')->willReturn(['/some/plugin']);
        $config->method('getPrefilters')->willReturn([]);
        $config->method('getResources')->willReturn([]);

        $factory = new SmartyEngineFactory($builder, $config);
        $engine = $factory->getTemplateEngine();

        $this->assertInstanceOf(SmartyEngine::class, $engine);
        // The wiring must consult every config aspect, in this exact order, before
        // pulling the configured Smarty instance out of the builder.
        $this->assertSame(
            [
                'setSettings',
                'setSecuritySettings',
                'registerPlugins',
                'registerPrefilters',
                'registerResources',
                'getSmarty',
            ],
            $callOrder
        );
    }
}
