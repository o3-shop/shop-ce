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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Snapshot;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawComposerJsonFetcher;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawRepoFetchException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshotBuilder;
use PHPUnit\Framework\TestCase;

class FromSnapshotBuilderTest extends TestCase
{
    public function testPostFoldInRootBuildsDirectFromPin(): void
    {
        // o3-shop@v1.6.1 (post-fold-in): tier-0 pins live directly
        // in the root composer.json. No metapackage involved.
        $fetcher = new FakeRawComposerJsonFetcher([
            'o3-shop/o3-shop|v1.6.1' => [
                'require' => [
                    'composer/ca-bundle' => '1.3.2',
                    'o3-shop/shop-ce' => 'v1.6.1',
                    'o3-shop/o3-theme' => '^v1.3.0',
                    'o3-shop/wave-theme' => '^v1.2.0',
                    'o3-shop/shop-demodata-ce' => '^v1.4.0',
                    'o3-shop/shop-facts' => 'v1.0.4',
                    'o3-shop/gdpr-optin-module' => 'v1.0.1',
                    'o3-shop/usercentrics' => 'v1.0.0',
                    'o3-shop/tinymce-editor' => 'v1.0.0',
                    'symfony/console' => 'v3.4.47',
                ],
                'require-dev' => [
                    'o3-shop/testing-library' => '^1.2.0',
                    'o3-shop/shop-ide-helper' => '^1.0.0',
                    'incenteev/composer-parameter-handler' => '^v2.0.0',
                ],
            ],
        ]);

        $snapshot = (new FromSnapshotBuilder($fetcher))->build('v1.6.1');

        $this->assertFalse($snapshot->usedPreFoldInIndirection());
        $this->assertNull($snapshot->preFoldInMetapackageVersion());
        $this->assertSame([
            'gdpr-optin-module' => 'v1.0.1',
            'o3-theme' => '^v1.3.0',
            'shop-ce' => 'v1.6.1',
            'shop-demodata-ce' => '^v1.4.0',
            'shop-facts' => 'v1.0.4',
            'shop-ide-helper' => '^1.0.0',
            'testing-library' => '^1.2.0',
            'tinymce-editor' => 'v1.0.0',
            'usercentrics' => 'v1.0.0',
            'wave-theme' => '^v1.2.0',
        ], $snapshot->fromPin());
    }

    public function testPreFoldInRootTriggersMetapackageIndirection(): void
    {
        // o3-shop@v1.6.0 (pre-fold-in): the root composer.json only
        // requires the metapackage. The actual tier-0 pins live one
        // level deeper in shop-metapackage-ce@v1.6.0.
        $fetcher = new FakeRawComposerJsonFetcher([
            'o3-shop/o3-shop|v1.6.0' => [
                'require' => [
                    'o3-shop/shop-metapackage-ce' => 'v1.6.0',
                ],
                'require-dev' => [
                    'o3-shop/testing-library' => '^1.2.0',
                    'o3-shop/shop-ide-helper' => '^1.0.0',
                    'incenteev/composer-parameter-handler' => '^v2.0.0',
                ],
            ],
            'o3-shop/shop-metapackage-ce|v1.6.0' => [
                'require' => [
                    'composer/ca-bundle' => '1.3.2',
                    'o3-shop/shop-ce' => 'v1.6.0',
                    'o3-shop/o3-theme' => '^v1.3.0',
                    'o3-shop/wave-theme' => '^v1.2.0',
                    'o3-shop/shop-demodata-ce' => '^v1.4.0',
                    'o3-shop/shop-facts' => 'v1.0.4',
                    'o3-shop/gdpr-optin-module' => 'v1.0.1',
                    'o3-shop/usercentrics' => 'v1.0.0',
                    'o3-shop/tinymce-editor' => 'v1.0.0',
                    'symfony/console' => 'v3.4.47',
                ],
            ],
        ]);

        $snapshot = (new FromSnapshotBuilder($fetcher))->build('v1.6.0');

        $this->assertTrue($snapshot->usedPreFoldInIndirection());
        $this->assertSame('v1.6.0', $snapshot->preFoldInMetapackageVersion());
        // Metapackage entry itself is dropped from from_pin.
        $this->assertArrayNotHasKey('shop-metapackage-ce', $snapshot->fromPin());
        // Tier-0 pins from the metapackage are present.
        $pins = $snapshot->fromPin();
        $this->assertSame('v1.6.0', $pins['shop-ce']);
        $this->assertSame('^v1.3.0', $pins['o3-theme']);
        $this->assertSame('v1.0.1', $pins['gdpr-optin-module']);
        // Root's own require-dev pins also appear.
        $this->assertSame('^1.2.0', $pins['testing-library']);
        $this->assertSame('^1.0.0', $pins['shop-ide-helper']);
    }

