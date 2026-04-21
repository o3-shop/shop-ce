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
 * One-off targeted transform for the "simple broken shim" case of the
 * underscore-method inheritance remediation (see
 * openspec/changes/fix-underscore-method-inheritance/design.md, D6).
 *
 * Given a list of <file>:<_method> pairs, for each pair this script:
 *   1. verifies the file contains the CLASSIC broken shim pattern:
 *      - `_method()` body is a single `return $this->method(...)` (or the
 *        void variant `$this->method(...);`), and
 *      - `method()` body is the real implementation, and
 *      - `method()` body does NOT call `parent::method(` (if it does, per-batch
 *        manual rewrite is required to avoid recursion — see memory note
 *        `feedback_subclass_parent_call_rewrite`).
 *   2. performs the inversion:
 *      - moves the real impl from `method()` into `_method()`,
 *      - replaces `method()` body with `return $this->_method($args);`
 *        (or `$this->_method($args);` for void),
 *      - rewrites `_method()`'s `@deprecated` line to the canonical form
 *        (retained-for-BC + new code MUST NOT call/override),
 *      - adds the canonical `@internal parent::method()` hint block to
 *        `method()`'s PHPDoc.
 *
 * Any pair whose pattern does not match is SKIPPED with a reason — those
 * cases require the full manual Edit flow.
 *
 * The four post-batch checks (inheritance-contract, equivalence,
 * per-file tests, full-suite security layer) still apply unchanged; this
 * script is verified extrinsically by those.
 *
 * Usage:
 *   bin/invert-underscore-shim.php <file>:<_method> [<file>:<_method>...]
 *
 * Options:
 *   --dry-run, -n   Print what would change; do not modify files.
 *   --help, -h      Print this help and exit.
 *
 * Exit codes:
 *   0  all pairs inverted cleanly
 *   1  one or more pairs skipped (manual handling needed)
 *   2  usage / extraction error
 */

declare(strict_types=1);

// Only run main when executed directly (so tests can include this file).
if (realpath($argv[0] ?? '') === __FILE__) {
    exit(main($argv));
}

function main(array $argv): int
{
    $pairs = [];
    $dry = false;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            echo usage();
            return 0;
        }
        if ($arg === '--dry-run' || $arg === '-n') {
            $dry = true;
            continue;
        }
        if (!str_contains($arg, ':')) {
            die_err("unknown arg: {$arg}", 2);
        }
        [$f, $m] = explode(':', $arg, 2);
        if (!str_starts_with($m, '_')) {
            die_err("method name must start with '_': {$arg}", 2);
        }
        $pairs[] = [$f, $m];
    }
    if ($pairs === []) {
        die_err('no pairs specified (try --help)', 2);
    }

    $totalOk = 0;
    $totalSkip = 0;
    foreach ($pairs as [$file, $um]) {
        $result = invertPair($file, $um);
        if ($result['ok']) {
            if (!$dry) {
                if (file_put_contents($file, $result['content']) === false) {
                    die_err("failed to write {$file}", 2);
                }
            }
            printf("INVERTED  %s :: %s%s\n", $file, $um, $dry ? ' (dry-run)' : '');
            $totalOk++;
        } else {
            printf("SKIP      %s :: %s  — %s\n", $file, $um, $result['reason']);
            $totalSkip++;
        }
    }
    fwrite(STDERR, sprintf("\n%d inverted, %d skipped\n", $totalOk, $totalSkip));
    return $totalSkip > 0 ? 1 : 0;
}

function usage(): string
{
    return <<<USAGE
Usage: invert-underscore-shim.php [OPTIONS] <file>:<_method> [<file>:<_method>...]

Inverts simple broken-shim pairs in-place. Skips any pair that doesn't match
the classic pattern (manual handling needed for those).

Arguments:
  <file>:<_method>  repo-relative path plus underscore method name
                    e.g. source/Application/Controller/Admin/FooAjax.php:_getQuery

Options:
  --dry-run, -n     do not modify files; just report INVERTED/SKIP
  --help, -h        print this help and exit

Exit codes:
  0  all pairs inverted
  1  one or more pairs skipped
  2  usage/extraction error

Design: openspec/changes/fix-underscore-method-inheritance/design.md (D6)

USAGE;
}

