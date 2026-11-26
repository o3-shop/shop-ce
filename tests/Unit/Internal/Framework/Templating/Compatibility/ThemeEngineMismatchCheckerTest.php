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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Templating\Compatibility;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\Compatibility\ThemeEngineMismatchChecker;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\ThemeTemplateEngineProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ThemeEngineMismatchCheckerTest extends TestCase
{
    /** @var string */
    private $views;

    protected function setUp(): void
    {
        $this->views = sys_get_temp_dir() . '/themecompat_' . uniqid() . '/';
        $this->theme('parent', "'templateEngine' => 'twig'");
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->views));
    }

    public function testSmartyChildOfTwigParentIsReportedAndLogged(): void
    {
        $this->theme('child', "'parentTheme' => 'parent', 'templateEngine' => 'smarty'");
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains("'child'"));

        $this->assertSame(
            ['child' => 'child', 'parent' => 'parent', 'childEngine' => 'smarty', 'parentEngine' => 'twig'],
            $this->checker($logger)->getMismatch('child')
        );
    }

    public function testChildWithOwnTplFilesAndNoEngineKeyIsReported(): void
    {
        $this->theme('child', "'parentTheme' => 'parent'");
        mkdir($this->views . 'child/tpl/page', 0777, true);
        touch($this->views . 'child/tpl/page/x.tpl');

        $this->assertSame('smarty', $this->checker()->getMismatch('child')['childEngine']);
    }

    public function testChildWithoutOwnTemplatesIsFine(): void
    {
        $this->theme('child', "'parentTheme' => 'parent'");
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $this->assertNull($this->checker($logger)->getMismatch('child'));
    }

    public function testThemeWithoutParentAndMatchingEnginesAreFine(): void
    {
        $this->theme('twigchild', "'parentTheme' => 'parent', 'templateEngine' => 'twig'");

        $this->assertNull($this->checker()->getMismatch('parent'));
        $this->assertNull($this->checker()->getMismatch('twigchild'));
    }

    private function theme(string $id, string $entries): void
    {
        mkdir($this->views . $id, 0777, true);
        file_put_contents($this->views . $id . '/theme.php', "<?php\n\$aTheme = ['id' => '$id', $entries];\n");
    }

    private function checker(?LoggerInterface $logger = null): ThemeEngineMismatchChecker
    {
        $logger = $logger ?? $this->createMock(LoggerInterface::class);

        return new ThemeEngineMismatchChecker(new ThemeTemplateEngineProvider($logger, $this->views), $logger, $this->views);
    }
}
