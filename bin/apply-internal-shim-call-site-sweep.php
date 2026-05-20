#!/usr/bin/env php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, version 3.
 *
 * Phase 2 rewriter for #107 internal call-site sweep.
 *
 * Reads the findings file produced by find-internal-shim-call-sites.php, then
 * for each entry rewrites `$this->method(` → `$this->_method(` at the exact
 * token location identified by the detector. Tokenizer-based so string and
 * comment occurrences of the same name are never touched.
 *
 * Optionally restrict to a directory prefix — `--prefix=source/Application/Controller/Admin/`
 * applies only to findings whose file starts with that prefix. Useful for
 * batched per-directory commits per the gate's batching decision.
 *
 * Usage:
 *   apply-internal-shim-call-site-sweep.php [--findings=<path>] [--prefix=<repo-relative-prefix>]
 *                                            [--dry-run] [--quiet]
 *   apply-internal-shim-call-site-sweep.php --help | -h
 *
 * Defaults (resolved against the repo root):
 *   --findings  openspec/changes/fix-internal-shim-call-sites/findings.json
 *   no --prefix means: apply to all findings
 *
 * Exit codes:
 *   0  rewrites applied (or dry-run completed)
 *   2  usage error
 *   3  findings file missing or malformed
 *   4  rewrite verification mismatch (a finding's expected token did not match the file)
 */

declare(strict_types=1);

// composer.json supports php ^7.4 || ^8.0; str_starts_with is PHP 8.0+.
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

exit(main($argv));

function main(array $argv): int
{
    $opts = parse_args($argv);
    if ($opts['help']) {
        echo usage_text();
        return 0;
    }

    $repoRoot = resolve_repo_root(__DIR__);
    $findingsPath = resolve_path($repoRoot, $opts['findings']);

    if (!is_file($findingsPath)) {
        fwrite(STDERR, "error: findings file not found: $findingsPath\n");
        return 3;
    }
    $findings = json_decode((string) file_get_contents($findingsPath), true);
    if (!is_array($findings)) {
        fwrite(STDERR, "error: findings file is not a JSON array: $findingsPath\n");
        return 3;
    }

    // Load exclusions and skip any finding that matches an exclusion entry
    // (the durable allow-list of intentional non-underscore call sites).
    $exclusionsPath = resolve_path($repoRoot, $opts['exclusions']);
    $excludedKeys = [];
    if (is_file($exclusionsPath)) {
        $exDecoded = json_decode((string) file_get_contents($exclusionsPath), true);
        if (is_array($exDecoded) && isset($exDecoded['exclusions']) && is_array($exDecoded['exclusions'])) {
            foreach ($exDecoded['exclusions'] as $e) {
                if (isset($e['file'], $e['line'], $e['method'])) {
                    $excludedKeys[$e['file'] . '|' . $e['line'] . '|' . $e['method']] = true;
                }
            }
        }
    }

    $prefix = $opts['prefix'];
    $filePattern = $opts['filePattern'];
    $byFile = [];
    foreach ($findings as $f) {
        if (!is_array($f) || !isset($f['file'], $f['line'], $f['method'])) {
            continue;
        }
        if ($prefix !== '' && !str_starts_with($f['file'], $prefix)) {
            continue;
        }
        if ($filePattern !== '' && !preg_match($filePattern, $f['file'])) {
            continue;
        }
        $key = $f['file'] . '|' . $f['line'] . '|' . $f['method'];
        if (isset($excludedKeys[$key])) {
            continue;
        }
        $byFile[$f['file']][] = ['line' => (int) $f['line'], 'method' => (string) $f['method']];
    }

    if ($byFile === []) {
        if (!$opts['quiet']) {
            $hint = $prefix === '' ? '' : " (with prefix \"$prefix\")";
            echo "No findings to apply$hint.\n";
        }
        return 0;
    }

    $totalRewritten = 0;
    $filesTouched = 0;
    foreach ($byFile as $relFile => $sites) {
        $absFile = resolve_path($repoRoot, $relFile);
        $rewrites = rewrite_file($absFile, $sites, $opts['dryRun']);
        if ($rewrites === null) {
            return 4;
        }
        if ($rewrites > 0) {
            $totalRewritten += $rewrites;
            $filesTouched++;
            if (!$opts['quiet']) {
                printf("  %s: %d rewrite%s\n", $relFile, $rewrites, $rewrites === 1 ? '' : 's');
            }
        }
    }

    if (!$opts['quiet']) {
        printf(
            "%s: %d rewrite%s across %d file%s.\n",
            $opts['dryRun'] ? 'Dry run' : 'Applied',
            $totalRewritten,
            $totalRewritten === 1 ? '' : 's',
            $filesTouched,
            $filesTouched === 1 ? '' : 's'
        );
    }

    return 0;
}

function parse_args(array $argv): array
{
    $opts = [
        'help' => false,
        'dryRun' => false,
        'quiet' => false,
        'findings' => 'openspec/changes/fix-internal-shim-call-sites/findings.json',
        'exclusions' => 'tests/Unit/BackwardsCompatibility/internal-shim-call-sites-exclusions.json',
        'prefix' => '',
        'filePattern' => '',
    ];
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $opts['help'] = true;
            continue;
        }
        if ($arg === '--dry-run') {
            $opts['dryRun'] = true;
            continue;
        }
        if ($arg === '--quiet') {
            $opts['quiet'] = true;
            continue;
        }
        if (str_starts_with($arg, '--findings=')) {
            $opts['findings'] = substr($arg, strlen('--findings='));
            continue;
        }
        if (str_starts_with($arg, '--prefix=')) {
            $opts['prefix'] = substr($arg, strlen('--prefix='));
            continue;
        }
        if (str_starts_with($arg, '--file-pattern=')) {
            $opts['filePattern'] = substr($arg, strlen('--file-pattern='));
            continue;
        }
        if (str_starts_with($arg, '--exclusions=')) {
            $opts['exclusions'] = substr($arg, strlen('--exclusions='));
            continue;
        }
        fwrite(STDERR, "error: unknown argument: $arg (try --help)\n");
        exit(2);
    }
    return $opts;
}