function invertPair(string $path, string $um): array
{
    $m = substr($um, 1);
    $src = @file_get_contents($path);
    if ($src === false) {
        return ['ok' => false, 'reason' => 'file not readable'];
    }

    $umInfo = locateMethod($src, $um);
    if ($umInfo === null) {
        return ['ok' => false, 'reason' => "{$um}() not found"];
    }
    $mInfo = locateMethod($src, $m);
    if ($mInfo === null) {
        return ['ok' => false, 'reason' => "{$m}() not found"];
    }

    // --- Verify the classic broken-shim pattern -----------------------------

    // 1. _method body must be a single `return $this->method($args)` or void-variant.
    $umBody = normaliseWs($umInfo['body']);
    $siblingCallPattern = '/^\s*(return\s+)?\$this->' . preg_quote($m, '/') . '\s*\([^;]*\)\s*;\s*$/s';
    if (!preg_match($siblingCallPattern, $umBody)) {
        return ['ok' => false, 'reason' => "_{$m}() body is not a simple shim"];
    }

    // 2. method body must not call parent::method(
    if (preg_match('/parent::' . preg_quote($m, '/') . '\s*\(/', $mInfo['body'])) {
        return ['ok' => false, 'reason' => "{$m}() body calls parent::{$m}() — requires manual parent:: rewrite"];
    }

    // 3. params on _method and method must match textually (we preserve both).
    $umParams = trim($umInfo['paramsText']);
    $mParams = trim($mInfo['paramsText']);
    if (normaliseWs($umParams) !== normaliseWs($mParams)) {
        return ['ok' => false, 'reason' => "_{$m}() and {$m}() signatures differ"];
    }

    // 4. Determine void vs return by inspecting the shim body.
    $isVoid = !preg_match('/^\s*return\s/', $umBody);

    // 5. Build the args list for the delegate call.
    $argsList = paramsToArgs($umInfo['paramsText']);

    // --- Rewrite the PHPDoc blocks ------------------------------------------

    $newUmDoc = rewriteDeprecatedDoc($umInfo['doc'], $m);
    $newMDoc = appendInternalHint($mInfo['doc'], $m);

    // --- Build new bodies ---------------------------------------------------

    $indent = '    ';
    $delegateBody = "{\n{$indent}{$indent}" .
        ($isVoid ? "\$this->_{$m}({$argsList});" : "return \$this->_{$m}({$argsList});") .
        "\n{$indent}}";

    // --- Assemble the new file ----------------------------------------------
    // We replace the larger contiguous region that spans from the earliest
    // block (doc+signature+body) to the latest one, in whichever order they
    // appear in the source.

    // Order by docStart offset
    if ($umInfo['docStart'] <= $mInfo['docStart']) {
        $first = $umInfo;
        $firstRole = 'underscore';
        $second = $mInfo;
        $secondRole = 'plain';
    } else {
        $first = $mInfo;
        $firstRole = 'plain';
        $second = $umInfo;
        $secondRole = 'underscore';
    }

    // Sanity: the two must not overlap.
    if ($first['bodyClose'] >= $second['docStart']) {
        return ['ok' => false, 'reason' => 'the _method and method regions overlap'];
    }

    $before = substr($src, 0, $first['docStart']);
    $between = substr($src, $first['bodyClose'] + 1, $second['docStart'] - $first['bodyClose'] - 1);
    $after = substr($src, $second['bodyClose'] + 1);

    // Rebuilt blocks.
    $umSig = substr($src, $umInfo['sigStart'], $umInfo['bodyOpen'] - $umInfo['sigStart']);
    $mSig = substr($src, $mInfo['sigStart'], $mInfo['bodyOpen'] - $mInfo['sigStart']);

    // The migrated body for _method is the OLD method body (with its surrounding braces).
    $migratedBody = substr($src, $mInfo['bodyOpen'], $mInfo['bodyClose'] - $mInfo['bodyOpen'] + 1);

    $newUmBlock = $newUmDoc . $umSig . $migratedBody;
    $newMBlock = $newMDoc . $mSig . $delegateBody;

    if ($firstRole === 'underscore') {
        $newFileContent = $before . $newUmBlock . $between . $newMBlock . $after;
    } else {
        $newFileContent = $before . $newMBlock . $between . $newUmBlock . $after;
    }

    return ['ok' => true, 'content' => $newFileContent];
}

/**
 * Locate a method declaration in PHP source by name.
 * Returns an associative array of offsets, or null if not found.
 *
 * - docStart / docEnd: byte offsets of the preceding docblock (if any; else
 *   equal to sigStart so that docStart..sigStart-1 is empty).
 * - sigStart: byte offset of the signature's first token (visibility or
 *   `static` etc — begins the "protected function ..." line including any
 *   leading whitespace).
 * - bodyOpen / bodyClose: offsets of the opening and closing curly braces.
 * - body: content between the braces (exclusive).
 * - paramsText: content of the param list (exclusive of parens).
 * - doc: content of the docblock (inclusive; empty string if absent).
 */
