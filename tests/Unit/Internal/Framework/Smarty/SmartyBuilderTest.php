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

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyBuilder;
use PHPUnit\Framework\TestCase;
use Smarty;

class SmartyBuilderTest extends TestCase
{
    public function testGetSmartyReturnsAFreshSmartyInstance(): void
    {
        $smarty = (new SmartyBuilder())->getSmarty();
        $this->assertInstanceOf(Smarty::class, $smarty);
    }

    public function testSetSettingsAssignsAllPassedKeysOntoSmarty(): void
    {
        $builder = new SmartyBuilder();
        $smarty = $builder
            ->setSettings([
                'left_delimiter'  => '[[',
                'right_delimiter' => ']]',
            ])
            ->getSmarty();

        $this->assertSame('[[', $smarty->left_delimiter);
        $this->assertSame(']]', $smarty->right_delimiter);
    }

    public function testSetSettingsIsFluent(): void
    {
        $builder = new SmartyBuilder();
        $this->assertSame($builder, $builder->setSettings([]));
    }

    public function testSetSecuritySettingsScalarValueIsAssignedDirectly(): void
    {
        $builder = new SmartyBuilder();
        $smarty = $builder
            ->setSecuritySettings(['secure_dir' => ['/some/path']])
            ->getSmarty();

        // For non-array entries the scalar is assigned directly.
        $smarty2 = (new SmartyBuilder())
            ->setSecuritySettings(['security' => true])
            ->getSmarty();
        $this->assertTrue($smarty2->security);
    }

    public function testSetSecuritySettingsMergesNestedArrays(): void
    {
        $builder = new SmartyBuilder();
        $smarty = $builder->getSmarty();
        // Pre-populate the original $secure_dir array under a known sub-key
        $smarty->trusted_dir = ['shared' => ['/old']];

        $builder->setSecuritySettings([
            'trusted_dir' => [
                'shared' => ['/new'], // sub-key is array → array_merge with existing
            ],
        ]);

        $this->assertSame(['/old', '/new'], $smarty->trusted_dir['shared']);
    }

    public function testSetSecuritySettingsAssignsScalarSubValueDirectly(): void
    {
        $builder = new SmartyBuilder();
        $smarty = $builder->getSmarty();
        $smarty->trusted_dir = []; // initialise as array so sub-key access works

        $builder->setSecuritySettings([
            'trusted_dir' => [
                'mode' => 'strict', // sub-value is scalar → direct assignment
            ],
        ]);

        $this->assertSame('strict', $smarty->trusted_dir['mode']);
    }

    public function testRegisterResourcesIsFluentAndCallsRegisterResource(): void
    {
        $builder = new SmartyBuilder();
        $smarty = $builder->getSmarty();
        // Smarty 2.6 register_resource takes a name + 4-callable array
        $callable = [
            static fn () => '',
            static fn () => 0,
            static fn () => true,
            static fn () => true,
        ];

        $result = $builder->registerResources(['mockres' => $callable]);
        $this->assertSame($builder, $result);
        // The registered resource is stored in $smarty->_plugins['resource']
        $this->assertArrayHasKey('mockres', $smarty->_plugins['resource'] ?? []);
    }

    public function testRegisterPrefiltersSkipsMissingFiles(): void
    {
        $builder = new SmartyBuilder();
        // Non-existent file → must be skipped without error.
        $result = $builder->registerPrefilters([
            'nope' => '/path/that/does/not/exist/' . uniqid() . '.php',
        ]);
        $this->assertSame($builder, $result);
    }

    public function testRegisterPluginsPrependsCallerArrayToSmartyPluginsDir(): void
    {
        $builder = new SmartyBuilder();
        $smarty = $builder->getSmarty();
        $smarty->plugins_dir = ['/existing/plugins'];

        $builder->registerPlugins(['/my/custom/plugins']);

        $this->assertSame(
            ['/my/custom/plugins', '/existing/plugins'],
            $smarty->plugins_dir,
            'New plugin dirs prepend so they take precedence over Smarty defaults.'
        );
    }
}
