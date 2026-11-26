#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * O3-Shop pre-update helper — fold the compilation metapackage into the shop root.
 *
 * Background (o3-shop/o3-shop#162, #149):
 * Historically an O3-Shop install's root composer.json only `require`s
 * `o3-shop/shop-metapackage-ce`, and that metapackage carries the full pinned
 * component set (o3-shop/* components, Symfony, Doctrine, …) plus the
 * `replace: oxid-esales/oxideshop-metapackage-ce` lineage marker.
 *
 * We are moving to a self-contained root: the components live directly in the
 * shop root composer.json and the metapackage indirection is dropped. For a
 * *fresh* install the new project template already ships that way; an *existing*
 * install, however, still `require`s the metapackage by name, so a plain
 * `composer update` to the metapackage-free release would no longer resolve.
 *
 * This script bridges that gap. Run it ONCE, before `composer update`, on an
 * existing install. It rewrites the root composer.json to the self-contained
 * shape by inlining the *currently installed* metapackage's own `require` and
 * `replace` (read from vendor/, so the exact pinned set is preserved — no
 * versions are hard-coded here), then removes the metapackage self-require.
 * Afterwards `composer update` resolves the same component set directly.
 *
 * Safe to re-run: if the metapackage require is already gone it does nothing.
 *
 * Usage:
 *   php bin/pre-update.php [--root=<dir>] [--dry-run]
 *
 *   --root=<dir>  Shop root that holds composer.json + vendor/ (default: cwd).
 *   --dry-run     Show what would change; write nothing.
 *
 * Exit codes: 0 = done or nothing to do; 1 = error (bad input / metapackage
 * not installed / write failure).
 */

const METAPACKAGE = 'o3-shop/shop-metapackage-ce';

/**
 * Fold a metapackage's dependency set into a root composer manifest.
 *
 * Pure transformation so it can be unit-tested without touching the filesystem.
 * The metapackage's pins win on conflict (it is the compilation), and the
 * metapackage self-require is always dropped.
 *
 * @param array $root        decoded root composer.json
 * @param array $metapackage decoded metapackage composer.json
 * @return array{0: array, 1: array{require:int, replace:int}} new root + counts
 */
function foldMetapackageIntoRoot(array $root, array $metapackage): array
{
    $require = $root['require'] ?? [];
    unset($require[METAPACKAGE]);

    $addedRequire = 0;
    foreach (($metapackage['require'] ?? []) as $package => $constraint) {
        if ($package === METAPACKAGE) {
            continue; // never re-introduce the metapackage itself
        }
        $require[$package] = $constraint;
        $addedRequire++;
    }

    $replace = $root['replace'] ?? [];
    $addedReplace = 0;
    foreach (($metapackage['replace'] ?? []) as $package => $constraint) {
        $replace[$package] = $constraint;
        $addedReplace++;
    }

    $root['require'] = $require;
    if ($replace !== []) {
        $root['replace'] = $replace;
    }

    return [$root, ['require' => $addedRequire, 'replace' => $addedReplace]];
}

/**
 * Decode a composer.json file, aborting with a clear message on failure.
 */
function loadComposerJson(string $path): array
{
    if (!is_file($path)) {
        fail("Not found: $path");
    }
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        fail("Invalid JSON: $path (" . json_last_error_msg() . ')');
    }
    return $data;
}

/**
 * Encode a composer manifest the way Composer itself writes it:
 * 4-space indent, unescaped slashes/unicode, trailing newline.
 */
function encodeComposerJson(array $data): string
{
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function fail(string $message): void
{
    fwrite(STDERR, "ERROR: $message\n");
    exit(1);
}

function info(string $message): void
{
    fwrite(STDOUT, "$message\n");
}

// ---- CLI --------------------------------------------------------------------

function main(array $argv): void
{
    $root = getcwd();
    $dryRun = false;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--dry-run') {
            $dryRun = true;
        } elseif (str_starts_with($arg, '--root=')) {
            $root = substr($arg, strlen('--root='));
        } else {
            fail("Unknown argument: $arg\nUsage: php bin/pre-update.php [--root=<dir>] [--dry-run]");
        }
    }

    $rootFile = rtrim($root, '/') . '/composer.json';
    $metaFile = rtrim($root, '/') . '/vendor/' . METAPACKAGE . '/composer.json';

    $rootData = loadComposerJson($rootFile);

    if (!isset($rootData['require'][METAPACKAGE])) {
        info("Nothing to do: '" . METAPACKAGE . "' is not required in $rootFile.");
        info('This install is already self-contained (or was migrated before). No changes made.');
        exit(0);
    }

    if (!is_file($metaFile)) {
        fail(
            "'" . METAPACKAGE . "' is required but not installed at:\n  $metaFile\n"
            . "Run 'composer install' first so the exact pinned set can be read from vendor/."
        );
    }

    $metaData = loadComposerJson($metaFile);
    [$newRoot, $counts] = foldMetapackageIntoRoot($rootData, $metaData);

    info('Folding ' . METAPACKAGE . ' into ' . $rootFile . ':');
    info("  - remove require '" . METAPACKAGE . "'");
    info("  - inline {$counts['require']} pinned require entries from the metapackage");
    info("  - add {$counts['replace']} replace entr" . ($counts['replace'] === 1 ? 'y' : 'ies'));

    if ($dryRun) {
        info("\n--dry-run: no files written. Resulting composer.json would be:\n");
        info(encodeComposerJson($newRoot));
        exit(0);
    }

    $backup = $rootFile . '.pre-update.bak';
    if (is_file($backup)) {
        fail("Backup already exists: $backup\nRemove it (or restore from it) before re-running.");
    }
    if (!copy($rootFile, $backup)) {
        fail("Could not write backup: $backup");
    }
    if (file_put_contents($rootFile, encodeComposerJson($newRoot)) === false) {
        fail("Could not write: $rootFile");
    }

    info("\nDone. Backup written to " . basename($backup) . '.');
    info('Next step: run  composer update  to resolve the inlined component set.');
}

// Only run when executed directly (so the functions can be unit-tested via include).
if (isset($argv) && realpath($argv[0]) === realpath(__FILE__)) {
    main($argv);
}
