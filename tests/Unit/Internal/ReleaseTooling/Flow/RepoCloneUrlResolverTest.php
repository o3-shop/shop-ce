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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\RepoCloneUrlResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RepoCloneUrlResolverTest extends TestCase
{
    public function testHttpsOriginYieldsHttpsCloneUrl(): void
    {
        $exec = new FakeProcessExecutor([
            'git config --get remote.origin.url' => new ProcessOutcome(0, "https://github.com/o3-shop/shop-ce\n", ''),
        ]);
        $resolver = RepoCloneUrlResolver::fromRepoOrigin($exec, '/tmp/shop-ce');

        $this->assertSame(RepoCloneUrlResolver::SCHEME_HTTPS, $resolver->scheme());
        $this->assertSame('https://github.com/o3-shop/shop-ce.git', $resolver->urlFor('o3-shop/shop-ce'));
        $this->assertSame('https://github.com/o3-shop/testing-library.git', $resolver->urlFor('o3-shop/testing-library'));
    }

    public function testSshOriginYieldsSshCloneUrl(): void
    {
        $exec = new FakeProcessExecutor([
            'git config --get remote.origin.url' => new ProcessOutcome(0, "git@github.com:o3-shop/shop-ce.git\n", ''),
        ]);
        $resolver = RepoCloneUrlResolver::fromRepoOrigin($exec, '/tmp/shop-ce');

        $this->assertSame(RepoCloneUrlResolver::SCHEME_SSH, $resolver->scheme());
        $this->assertSame('git@github.com:o3-shop/shop-ce.git', $resolver->urlFor('o3-shop/shop-ce'));
    }

    public function testCaseRenamePreservedInUrl(): void
    {
        $resolver = new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS);
        // PackageRepoSlug::RENAMES covers o3-theme, mink-selenium-driver, php-selenium
        $this->assertSame('https://github.com/o3-shop/o3-Theme.git', $resolver->urlFor('o3-shop/o3-theme'));
        $this->assertSame(
            'https://github.com/o3-shop/MinkSeleniumDriver.git',
            $resolver->urlFor('o3-shop/mink-selenium-driver')
        );
        $this->assertSame('https://github.com/o3-shop/PHP-Selenium.git', $resolver->urlFor('o3-shop/php-selenium'));
    }

    public function testCaseRenamePreservedInSshUrl(): void
    {
        $resolver = new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_SSH);
        $this->assertSame('git@github.com:o3-shop/o3-Theme.git', $resolver->urlFor('o3-shop/o3-theme'));
    }

    public function testUnknownSchemeRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unknown clone-URL scheme');
        new RepoCloneUrlResolver('ftp');
    }

    public function testUnsupportedOriginUrlAborts(): void
    {
        $exec = new FakeProcessExecutor([
            'git config --get remote.origin.url' => new ProcessOutcome(0, "git://example.org/shop-ce\n", ''),
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsupported origin URL');
        RepoCloneUrlResolver::fromRepoOrigin($exec, '/tmp/shop-ce');
    }

    public function testGitConfigFailureAborts(): void
    {
        $exec = new FakeProcessExecutor([
            'git config --get remote.origin.url' => new ProcessOutcome(1, '', "fatal: not a git repo\n"),
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not read git remote.origin.url');
        RepoCloneUrlResolver::fromRepoOrigin($exec, '/tmp/shop-ce');
    }
}
