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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentWithOrphanSettingEvent;
use PHPUnit\Framework\TestCase;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationExtender;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShopEnvironmentConfigurationExtenderTest extends TestCase
{
    /** @var ShopEnvironmentConfigurationDaoInterface|ObjectProphecy */
    private $environmentDao;
    /** @var ShopEnvironmentConfigurationExtender */
    private $environmentExtension;
    /** @var ObjectProphecy|EventDispatcherInterface */
    private $eventDispatcher;
    /** @var int */
    private $shopId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->environmentDao = $this->prophesize(ShopEnvironmentConfigurationDaoInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->environmentExtension = new ShopEnvironmentConfigurationExtender(
            $this->environmentDao->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    public function testGetExtendedConfigurationWithEmpty(): void
    {
        $shopConfiguration = [
            'modules' => [
                'abc' => [],
            ],
        ];
        $environmentConfiguration = [];
        $this->environmentDao->get($this->shopId)->willReturn($environmentConfiguration);

        $result = $this->environmentExtension->getExtendedConfiguration($this->shopId, $shopConfiguration);

        $this->assertSame($shopConfiguration, $result);
    }

    public function testGetExtendedConfigurationWithModuleChains(): void
    {
        $shopConfiguration = [
            'modules' => [
                'abc' => [],
            ],
        ];
        $environmentConfiguration = [
            'moduleChains' => ['xyz' => 123],
        ];
        $this->environmentDao->get($this->shopId)->willReturn($environmentConfiguration);

        $result = $this->environmentExtension->getExtendedConfiguration($this->shopId, $shopConfiguration);

        $this->assertSame($shopConfiguration, $result);
    }

    public function testGetExtendedConfigurationWithModule(): void
    {
        $shopConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        'some-setting-1' => [
                            'group' => 'original-group-1',
                            'some-type' => 'original-type-1',
                            'value' => 'original-value-1',
                        ],
                    ],
                ],
            ],
        ];
        $environmentConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        'some-setting-1' => [
                            'value' => 'new-value-1',
                        ],
                    ],
                ],
            ],
        ];
        $expectedConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        'some-setting-1' => [
                            'group' => 'original-group-1',
                            'some-type' => 'original-type-1',
                            'value' => 'new-value-1',
                        ],
                    ],
                ],
            ],
        ];
        $this->environmentDao->get($this->shopId)->willReturn($environmentConfiguration);

        $result = $this->environmentExtension->getExtendedConfiguration($this->shopId, $shopConfiguration);

        $this->assertSame($expectedConfiguration, $result);
    }

    public function testGetExtendedConfigurationWithMissingSetting(): void
    {
        $missingSettingId = 'some-missing-setting-1';
        $shopConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        'some-setting-1' => [
                            'group' => 'original-group-1',
                            'some-type' => 'original-type-1',
                            'value' => 'original-value-1',
                        ],
                    ],
                ],
            ],
        ];
        $environmentConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        $missingSettingId => [
                            'value' => 'new-value-1',
                        ],
                    ],
                ],
            ],
        ];
        $this->environmentDao->get($this->shopId)->willReturn($environmentConfiguration);

        $this->environmentExtension->getExtendedConfiguration($this->shopId, $shopConfiguration);

        $this->eventDispatcher->dispatch(
            ShopEnvironmentWithOrphanSettingEvent::NAME,
            new ShopEnvironmentWithOrphanSettingEvent(
                $this->shopId,
                'abc',
                $missingSettingId
            )
        )
            ->shouldBeCalledOnce();
    }

    public function testGetExtendedConfigurationWithMissingModuleIdAndSetting(): void
    {
        $missingModuleId = 'missing-module-id';
        $missingSettingId = 'some-missing-setting-1';
        $shopConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        'some-setting-1' => [
                            'group' => 'original-group-1',
                            'some-type' => 'original-type-1',
                            'value' => 'original-value-1',
                        ],
                    ],
                ],
                'def' => [
                    'moduleSettings' => [
                        'some-setting-2' => [
                            'group' => 'original-group-2',
                            'some-type' => 'original-type-2',
                            'value' => 'original-value-2',
                        ],
                    ],
                ],
            ],
        ];
        $environmentConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        'some-setting-1' => [
                            'value' => 'new-value-1',
                        ],
                        $missingSettingId => [
                            'value' => 123,
                        ],
                    ],
                ],
                $missingModuleId => [
                    'moduleSettings' => [],
                ],
                'def' => [
                    'moduleSettings' => [
                        'some-setting-2' => [
                            'value' => 'new-value-2',
                        ]
                    ],
                ],
            ],
        ];
        $expectedConfiguration = [
            'modules' => [
                'abc' => [
                    'moduleSettings' => [
                        'some-setting-1' => [
                            'group' => 'original-group-1',
                            'some-type' => 'original-type-1',
                            'value' => 'new-value-1',
                        ],
                    ],
                ],
                'def' => [
                    'moduleSettings' => [
                        'some-setting-2' => [
                            'group' => 'original-group-2',
                            'some-type' => 'original-type-2',
                            'value' => 'new-value-2',
                        ],
                    ],
                ],
            ],
        ];
        $this->environmentDao->get($this->shopId)->willReturn($environmentConfiguration);

        $result = $this->environmentExtension->getExtendedConfiguration($this->shopId, $shopConfiguration);

        $this->assertSame($expectedConfiguration, $result);
    }
}
