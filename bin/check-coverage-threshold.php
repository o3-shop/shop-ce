#!/usr/bin/env php
<?php

/**
 * Quality-gate: fail when statement coverage drops below a configured
 * threshold. Reads a PHPUnit clover-XML coverage report, computes
 * `coveredstatements / statements`, and exits non-zero with a clear
 * diagnostic when below the threshold. Wired into both the local
 * `./docker.sh test-all-coverage` flow and the matrix `test` job in
 * .github/workflows/tests.yml so the local and CI gates stay in sync.
 *
 * Usage:
 *   bin/check-coverage-threshold.php --clover <path> [--threshold <percent>]
 *
 * Defaults:
 *   --threshold 90
 *
 * Exit codes:
 *   0  coverage at-or-above threshold (or empty project: 0/0 statements)
 *   1  coverage below threshold
 *   2  clover file missing, unreadable, or malformed
 *   3  CLI arguments invalid
 *
 * Refs: o3-shop/o3-shop#137
 */

declare(strict_types=1);

[$cloverPath, $threshold] = parseArgs($argv);

if (!is_file($cloverPath) || !is_readable($cloverPath)) {
    fwrite(STDERR, sprintf("[ERROR] clover report not readable: %s\n", $cloverPath));
    exit(2);
}

$xml = @simplexml_load_file($cloverPath);
if ($xml === false) {
    fwrite(STDERR, sprintf("[ERROR] could not parse clover XML: %s\n", $cloverPath));
    exit(2);
}

$metrics = $xml->project->metrics ?? null;
if ($metrics === null) {
    fwrite(STDERR, sprintf("[ERROR] clover XML has no <project><metrics>: %s\n", $cloverPath));
    exit(2);
}

$statements = (int) ($metrics['statements'] ?? 0);
$covered = (int) ($metrics['coveredstatements'] ?? 0);

if ($statements === 0) {
    fwrite(STDOUT, "[OK] coverage: no statements in report; threshold check skipped.\n");
    exit(0);
}

$percentage = round(($covered / $statements) * 100, 2);

if ($percentage + 1e-9 < $threshold) {
    fwrite(STDERR, sprintf(
        "[FAIL] coverage %s%% is below threshold %s%% (covered %d / %d statements).\n",
        formatPct($percentage),
        formatPct($threshold),
        $covered,
        $statements
    ));
    exit(1);
}

fwrite(STDOUT, sprintf(
    "[OK] coverage %s%% meets threshold %s%% (covered %d / %d statements).\n",
    formatPct($percentage),
    formatPct($threshold),
    $covered,
    $statements
));
exit(0);

/**
 * @param array<int,string> $argv
 * @return array{0: string, 1: float}
 */
function parseArgs(array $argv): array
{
    $cloverPath = null;
    $threshold = 90.0;
    $i = 1;
    while ($i < count($argv)) {
        $arg = $argv[$i];
        if ($arg === '--clover') {
            $cloverPath = $argv[$i + 1] ?? null;
            $i += 2;
            continue;
        }
        if (strpos($arg, '--clover=') === 0) {
            $cloverPath = substr($arg, strlen('--clover='));
            $i++;
            continue;
        }
        if ($arg === '--threshold') {
            $threshold = (float) ($argv[$i + 1] ?? '');
            $i += 2;
            continue;
        }
        if (strpos($arg, '--threshold=') === 0) {
            $threshold = (float) substr($arg, strlen('--threshold='));
            $i++;
            continue;
        }
        fwrite(STDERR, sprintf("[ERROR] unknown argument: %s\n", $arg));
        usage();
        exit(3);
    }
    if ($cloverPath === null || $cloverPath === '') {
        fwrite(STDERR, "[ERROR] --clover <path> is required\n");
        usage();
        exit(3);
    }
    if ($threshold < 0.0 || $threshold > 100.0) {
        fwrite(STDERR, sprintf("[ERROR] --threshold must be in [0, 100]; got %s\n", (string) $threshold));
        exit(3);
    }
    return [$cloverPath, $threshold];
}

function usage(): void
{
    fwrite(STDERR, "Usage: bin/check-coverage-threshold.php --clover <path> [--threshold <percent>]\n");
}

function formatPct(float $value): string
{
    return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.');
}