function usage_text(): string
{
    return <<<TXT
apply-internal-shim-call-site-sweep.php — Phase 2 rewriter for #107.

Reads findings.json and for each entry rewrites `\$this->method(` to
`\$this->_method(` at the exact token location. Tokenizer-based; never
touches string or comment occurrences.

Usage:
  bin/apply-internal-shim-call-site-sweep.php [--findings=<path>] [--prefix=<dir>]
                                              [--dry-run] [--quiet]
  bin/apply-internal-shim-call-site-sweep.php --help | -h

Options:
  --findings      Path to findings JSON
                  (default: openspec/changes/fix-internal-shim-call-sites/findings.json)
  --exclusions    Path to exclusions JSON (durable allow-list)
                  (default: tests/Unit/BackwardsCompatibility/internal-shim-call-sites-exclusions.json)
  --prefix        Limit rewrites to findings whose file starts with this prefix
                  (e.g. source/Application/Controller/Admin/). No default: applies to all.
  --file-pattern  Limit rewrites to findings whose file matches this regex
                  (e.g. '~Ajax\.php$~' for *Ajax.php files only). No default.
  --dry-run       Verify findings without writing files; print the would-be summary.
  --quiet         Suppress per-file progress output.

TXT;
}

function resolve_repo_root(string $startDir): string
{
    $cmd = sprintf('git -C %s rev-parse --show-toplevel 2>/dev/null', escapeshellarg($startDir));
    $root = trim((string) shell_exec($cmd));
    if ($root === '' || !is_dir($root)) {
        fwrite(STDERR, "error: cannot resolve repo root from $startDir\n");
        exit(3);
    }
    return $root;
}

