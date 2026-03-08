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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\BackwardsCompatibility;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Backwards Compatibility Contract Test
 *
 * Verifies the "safe harbour" guarantee: every public/protected method from the
 * v6 API surface must still exist and must remain overridable (not final).
 *
 * The contract is defined by two JSON snapshot files:
 *   - unified-namespace-snapshot.json   (unified → edition class mapping)
 *   - api-signature-snapshot.json       (method-level API surface)
 *
 * To regenerate snapshots after intentional API additions:
 *   php tests/Unit/BackwardsCompatibility/generate-bc-snapshot.php
 */
class BackwardsCompatibilityTest extends TestCase
{
    /** @var string */
    private static $snapshotDir;

    /** @var array */
    private static $namespaceSnapshot;

    /** @var array */
    private static $apiSnapshot;

    /** @var array */
    private static $currentClassMap;

    public static function setUpBeforeClass(): void
    {
        self::$snapshotDir = __DIR__;

        $nsFile  = self::$snapshotDir . '/unified-namespace-snapshot.json';
        $apiFile = self::$snapshotDir . '/api-signature-snapshot.json';

        if (!file_exists($nsFile)) {
            self::fail(
                "Unified namespace snapshot not found at {$nsFile}.\n" .
                "Run: php tests/Unit/BackwardsCompatibility/generate-bc-snapshot.php"
            );
        }
        if (!file_exists($apiFile)) {
            self::fail(
                "API signature snapshot not found at {$apiFile}.\n" .
                "Run: php tests/Unit/BackwardsCompatibility/generate-bc-snapshot.php"
            );
        }

        self::$namespaceSnapshot = json_decode(file_get_contents($nsFile), true);
        self::$apiSnapshot       = json_decode(file_get_contents($apiFile), true);

        // Load the current class map for comparison
        $classMapFile = dirname(__DIR__, 3) . '/source/Core/Autoload/UnifiedNameSpaceClassMap.php';
        if (file_exists($classMapFile)) {
            self::$currentClassMap = require $classMapFile;
        } else {
            self::$currentClassMap = [];
        }
    }

    // ─── Unified Namespace Map Tests ─────────────────────────────────────────

    /**
     * Every unified namespace class that existed in the snapshot must still
     * be present in the current UnifiedNameSpaceClassMap.
     */
    public function testAllUnifiedNamespaceEntriesStillExist(): void
    {
        $missing = [];

        foreach (self::$namespaceSnapshot as $unifiedClass => $meta) {
            if (!isset(self::$currentClassMap[$unifiedClass])) {
                $missing[] = $unifiedClass;
            }
        }

        $this->assertEmpty(
            $missing,
            "The following unified namespace classes were removed from UnifiedNameSpaceClassMap:\n  - " .
            implode("\n  - ", $missing)
        );
    }

    /**
     * Every edition class referenced by the unified namespace map must be
     * loadable (i.e. the PHP file exists and can be autoloaded).
     */
    public function testAllEditionClassTargetsAreLoadable(): void
    {
        $unloadable = [];

        foreach (self::$currentClassMap as $unifiedClass => $meta) {
            // Only check entries that were part of the original snapshot
            if (!isset(self::$namespaceSnapshot[$unifiedClass])) {
                continue;
            }

            $editionClass = $meta['editionClassName'];
            if (!class_exists($editionClass, true) && !interface_exists($editionClass, true)) {
                $unloadable[] = "{$unifiedClass} -> {$editionClass}";
            }
        }

        $this->assertEmpty(
            $unloadable,
            "The following edition classes cannot be loaded:\n  - " .
            implode("\n  - ", $unloadable)
        );
    }

    // ─── Method Existence Tests ──────────────────────────────────────────────

