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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigContext;
use PHPUnit\Framework\TestCase;

final class TwigContextTest extends TestCase
{
    public function testActiveThemeIdsChildThemeFirst(): void
    {
        $this->assertSame(['child', 'parent'], $this->context(['sCustomTheme' => 'child', 'sTheme' => 'parent'])->getActiveThemeIds());
        $this->assertSame(['parent'], $this->context(['sCustomTheme' => '', 'sTheme' => 'parent'])->getActiveThemeIds());
    }

    /**
     * @dataProvider compileCheckProvider
     */
    public function testTemplateCompileCheckMode(bool $checkTemplates, bool $productive, bool $expected): void
    {
        $this->assertSame(
            $expected,
            $this->context(['blCheckTemplates' => $checkTemplates], $productive)->getTemplateCompileCheckMode()
        );
    }

    public function compileCheckProvider(): array
    {
        return [
            'check on' => [true, false, true],
            'check off' => [false, false, false],
            'productive mode overrides' => [true, true, false],
        ];
    }

    private function context(array $params, bool $productive = false): TwigContext
    {
        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(static function (string $name) use ($params) {
            return $params[$name] ?? null;
        });
        $config->method('isProductiveMode')->willReturn($productive);

        return new TwigContext($config, $this->createMock(UtilsView::class));
    }
}
