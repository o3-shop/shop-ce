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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Templating\Engine;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\TemplateEngineName;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\TemplateEngineResolver;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\ThemeTemplateEngineProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class TemplateEngineResolverTest extends TestCase
{
    /** @var TemplateEngineInterface */
    private $smarty;

    /** @var TemplateEngineInterface */
    private $twig;

    protected function setUp(): void
    {
        $this->smarty = $this->createMock(TemplateEngineInterface::class);
        $this->twig = $this->createMock(TemplateEngineInterface::class);
    }

    public function testAdminModeAlwaysSmarty(): void
    {
        $provider = $this->createMock(ThemeTemplateEngineProviderInterface::class);
        $provider->expects($this->never())->method('getEngineName');

        $this->assertSame($this->smarty, $this->resolver($provider, [true])->getEngine());
    }

    public function testStorefrontFollowsTheme(): void
    {
        $this->assertSame($this->twig, $this->resolver($this->provider(TemplateEngineName::TWIG), [false])->getEngine());
        $this->assertSame($this->smarty, $this->resolver($this->provider(TemplateEngineName::SMARTY), [false])->getEngine());
    }

    public function testDecisionIsPerCall(): void
    {
        $resolver = $this->resolver($this->provider(TemplateEngineName::TWIG), [false, true, false]);

        $this->assertSame($this->twig, $resolver->getEngine());
        $this->assertSame($this->smarty, $resolver->getEngine());
        $this->assertSame($this->twig, $resolver->getEngine());
    }

    private function provider(string $engineName): ThemeTemplateEngineProviderInterface
    {
        $provider = $this->createMock(ThemeTemplateEngineProviderInterface::class);
        $provider->method('getEngineName')->willReturn($engineName);

        return $provider;
    }

    private function resolver(ThemeTemplateEngineProviderInterface $provider, array $adminModes): TemplateEngineResolver
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('isAdmin')->willReturnOnConsecutiveCalls(...$adminModes);

        return new TemplateEngineResolver(
            new ServiceLocator([
                TemplateEngineName::SMARTY => function () {
                    return $this->smarty;
                },
                TemplateEngineName::TWIG => function () {
                    return $this->twig;
                },
            ]),
            $provider,
            $context
        );
    }
}
