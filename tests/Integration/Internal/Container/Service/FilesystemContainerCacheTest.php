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

namespace Integration\Internal\Container\Service;

use OxidEsales\EshopCommunity\Internal\Container\ContainerBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ContainerCacheInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

final class FilesystemContainerCacheTest extends TestCase
{
    use ContainerTrait;

    protected function setUp(): void
    {
        $this->get(ContainerCacheInterface::class)->invalidate();
        parent::setUp();
    }

    public function testExists(): void
    {
        $cache = $this->get(ContainerCacheInterface::class);
        $cache->put($this->getContainer());

        $this->assertTrue(
            $cache->exists()
        );
    }

    public function testGet(): void
    {
        $cache = $this->get(ContainerCacheInterface::class);
        $cache->put($this->getContainer());

        $this->assertInstanceOf(
            \ProjectServiceContainer::class,
            $cache->get()
        );
    }

    public function testInvalidate(): void
    {
        $cache = $this->get(ContainerCacheInterface::class);
        $cache->put($this->getContainer());

        $cache->invalidate();

        $this->assertFalse(
            $cache->exists()
        );
    }

    private function getContainer(): \Symfony\Component\DependencyInjection\ContainerBuilder
    {
        $containerBuilder = (new ContainerBuilderFactory())->create();
        $symfonyContainer = $containerBuilder->getContainer();
        $symfonyContainer->compile();
        return $symfonyContainer;
    }
}
