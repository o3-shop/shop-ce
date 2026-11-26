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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Templating\Engine;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\ThemeTemplateEngineProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ThemeTemplateEngineProviderTest extends TestCase
{
    public function testExplicitTwig(): void
    {
        $this->assertSame('twig', $this->provider()->getEngineName('twigtheme'));
    }

    public function testExplicitSmarty(): void
    {
        $this->assertSame('smarty', $this->provider()->getEngineName('smartytheme'));
    }

    public function testMissingKeyDefaultsToSmarty(): void
    {
        $this->assertSame('smarty', $this->provider()->getEngineName('plaintheme'));
    }

    public function testChildWithoutKeyInheritsParent(): void
    {
        $this->assertSame('twig', $this->provider()->getEngineName('twigchild'));
    }

    public function testChildOwnKeyWins(): void
    {
        $this->assertSame('smarty', $this->provider()->getEngineName('smartychildoftwig'));
    }

    public function testOwnEngineName(): void
    {
        $provider = $this->provider();
        $this->assertSame('twig', $provider->getOwnEngineName('twigtheme'));
        $this->assertNull($provider->getOwnEngineName('twigchild'));
    }

    public function testUnknownThemeFallsBackToSmartyAndLogsWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $this->assertSame('smarty', $this->provider($logger)->getEngineName('does-not-exist'));
    }

    private function provider(?LoggerInterface $logger = null): ThemeTemplateEngineProvider
    {
        return new ThemeTemplateEngineProvider(
            $logger ?? $this->createMock(LoggerInterface::class),
            __DIR__ . '/Fixtures/views/'
        );
    }
}