function locateMethod(string $src, string $name): ?array
{
    $tokens = @token_get_all($src);
    if (!is_array($tokens)) {
        return null;
    }

    // Build offsets: each token's start byte offset.
    $offsets = tokenOffsets($src, $tokens);

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $t = $tokens[$i];
        if (!is_array($t) || $t[0] !== T_FUNCTION) {
            continue;
        }
        // Find the method name token.
        $nameIdx = null;
        for ($j = $i + 1; $j < $count; $j++) {
            $tj = $tokens[$j];
            if (is_array($tj) && in_array($tj[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if (is_array($tj) && $tj[0] === T_STRING) {
                $nameIdx = $j;
                break;
            }
            if (is_string($tj) && $tj === '&') {
                continue;
            }
            break;
        }
        if ($nameIdx === null || $tokens[$nameIdx][1] !== $name) {
            continue;
        }

        // Walk back from T_FUNCTION to find signature-start (outermost modifier).
        $sigStartIdx = $i;
        for ($k = $i - 1; $k >= 0; $k--) {
            $tk = $tokens[$k];
            // skip whitespace/comments between modifiers and function keyword
            if (is_array($tk) && in_array($tk[0], [T_WHITESPACE, T_COMMENT], true)) {
                continue;
            }
            if (is_array($tk) && in_array($tk[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL], true)) {
                $sigStartIdx = $k;
                continue;
            }
            break;
        }
        $sigStart = $offsets[$sigStartIdx];

        // Include leading whitespace on the line up to a preceding newline.
        while ($sigStart > 0 && $src[$sigStart - 1] !== "\n") {
            $sigStart--;
        }

        // Look for a docblock IMMEDIATELY preceding (allowing whitespace).
        $docStart = $sigStart;
        $docEnd = $sigStart;
        $doc = '';
        for ($k = $sigStartIdx - 1; $k >= 0; $k--) {
            $tk = $tokens[$k];
            if (!is_array($tk)) {
                break;
            }
            if ($tk[0] === T_WHITESPACE) {
                continue;
            }
            if ($tk[0] === T_DOC_COMMENT) {
                $docStart = $offsets[$k];
                $docEnd = $docStart + strlen($tk[1]);
                $doc = $tk[1];
                // Also include leading whitespace on the docblock's line.
                while ($docStart > 0 && $src[$docStart - 1] !== "\n") {
                    $docStart--;
                }
                // The "region" includes any whitespace between doc and signature.
                $doc = substr($src, $docStart, $docEnd - $docStart)
                    . substr($src, $docEnd, $sigStart - $docEnd);
            }
            break;
        }

        // Find `(` after nameIdx and match to `)`.
        $parenOpen = null;
        for ($k = $nameIdx + 1; $k < $count; $k++) {
            $tk = $tokens[$k];
            if (is_string($tk) && $tk === '(') {
                $parenOpen = $k;
                break;
            }
        }
        if ($parenOpen === null) {
            return null;
        }
        $depth = 1;
        $parenClose = null;
        for ($k = $parenOpen + 1; $k < $count; $k++) {
            $tk = $tokens[$k];
            if (is_string($tk)) {
                if ($tk === '(') {
                    $depth++;
                } elseif ($tk === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $parenClose = $k;
                        break;
                    }
                }
            }
        }
        if ($parenClose === null) {
            return null;
        }

        $paramsText = substr($src, $offsets[$parenOpen] + 1, $offsets[$parenClose] - $offsets[$parenOpen] - 1);

        // Find the `{` that opens the body.
        $bodyOpenIdx = null;
        for ($k = $parenClose + 1; $k < $count; $k++) {
            $tk = $tokens[$k];
            if (is_string($tk) && $tk === '{') {
                $bodyOpenIdx = $k;
                break;
            }
        }
        if ($bodyOpenIdx === null) {
            return null;
        }
        $bodyOpen = $offsets[$bodyOpenIdx];

        // Walk to matching close brace.
        $depth = 1;
        $bodyCloseIdx = null;
        for ($k = $bodyOpenIdx + 1; $k < $count; $k++) {
            $tk = $tokens[$k];
            if (is_string($tk)) {
                if ($tk === '{') {
                    $depth++;
                } elseif ($tk === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $bodyCloseIdx = $k;
                        break;
                    }
                }
                continue;
            }
            if ($tk[0] === T_CURLY_OPEN || $tk[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
            }
        }
        if ($bodyCloseIdx === null) {
            return null;
        }
        $bodyClose = $offsets[$bodyCloseIdx];

        $body = substr($src, $bodyOpen + 1, $bodyClose - $bodyOpen - 1);

        return [
            'docStart' => $docStart,
            'docEnd' => $docEnd,
            'doc' => $doc,
            'sigStart' => $sigStart,
            'bodyOpen' => $bodyOpen,
            'bodyClose' => $bodyClose,
            'body' => $body,
            'paramsText' => $paramsText,
        ];
    }
    return null;
}

/**
 * @param array<int, string|array{0:int,1:string,2:int}> $tokens
 * @return array<int, int> parallel array: token index → byte offset
 */
function tokenOffsets(string $src, array $tokens): array
{
    $offsets = [];
    $pos = 0;
    foreach ($tokens as $i => $t) {
        $offsets[$i] = $pos;
        $pos += strlen(is_string($t) ? $t : $t[1]);
    }
    return $offsets;
}

function normaliseWs(string $s): string
{
    return preg_replace('/\s+/', ' ', trim($s));
}

function paramsToArgs(string $paramsText): string
{
    // Extract the $varname tokens from the param list.
    // Cheap but effective: split on commas, strip defaults and type hints, keep just $name.
    $paramsText = trim($paramsText);
    if ($paramsText === '') {
        return '';
    }
    // Naive split that handles nested parens in default values
    $parts = [];
    $depth = 0;
    $cur = '';
    $len = strlen($paramsText);
    for ($i = 0; $i < $len; $i++) {
        $ch = $paramsText[$i];
        if ($ch === '(' || $ch === '[' || $ch === '{') {
            $depth++;
        }
        if ($ch === ')' || $ch === ']' || $ch === '}') {
            $depth--;
        }
        if ($ch === ',' && $depth === 0) {
            $parts[] = $cur;
            $cur = '';
            continue;
        }
        $cur .= $ch;
    }
    if ($cur !== '') {
        $parts[] = $cur;
    }
    $args = [];
    foreach ($parts as $p) {
        if (preg_match('/&?\.{0,3}(\$\w+)/', $p, $m)) {
            // Respect variadic.
            if (preg_match('/\.\.\.\s*' . preg_quote($m[1], '/') . '/', $p)) {
                $args[] = '...' . $m[1];
            } else {
                $args[] = $m[1];
            }
        }
    }
    return implode(', ', $args);
}

function rewriteDeprecatedDoc(string $doc, string $m): string
{
    $canonical = '@deprecated Use ' . $m . '() instead. This underscore-prefixed name is retained only'
        . "\n     *             for backward compatibility with module subclasses that already override"
        . "\n     *             it; new code, including new modules, MUST NOT call or override _" . $m . '().';

    // Locate existing @deprecated line (and continuation lines).
    if (preg_match('/(^|\n)(\s*\*\s*)@deprecated[^\n]*(?:\n\s*\*\s{2,}[^\n]*)*/', $doc, $matches, PREG_OFFSET_CAPTURE)) {
        $matchOffset = $matches[0][1];
        $matchText = $matches[0][0];
        $leadingNewline = $matches[1][0];
        $prefix = $matches[2][0];

        $replacement = $leadingNewline . $prefix . $canonical;
        $doc = substr($doc, 0, $matchOffset) . $replacement . substr($doc, $matchOffset + strlen($matchText));
        return $doc;
    }

    // No existing @deprecated — insert before the closing */
    if (preg_match('#(\n\s*)\*/#', $doc, $m2, PREG_OFFSET_CAPTURE)) {
        $closeOffset = $m2[0][1];
        $indent = $m2[1][0];
        $insertion = $indent . '* ' . $canonical;
        $doc = substr($doc, 0, $closeOffset) . $insertion . substr($doc, $closeOffset);
    }
    return $doc;
}

function appendInternalHint(string $doc, string $m): string
{
    $hintBody = '@internal If your override does not fully replace the behavior, call parent::' . $m . '()'
        . "\n     *           (not the deprecated _" . $m . '()) so downstream overrides in the class chain'
        . "\n     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.";

    if (preg_match('#(\n\s*)\*/#', $doc, $m2, PREG_OFFSET_CAPTURE)) {
        $closeOffset = $m2[0][1];
        $indent = $m2[1][0];
        // Precede with a blank `*` line if not already there.
        $insertion = $indent . '*'
            . $indent . '* ' . $hintBody;
        $doc = substr($doc, 0, $closeOffset) . $insertion . substr($doc, $closeOffset);
    }
    return $doc;
}

function die_err(string $msg, int $code = 1): never
{
    fwrite(STDERR, 'error: ' . $msg . "\n");
    exit($code);
}
