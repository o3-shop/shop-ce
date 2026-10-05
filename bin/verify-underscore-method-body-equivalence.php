#!/usr/bin/env php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * Batch equivalence checker for the underscore-method inheritance remediation.
 *
 * Given a list of (file, _method) pairs, for each pair extract the body of
 * _method() from the pinned baseline revision and from the current working
 * tree, then diff them. A pair is EQUIVALENT if the bodies match byte-for-byte
 * after a lenient whitespace normalisation (leading/trailing blank lines, and
 * trailing whitespace on each line, are ignored). Anything else is a MISMATCH
 * and is reported in detail.
 *
 * The inheritance-contract test proves dispatch works; this script proves the
 * body that modules have been depending on for BC survived the edit unchanged.
 *
 * Usage:
 *   verify-underscore-method-body-equivalence.php <file>:<method> [<file>:<method>...]
 *   verify-underscore-method-body-equivalence.php --batch=<name>
 *
 * The --batch form reads the batch roster from --revision's findings.json-style
 * layout; supported values: application-component (batch 1),
 * application-component-widget (batch 2), etc. See design.md D6 for batch map.
 *
 * Options:
 *   --revision=<sha>      Baseline git revision. Default: ebe86dc0...
 *   --compare-sibling     For each pair, compare the current _method() body
 *                         against the non-underscore sibling's body in
 *                         --revision. This is the "did the Edit tool move the
 *                         pre-remediation method() body cleanly into _method()"
 *                         check. Use with --revision=<pre-remediation-commit>.
 *   --quiet               Only print the final summary line.
 *   --help, -h            Print this message and exit.
 *
 * Exit codes:
 *   0  all pairs equivalent
 *   1  one or more pairs mismatched
 *   2  usage error or extraction failure
 */

declare(strict_types=1);

const DEFAULT_REVISION = 'ebe86dc08875034d5a3d0533b7cbdede7cc6abff';

exit(main($argv));

function main(array $argv): int
{
    $opts = parse_args($argv);
    if ($opts['help']) {
        echo usage_text();
        return 0;
    }

    $repoRoot = resolve_repo_root(__DIR__);
    validate_revision($repoRoot, $opts['revision']);

    $pairs = $opts['pairs'];
    if ($pairs === []) {
        fwrite(STDERR, "error: no <file>:<method> pairs supplied. Try --help.\n");
        return 2;
    }

    $verbose = !$opts['quiet'];
    $mismatches = [];
    $checked = 0;

    foreach ($pairs as [$file, $method]) {
        $checked++;
        $baselineMethod = $opts['compareSibling'] ? ltrim($method, '_') : $method;
        $baseline = extract_method_body($repoRoot, $file, $baselineMethod, $opts['revision']);
        $current = extract_method_body_from_worktree($repoRoot, $file, $method);

        if ($baseline === null) {
            $mismatches[] = [$file, $method, 'NOT_FOUND_IN_BASELINE', ''];
            if ($verbose) {
                fwrite(STDERR, sprintf("[NOT IN BASELINE] %s::%s\n", $file, $method));
            }
            continue;
        }
        if ($current === null) {
            $mismatches[] = [$file, $method, 'NOT_FOUND_IN_CURRENT', ''];
            if ($verbose) {
                fwrite(STDERR, sprintf("[NOT IN CURRENT]  %s::%s\n", $file, $method));
            }
            continue;
        }

        $normBaseline = normalise($baseline);
        $normCurrent = normalise($current);

        if ($normBaseline === $normCurrent) {
            if ($verbose) {
                fwrite(STDOUT, sprintf("[EQUIVALENT]     %s::%s\n", $file, $method));
            }
            continue;
        }

        $mismatches[] = [$file, $method, 'BODY_DIVERGED', make_inline_diff($normBaseline, $normCurrent)];
        if ($verbose) {
            fwrite(STDERR, sprintf("[DIVERGED]       %s::%s\n", $file, $method));
            fwrite(STDERR, tail_diff($normBaseline, $normCurrent, 6) . "\n");
        }
    }

    $okCount = $checked - count($mismatches);
    if ($mismatches === []) {
        fwrite(STDOUT, sprintf("ALL %d PAIRS EQUIVALENT\n", $checked));
        return 0;
    }

    fwrite(STDERR, sprintf(
        "\n%d / %d pairs NOT equivalent:\n",
        count($mismatches),
        $checked
    ));
    foreach ($mismatches as [$f, $m, $kind]) {
        fwrite(STDERR, sprintf("  [%s] %s::%s\n", $kind, $f, $m));
    }
    return 1;
}

