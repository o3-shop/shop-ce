#!/usr/bin/env php
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

/**
 * Backwards Compatibility Snapshot Generator
 *
 * Scans the UnifiedNameSpaceClassMap and all edition classes reachable through it,
 * then produces two JSON snapshot files:
 *
 *   1. unified-namespace-snapshot.json  — the namespace map (unified → edition class + flags)
 *   2. api-signature-snapshot.json      — every public/protected method per edition class
 *
 * Usage:
 *   php tests/Unit/BackwardsCompatibility/generate-bc-snapshot.php
 */

$rootDir = dirname(__DIR__, 3);

// Composer autoloader — needed to resolve edition class names
require_once $rootDir . '/vendor/autoload.php';

$classMapFile = $rootDir . '/source/Core/Autoload/UnifiedNameSpaceClassMap.php';
$outputDir = __DIR__;

if (!file_exists($classMapFile)) {
    fwrite(STDERR, "ERROR: UnifiedNameSpaceClassMap.php not found at {$classMapFile}\n");
    exit(1);
}

// ── 1. Load the unified namespace map ────────────────────────────────────────

$classMap = require $classMapFile;

// ── 2. Build the namespace snapshot ──────────────────────────────────────────

$namespaceSnapshot = [];

$skipped = [];

foreach ($classMap as $unifiedClass => $meta) {
    $editionClass = $meta['editionClassName'];

    // Only include entries where the edition class actually exists.
    // Broken mappings that were inherited from the predecessor are not part
    // of the BC contract — they never worked in the first place.
    if (!class_exists($editionClass, true) && !interface_exists($editionClass, true)) {
        $skipped[] = "{$unifiedClass} -> {$editionClass}";
        continue;
    }

    $namespaceSnapshot[$unifiedClass] = [
        'editionClassName' => $meta['editionClassName'],
        'isAbstract'       => $meta['isAbstract'],
        'isInterface'      => $meta['isInterface'],
        'isDeprecated'     => $meta['isDeprecated'],
    ];
}

ksort($namespaceSnapshot);

// ── 3. Build the method signature snapshot ───────────────────────────────────

$methodSnapshot = [];
$errors = [];

foreach ($classMap as $unifiedClass => $meta) {
    $editionClass = $meta['editionClassName'];

    try {
        $reflection = new ReflectionClass($editionClass);
    } catch (ReflectionException $e) {
        $errors[] = "Could not reflect {$editionClass}: " . $e->getMessage();
        continue;
    }

    $methods = [];

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
        // Only include methods declared in the edition class itself or inherited
        // from other edition classes — skip methods from PHP internals.
        $declaringClass = $method->getDeclaringClass()->getName();
        if (strpos($declaringClass, 'OxidEsales\\') === false) {
            continue;
        }

        $visibility = $method->isPublic() ? 'public' : 'protected';

        $methods[$method->getName()] = [
            'visibility'     => $visibility,
            'isFinal'        => $method->isFinal(),
            'isStatic'       => $method->isStatic(),
            'declaringClass' => $declaringClass,
        ];
    }

    ksort($methods);

    $methodSnapshot[$unifiedClass] = [
        'editionClassName' => $editionClass,
        'isFinal'          => $reflection->isFinal(),
        'isAbstract'       => $reflection->isAbstract(),
        'methods'          => $methods,
    ];
}

ksort($methodSnapshot);

// ── 4. Write snapshot files ──────────────────────────────────────────────────

$nsFile = $outputDir . '/unified-namespace-snapshot.json';
$apiFile = $outputDir . '/api-signature-snapshot.json';

file_put_contents($nsFile, json_encode($namespaceSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
file_put_contents($apiFile, json_encode($methodSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

// ── 5. Report ────────────────────────────────────────────────────────────────

$classCount = count($methodSnapshot);
$methodCount = 0;
foreach ($methodSnapshot as $entry) {
    $methodCount += count($entry['methods']);
}

echo "Backwards Compatibility Snapshot generated successfully.\n";
echo "  Classes:  {$classCount}\n";
echo "  Methods:  {$methodCount}\n";
echo "  Files:\n";
echo "    {$nsFile}\n";
echo "    {$apiFile}\n";

if (!empty($skipped)) {
    echo "\n  Skipped (unloadable edition classes, not part of BC contract) (" . count($skipped) . "):\n";
    foreach ($skipped as $entry) {
        echo "    - {$entry}\n";
    }
}

if (!empty($errors)) {
    echo "\n  Warnings (" . count($errors) . "):\n";
    foreach ($errors as $err) {
        echo "    - {$err}\n";
    }
}