    /**
     * Every public/protected method from the snapshot must still exist on the
     * edition class.
     */
    public function testAllSnapshotMethodsStillExist(): void
    {
        $missing = [];

        foreach (self::$apiSnapshot as $unifiedClass => $classEntry) {
            $editionClass = $classEntry['editionClassName'];

            if (!class_exists($editionClass, true) && !interface_exists($editionClass, true)) {
                // Class itself is missing — covered by testAllEditionClassTargetsAreLoadable
                continue;
            }

            $reflection = new ReflectionClass($editionClass);

            foreach ($classEntry['methods'] as $methodName => $methodMeta) {
                if (!$reflection->hasMethod($methodName)) {
                    $missing[] = "{$editionClass}::{$methodName}()";
                }
            }
        }

        $this->assertEmpty(
            $missing,
            "The following methods from the BC snapshot no longer exist:\n  - " .
            implode("\n  - ", $missing)
        );
    }

    // ─── Overridability Tests ────────────────────────────────────────────────

    /**
     * No edition class that was non-final in the snapshot may become final.
     */
    public function testNoClassBecameFinal(): void
    {
        $violations = [];

        foreach (self::$apiSnapshot as $unifiedClass => $classEntry) {
            $editionClass = $classEntry['editionClassName'];

            if (!class_exists($editionClass, true) && !interface_exists($editionClass, true)) {
                continue;
            }

            if ($classEntry['isFinal']) {
                // Was already final in snapshot — skip
                continue;
            }

            $reflection = new ReflectionClass($editionClass);
            if ($reflection->isFinal()) {
                $violations[] = $editionClass;
            }
        }

        $this->assertEmpty(
            $violations,
            "The following classes were made final, breaking the BC contract:\n  - " .
            implode("\n  - ", $violations)
        );
    }

    /**
     * No method that was non-final in the snapshot may become final.
     */
    public function testNoMethodBecameFinal(): void
    {
        $violations = [];

        foreach (self::$apiSnapshot as $unifiedClass => $classEntry) {
            $editionClass = $classEntry['editionClassName'];

            if (!class_exists($editionClass, true) && !interface_exists($editionClass, true)) {
                continue;
            }

            $reflection = new ReflectionClass($editionClass);

            foreach ($classEntry['methods'] as $methodName => $methodMeta) {
                if ($methodMeta['isFinal']) {
                    // Was already final in snapshot — skip
                    continue;
                }

                if (!$reflection->hasMethod($methodName)) {
                    // Missing methods are covered by testAllSnapshotMethodsStillExist
                    continue;
                }

                $method = $reflection->getMethod($methodName);
                if ($method->isFinal()) {
                    $violations[] = "{$editionClass}::{$methodName}()";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "The following methods were made final, breaking the BC contract:\n  - " .
            implode("\n  - ", $violations)
        );
    }

    /**
     * No method may have its visibility reduced (e.g. public → protected).
     */
    public function testNoMethodVisibilityReduced(): void
    {
        $violations = [];

        foreach (self::$apiSnapshot as $unifiedClass => $classEntry) {
            $editionClass = $classEntry['editionClassName'];

            if (!class_exists($editionClass, true) && !interface_exists($editionClass, true)) {
                continue;
            }

            $reflection = new ReflectionClass($editionClass);

            foreach ($classEntry['methods'] as $methodName => $methodMeta) {
                if (!$reflection->hasMethod($methodName)) {
                    continue;
                }

                $method = $reflection->getMethod($methodName);

                // public → protected or private is a BC break
                if ($methodMeta['visibility'] === 'public' && !$method->isPublic()) {
                    $violations[] = "{$editionClass}::{$methodName}() was public, now " .
                        ($method->isProtected() ? 'protected' : 'private');
                }

                // protected → private is a BC break
                if ($methodMeta['visibility'] === 'protected' && $method->isPrivate()) {
                    $violations[] = "{$editionClass}::{$methodName}() was protected, now private";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "The following methods had their visibility reduced, breaking the BC contract:\n  - " .
            implode("\n  - ", $violations)
        );
    }
}