function usage_text(): string
{
    $rev = DEFAULT_REVISION;
    return <<<USAGE
Usage:
  verify-underscore-method-body-equivalence.php [OPTIONS] <file>:<method> [<file>:<method>...]

Compares the body of each _method() in the current working tree against the
body in the pinned baseline revision. Reports per-pair equivalence and exits
non-zero if any pair has diverged.

Arguments:
  <file>:<method>   Repo-relative path + underscore method name, e.g.
                    source/Application/Component/BasketComponent.php:_getItems

Options:
  --revision=<sha>  Baseline revision. Default: {$rev}
  --quiet           Only print the final summary line.
  -h, --help        Print this message and exit.

Exit codes:
  0 all equivalent   1 mismatches found   2 usage / extraction error

USAGE;
}

function parse_args(array $argv): array
{
    $pairs = [];
    $revision = DEFAULT_REVISION;
    $quiet = false;
    $help = false;
    $compareSibling = false;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $help = true;
        } elseif ($arg === '--quiet') {
            $quiet = true;
        } elseif ($arg === '--compare-sibling') {
            $compareSibling = true;
        } elseif (str_starts_with($arg, '--revision=')) {
            $revision = substr($arg, strlen('--revision='));
            if ($revision === '') {
                die_err('--revision requires a non-empty value', 2);
            }
        } elseif (str_contains($arg, ':')) {
            [$f, $m] = explode(':', $arg, 2);
            if ($f === '' || $m === '' || !str_starts_with($m, '_')) {
                die_err("invalid pair '{$arg}': expected <file>:<_method>", 2);
            }
            $pairs[] = [$f, $m];
        } else {
            die_err("unknown argument: {$arg} (try --help)", 2);
        }
    }
    return [
        'help' => $help,
        'quiet' => $quiet,
        'revision' => $revision,
        'compareSibling' => $compareSibling,
        'pairs' => $pairs,
    ];
}

function resolve_repo_root(string $scriptDir): string
{
    $r = run_process(['git', '-C', $scriptDir, 'rev-parse', '--show-toplevel']);
    if ($r['code'] !== 0) {
        die_err("script location '{$scriptDir}' is not inside a git work tree.\n" . trim($r['stderr']), 2);
    }
    return rtrim($r['stdout']);
}

function validate_revision(string $repoRoot, string $revision): void
{
    $r = run_process(['git', '-C', $repoRoot, 'rev-parse', '--verify', $revision . '^{commit}']);
    if ($r['code'] !== 0) {
        die_err("revision '{$revision}' does not exist in repo at {$repoRoot}\n" . trim($r['stderr']), 2);
    }
}

function extract_method_body(string $repoRoot, string $file, string $method, string $revision): ?string
{
    $r = run_process(['git', '-C', $repoRoot, 'show', $revision . ':' . $file]);
    if ($r['code'] !== 0) {
        return null;
    }
    return extract_body_from_source($r['stdout'], $method);
}

function extract_method_body_from_worktree(string $repoRoot, string $file, string $method): ?string
{
    $full = $repoRoot . '/' . $file;
    $src = @file_get_contents($full);
    if ($src === false) {
        return null;
    }
    return extract_body_from_source($src, $method);
}