function resolve_path(string $repoRoot, string $path): string
{
    if ($path !== '' && $path[0] === '/') {
        return $path;
    }
    return rtrim($repoRoot, '/') . '/' . ltrim($path, '/');
}

/**
 * Rewrite a single file. Returns the number of rewrites applied,
 * or null on a verification mismatch (means findings.json is stale
 * relative to the file).
 *
 * @param list<array{line:int,method:string}> $sites
 */
function rewrite_file(string $absPath, array $sites, bool $dryRun): ?int
{
    if (!is_file($absPath)) {
        fwrite(STDERR, "error: source file missing: $absPath\n");
        return null;
    }
    $code = file_get_contents($absPath);
    if ($code === false) {
        fwrite(STDERR, "error: cannot read $absPath\n");
        return null;
    }
    $tokens = token_get_all($code);
    $count = count($tokens);

    // Build (line => list of method names to rewrite on that line)
    $byLine = [];
    foreach ($sites as $s) {
        $byLine[$s['line']][] = $s['method'];
    }

    // Find each `$this->method(` token sequence; if its (line, method) is
    // in the requested set, mark that T_STRING for replacement.
    $replacements = []; // list of [token-index, original-name, new-name]
    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (!is_array($tok) || $tok[0] !== T_VARIABLE || $tok[1] !== '$this') {
            continue;
        }
        $j = next_significant($tokens, $i);
        if ($j === null || !is_array($tokens[$j]) || $tokens[$j][0] !== T_OBJECT_OPERATOR) {
            continue;
        }
        $k = next_significant($tokens, $j);
        if ($k === null || !is_array($tokens[$k]) || $tokens[$k][0] !== T_STRING) {
            continue;
        }
        $l = next_significant($tokens, $k);
        if ($l === null || $tokens[$l] !== '(') {
            continue;
        }
        $line = $tokens[$k][2];
        $name = $tokens[$k][1];
        if (!isset($byLine[$line])) {
            continue;
        }
        $needles = $byLine[$line];
        $idx = array_search($name, $needles, true);
        if ($idx === false) {
            continue;
        }
        // Consume one needle so a duplicate (same method on same line) does
        // not match twice for one finding.
        unset($byLine[$line][$idx]);
        $byLine[$line] = array_values($byLine[$line]);
        if ($byLine[$line] === []) {
            unset($byLine[$line]);
        }
        $replacements[] = [$k, $name, '_' . $name];
    }

    // Verify: every finding for this file should have matched a token.
    if ($byLine !== []) {
        $missed = [];
        foreach ($byLine as $line => $names) {
            foreach ($names as $n) {
                $missed[] = "{$line}:{$n}";
            }
        }
        fwrite(
            STDERR,
            sprintf(
                "error: verification mismatch in %s: %d finding%s did not match a token (stale findings?). Missing: %s\n",
                $absPath,
                count($missed),
                count($missed) === 1 ? '' : 's',
                implode(', ', $missed)
            )
        );
        return null;
    }

    if ($replacements === [] || $dryRun) {
        return count($replacements);
    }

    // Apply replacements by mutating the tokens array, then re-emit.
    foreach ($replacements as [$idx, , $newName]) {
        $tok = $tokens[$idx];
        // Token shape: [T_STRING, "name", line]
        $tokens[$idx] = [$tok[0], $newName, $tok[2]];
    }

    $rebuilt = '';
    foreach ($tokens as $tok) {
        $rebuilt .= is_array($tok) ? $tok[1] : $tok;
    }

    if (file_put_contents($absPath, $rebuilt) === false) {
        fwrite(STDERR, "error: failed to write $absPath\n");
        return null;
    }
    return count($replacements);
}

function next_significant(array $tokens, int $i): ?int
{
    $count = count($tokens);
    for ($j = $i + 1; $j < $count; $j++) {
        $tok = $tokens[$j];
        if (is_array($tok) && in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        return $j;
    }
    return null;
}