    public function testRequireDevOnlyEntriesAppearInFromPin(): void
    {
        // shop-ide-helper sits exclusively in o3-shop's require-dev.
        // It should still appear in from_pin (we ship dev-tooling
        // releases, so it's release-eligible).
        $fetcher = new FakeRawComposerJsonFetcher([
            'o3-shop/o3-shop|v1.6.1' => [
                'require' => ['o3-shop/shop-ce' => 'v1.6.1'],
                'require-dev' => ['o3-shop/shop-ide-helper' => '^1.0.0'],
            ],
        ]);

        $snapshot = (new FromSnapshotBuilder($fetcher))->build('v1.6.1');

        $this->assertArrayHasKey('shop-ide-helper', $snapshot->fromPin());
        $this->assertSame('^1.0.0', $snapshot->fromPin()['shop-ide-helper']);
    }

    public function testRootPinOverwritesMetapackagePinForSamePackage(): void
    {
        // Hypothetical: if both root and metapackage pin the same
        // tier-0 dep, root wins (it's more direct). Documents the
        // resolution semantics for the rare overlap.
        $fetcher = new FakeRawComposerJsonFetcher([
            'o3-shop/o3-shop|v1.6.0' => [
                'require' => ['o3-shop/shop-metapackage-ce' => 'v1.6.0'],
                'require-dev' => ['o3-shop/shop-ce' => 'dev-override'],
            ],
            'o3-shop/shop-metapackage-ce|v1.6.0' => [
                'require' => ['o3-shop/shop-ce' => 'v1.6.0'],
            ],
        ]);

        $snapshot = (new FromSnapshotBuilder($fetcher))->build('v1.6.0');

        $this->assertSame('dev-override', $snapshot->fromPin()['shop-ce']);
    }

    public function testNonO3ShopPackagesAreFilteredOut(): void
    {
        $fetcher = new FakeRawComposerJsonFetcher([
            'o3-shop/o3-shop|v1.6.1' => [
                'require' => [
                    'symfony/console' => 'v3.4.47',
                    'doctrine/dbal' => '2.12.1',
                    'o3-shop/shop-ce' => 'v1.6.1',
                ],
            ],
        ]);

        $snapshot = (new FromSnapshotBuilder($fetcher))->build('v1.6.1');

        $this->assertSame(['shop-ce' => 'v1.6.1'], $snapshot->fromPin());
    }

    public function testFromPinIsAlphabeticallySorted(): void
    {
        $fetcher = new FakeRawComposerJsonFetcher([
            'o3-shop/o3-shop|v1.6.1' => [
                'require' => [
                    'o3-shop/wave-theme' => '^v1.2.0',
                    'o3-shop/gdpr-optin-module' => 'v1.0.1',
                    'o3-shop/shop-ce' => 'v1.6.1',
                ],
            ],
        ]);

        $snapshot = (new FromSnapshotBuilder($fetcher))->build('v1.6.1');

        $this->assertSame(
            ['gdpr-optin-module', 'shop-ce', 'wave-theme'],
            array_keys($snapshot->fromPin())
        );
    }

    public function testFetcherFailurePropagates(): void
    {
        $fetcher = new FakeRawComposerJsonFetcher([]);  // no fixtures
        $this->expectException(RawRepoFetchException::class);
        (new FromSnapshotBuilder($fetcher))->build('v9.9.9');
    }
}

/**
 * In-memory fake. Looks up canned composer.json arrays keyed by
 * "<package>|<ref>" and returns them. Throws when no fixture matches.
 */
final class FakeRawComposerJsonFetcher implements RawComposerJsonFetcher
{
    /** @var array<string,array<string,mixed>> */
    private array $fixtures;

    /** @var array<int,string> ordered list of fetch keys actually requested */
    public array $calls = [];

    /**
     * @param array<string,array<string,mixed>> $fixtures key "<package>|<ref>" => parsed composer.json
     */
    public function __construct(array $fixtures)
    {
        $this->fixtures = $fixtures;
    }

    public function fetch(string $packageName, string $ref): array
    {
        $key = $packageName . '|' . $ref;
        $this->calls[] = $key;
        if (!isset($this->fixtures[$key])) {
            throw new RawRepoFetchException("no fixture for {$key}");
        }
        return $this->fixtures[$key];
    }
}
