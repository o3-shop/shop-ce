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

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup;

use PHPUnit\Framework\TestCase;

/**
 * The setup wizard, the admin's System health page and source/README link to
 * docs.o3-shop.com. The docs moved twice (eshop/…, then user/installation/…),
 * and every move left released shops with 404s. Links must use the current
 * scheme, /en/latest/User/…, and never pages on www.o3-shop.com or on any
 * oxid-esales.com host (www., docs., …).
 *
 * This guards the URL scheme only. It does not check that a page or anchor
 * exists (that needs the network); check new links by hand.
 *
 * Refs: o3-shop/o3-shop#28
 */
final class DocumentationLinksTest extends TestCase
{
    private const CURRENT_DOCS_PREFIX = 'https://docs.o3-shop.com/en/latest/User/';

    /**
     * @return array<string,array{string}>
     */
    public function linkingFiles(): array
    {
        return [
            'setup wizard EN' => ['source/Setup/En/lang.php'],
            'setup wizard DE' => ['source/Setup/De/lang.php'],
            'admin System health' => ['source/Core/SystemRequirements.php'],
            'source/README' => ['source/README'],
        ];
    }

    /**
     * @dataProvider linkingFiles
     */
    public function testDocumentationLinksUseTheCurrentScheme(string $file): void
    {
        $urls = $this->docsUrls($file);

        $this->assertNotEmpty($urls, "Expected '$file' to link to docs.o3-shop.com.");
        foreach ($urls as $url) {
            $this->assertStringStartsWith(self::CURRENT_DOCS_PREFIX, $url, "Outdated documentation link in '$file'.");
        }
    }

    /**
     * @dataProvider linkingFiles
     */
    public function testNoLinksToOldManualPages(string $file): void
    {
        $contents = $this->read($file);

        $this->assertDoesNotMatchRegularExpression(
            '#https?://(www\.)?o3-shop\.com/[a-z]|https?://([a-z0-9-]+\.)*oxid-esales\.com/[a-z]#i',
            $contents,
            "'$file' links to a page on www.o3-shop.com or oxid-esales.com; their manual pages no longer exist."
        );
    }

    /**
     * @return array<int,string>
     */
    private function docsUrls(string $file): array
    {
        preg_match_all('#https?://docs\.o3-shop\.com[^\s\'"<>)`]*#', $this->read($file), $matches);

        return array_map(static fn (string $url): string => rtrim($url, '.,;:'), $matches[0]);
    }

    private function read(string $file): string
    {
        $path = dirname(__DIR__, 3) . '/' . $file;
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