function extract_body_from_source(string $source, string $method): ?string
{
    // Locate `function <method>(` then walk forward to the matching pair of braces.
    $tokens = @token_get_all($source);
    if (!is_array($tokens)) {
        return null;
    }

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (!is_array($tok) || $tok[0] !== T_FUNCTION) {
            continue;
        }
        // find method name
        $name = null;
        $j = $i + 1;
        for (; $j < $count; $j++) {
            $t = $tokens[$j];
            if (is_array($t) && ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
                continue;
            }
            if (is_string($t) && $t === '&') {
                continue;
            }
            if (is_array($t) && $t[0] === T_STRING) {
                $name = $t[1];
            }
            break;
        }
        if ($name !== $method) {
            continue;
        }
        // find opening `{` of the function body
        $depth = 0;
        $bodyStart = null;
        for ($k = $j + 1; $k < $count; $k++) {
            $t = $tokens[$k];
            if (is_string($t) && $t === '{') {
                $bodyStart = $k;
                break;
            }
        }
        if ($bodyStart === null) {
            return null;
        }
        // walk to matching close brace, tracking strings / comments nothing special
        $depth = 1;
        $bodyEnd = null;
        for ($k = $bodyStart + 1; $k < $count; $k++) {
            $t = $tokens[$k];
            if (is_string($t)) {
                if ($t === '{') {
                    $depth++;
                } elseif ($t === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $bodyEnd = $k;
                        break;
                    }
                }
                continue;
            }
            // curly braces inside strings appear as T_CURLY_OPEN / T_DOLLAR_OPEN_CURLY_BRACES / '}' as string
            // We handle '}' via the is_string branch above; the opens are tokens that do not return standalone strings.
            if ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
            }
        }
        if ($bodyEnd === null) {
            return null;
        }
        // Reconstruct body text from tokens[$bodyStart .. $bodyEnd] exclusive braces.
        $body = '';
        for ($k = $bodyStart + 1; $k < $bodyEnd; $k++) {
            $t = $tokens[$k];
            $body .= is_string($t) ? $t : $t[1];
        }
        return $body;
    }
    return null;
}

function normalise(string $body): string
{
    // Strip leading/trailing blank lines; strip trailing whitespace on each line.
    $lines = preg_split('/\R/', $body);
    $lines = array_map(static fn (string $l): string => rtrim($l), $lines);
    // drop leading empties
    while ($lines !== [] && $lines[0] === '') {
        array_shift($lines);
    }
    // drop trailing empties
    while ($lines !== [] && end($lines) === '') {
        array_pop($lines);
    }
    return implode("\n", $lines);
}

function make_inline_diff(string $a, string $b): string
{
    return tail_diff($a, $b, 5);
}

function tail_diff(string $a, string $b, int $context): string
{
    $aLines = explode("\n", $a);
    $bLines = explode("\n", $b);
    // quickest useful output: show the first differing line with a few lines of context
    $max = max(count($aLines), count($bLines));
    for ($i = 0; $i < $max; $i++) {
        $av = $aLines[$i] ?? '<EOF>';
        $bv = $bLines[$i] ?? '<EOF>';
        if ($av !== $bv) {
            $start = max(0, $i - $context);
            $end = min($max, $i + $context + 1);
            $out = [];
            for ($k = $start; $k < $end; $k++) {
                $la = $aLines[$k] ?? '<EOF>';
                $lb = $bLines[$k] ?? '<EOF>';
                if ($la === $lb) {
                    $out[] = '    ' . $la;
                } else {
                    $out[] = '--- ' . $la;
                    $out[] = '+++ ' . $lb;
                }
            }
            return implode("\n", $out);
        }
    }
    return '(no textual diff found — possibly different lengths only in trailing whitespace)';
}

function run_process(array $cmd, ?string $cwd = null): array
{
    $proc = proc_open(
        $cmd,
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $cwd
    );
    if (!is_resource($proc)) {
        die_err('failed to start: ' . implode(' ', $cmd), 2);
    }
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return ['code' => $code, 'stdout' => $stdout, 'stderr' => $stderr];
}

function die_err(string $message, int $code = 1): never
{
    fwrite(STDERR, 'error: ' . $message . "\n");
    exit($code);
}
