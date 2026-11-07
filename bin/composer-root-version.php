#!/usr/bin/env php
<?php

/**
 * Prints the COMPOSER_ROOT_VERSION for this checkout: the `branch-alias` key
 * in composer.json whose alias has the highest version (e.g. `dev-b-1.7`).
 *
 * From b-1.5 on, each release line adds its own alias, so the newest one is
 * the line the checkout belongs to: on the release branch and on every feature
 * branch cut from it. Composer cannot work that out for a feature branch
 * itself (it guesses `dev-main`, which no dependency accepts), and a hardcoded
 * value goes stale with every new release line.
 *
 * This only holds while every line adds its alias when it is cut: a new
 * b-1.8 without `"dev-b-1.8": "1.8-dev"` would be stamped as b-1.7. Older
 * branches (b-1.4 and before carry OXID's `dev-b-7.0.x` aliases) don't
 * follow the rule.
 *
 * Used by docker/entrypoint.sh and the E2E job in
 * .github/workflows/code-quality.yml.
 *
 * Usage:
 *   bin/composer-root-version.php [<path to composer.json>]
 *
 * Exit codes:
 *   0  printed the version
 *   1  composer.json has no extra.branch-alias
 *   2  composer.json missing, unreadable, or malformed
 *
 * Refs: o3-shop/o3-shop#282
 */

declare(strict_types=1);

$path = $argv[1] ?? dirname(__DIR__) . '/composer.json';

$contents = is_file($path) ? file_get_contents($path) : false;
if ($contents === false) {
    fwrite(STDERR, "[ERROR] Cannot read '{$path}'.\n");
    exit(2);
}

$composer = json_decode($contents, true);
if (!is_array($composer)) {
    fwrite(STDERR, "[ERROR] '{$path}' is not valid JSON: " . json_last_error_msg() . "\n");
    exit(2);
}

$aliases = $composer['extra']['branch-alias'] ?? null;
$aliases = is_array($aliases) ? array_filter($aliases, 'is_string') : [];
if ($aliases === []) {
    fwrite(STDERR, "[ERROR] '{$path}' has no extra.branch-alias to derive the root version from.\n");
    exit(1);
}

uasort($aliases, 'version_compare');
fwrite(STDOUT, array_key_last($aliases) . "\n");
exit(0);
