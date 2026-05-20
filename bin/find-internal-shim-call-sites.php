#!/usr/bin/env php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, version 3.
 *
 * Internal call-site detector for BC-shim methods.
 *
 * For every (Class, _method) entry in the pinned inventory at
 * tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json, find every
 * `$this->method(` call site in source/ where the declaring class also has both
 * `_method()` and `method()` declared (the BC-shim pair is in effect locally).
 * Emits deterministic JSON `{file, line, class, method}` per match, sorted by
 * (file, line), to the findings path.
 *
 * Companion to InheritanceContractTest: that test enforces structural BC
 * (overrides of `_method()` are dispatched through the public surface). This
 * script enforces the call-site convention (internal code uses
 * `$this->_method()` not `$this->method()`). See o3-shop/o3-shop#107.
 *
 * Usage:
 *   find-internal-shim-call-sites.php [--inventory=<path>] [--source-root=<path>]
 *                                     [--output=<path>] [--quiet]
 *   find-internal-shim-call-sites.php --help | -h
 *
 * Defaults (resolved against the repo root, not the caller's cwd):
 *   --inventory   tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json
 *   --source-root source/
 *   --output      openspec/changes/fix-internal-shim-call-sites/findings.json
 *
 * Exit codes:
 *   0  detector ran cleanly (regardless of whether findings is empty)
 *   2  usage error
 *   3  inventory or source-root not found
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

    // Resolve the repo root for both path resolution and producing
    // repo-relative file paths in the findings output. Order of precedence:
    //   1. Explicit --repo-root (the test harness passes this so the
    //      detector works inside the Docker container without needing git).
    //   2. `git rev-parse --show-toplevel` from this script's directory
    //      (the normal CLI case).
    $repoRoot = $opts['repoRoot'] !== ''
        ? rtrim($opts['repoRoot'], '/')
        : resolve_repo_root(__DIR__);
    $inventoryPath = resolve_path($repoRoot, $opts['inventory']);
    $sourceRoot = resolve_path($repoRoot, $opts['sourceRoot']);
    $outputPath = resolve_path($repoRoot, $opts['output']);

    if (!is_dir($sourceRoot)) {
        fwrite(STDERR, "error: source root not found: $sourceRoot\n");
        return 3;
    }

    [$classMethods, $callSites, $classExtends] = scan_source_tree($sourceRoot, $repoRoot);

    // Inventory file is parsed but only used as informational; the detector
    // relies on current-source shim pairs as the operational definition.
    // Reason: the official baseline generator under-counts (missed
    // `_getAll`/`_addFilter` on ListComponentAjax, etc.). For a call-site
    // convention sweep, "this class currently has both names defined" is the
    // right oracle.
    if ($inventoryPath !== '' && !is_file($inventoryPath)) {
        fwrite(STDERR, "error: inventory not found: $inventoryPath\n");
        return 3;
    }

    $findings = filter_findings($callSites, $classMethods, $classExtends);

    write_findings($outputPath, $findings);

    if (!$opts['quiet']) {
        printf(
            "Scanned %d classes, %d call sites; %d findings written to %s\n",
            count($classMethods),
            count($callSites),
            count($findings),
            $outputPath
        );
    }

    return 0;
}

function parse_args(array $argv): array
{
    $opts = [
        'help' => false,
        'quiet' => false,
        'repoRoot' => '',
        'inventory' => 'tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json',
        'sourceRoot' => 'source/',
        'output' => 'openspec/changes/fix-internal-shim-call-sites/findings.json',
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $opts['help'] = true;
            continue;
        }
        if ($arg === '--quiet') {
            $opts['quiet'] = true;
            continue;
        }
        if (str_starts_with($arg, '--repo-root=')) {
            $opts['repoRoot'] = substr($arg, strlen('--repo-root='));
            continue;
        }
        if (str_starts_with($arg, '--inventory=')) {
            $opts['inventory'] = substr($arg, strlen('--inventory='));
            continue;
        }
        if (str_starts_with($arg, '--source-root=')) {
            $opts['sourceRoot'] = substr($arg, strlen('--source-root='));
            continue;
        }
        if (str_starts_with($arg, '--output=')) {
            $opts['output'] = substr($arg, strlen('--output='));
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
find-internal-shim-call-sites.php — detect convention violations of #107.

Walks source/ and reports every `\$this->method(` call site where `method` is
the non-underscore counterpart of an inventory `_method` entry AND the class
declaring the call also declares both `_method()` and `method()`.

Usage:
  bin/find-internal-shim-call-sites.php [--inventory=<path>] [--source-root=<path>]
                                        [--output=<path>] [--quiet]
  bin/find-internal-shim-call-sites.php --help | -h

Defaults (resolved against the repo root):
  --inventory    tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json
  --source-root  source/
  --output       openspec/changes/fix-internal-shim-call-sites/findings.json

Example (from anywhere):
  /Users/foo/o3/shop-ce/bin/find-internal-shim-call-sites.php

See openspec/changes/fix-internal-shim-call-sites/design.md for the full design.

TXT;
}

function resolve_repo_root(string $startDir): string
{
    $cmd = sprintf('git -C %s rev-parse --show-toplevel 2>/dev/null', escapeshellarg($startDir));
    $root = trim((string) shell_exec($cmd));
    if ($root === '' || !is_dir($root)) {
        fwrite(STDERR, "error: cannot resolve repo root from $startDir (not a git work tree?)\n");
        exit(3);
    }
    return $root;
}

function is_absolute(string $path): bool
{
    return $path !== '' && $path[0] === '/';
}

function resolve_path(string $repoRoot, string $path): string
{
    if (is_absolute($path)) {
        return $path;
    }
    return rtrim($repoRoot, '/') . '/' . ltrim($path, '/');
}

/**
 * @return array<string,bool> set keyed by underscore-prefixed method names
 */
function load_inventory_method_names(string $path): array
{
    $json = file_get_contents($path);
    if ($json === false) {
        fwrite(STDERR, "error: cannot read inventory: $path\n");
        exit(3);
    }
    $entries = json_decode($json, true);
    if (!is_array($entries)) {
        fwrite(STDERR, "error: inventory is not a JSON array: $path\n");
        exit(3);
    }
    $set = [];
    foreach ($entries as $entry) {
        if (!is_array($entry) || !isset($entry['method']) || !is_string($entry['method'])) {
            continue;
        }
        $name = $entry['method'];
        if ($name !== '' && $name[0] === '_' && (strlen($name) < 2 || $name[1] !== '_')) {
            $set[$name] = true;
        }
    }
    return $set;
}

/**
 * @return array{
 *   0: array<string, array<string,bool>>,  // FQCN => set of declared method names
 *   1: list<array{file:string,line:int,class:string,method:string}>,
 *   2: array<string,string>                // FQCN => parent FQCN
 * }
 */
function scan_source_tree(string $sourceRoot, string $repoRoot): array
{
    $classMethods = [];
    $callSites = [];
    $classExtends = [];

    $iter = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS)
    );

    /** @var \SplFileInfo $fileInfo */
    foreach ($iter as $fileInfo) {
        if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
            continue;
        }
        $filePath = $fileInfo->getPathname();
        $relPath = ltrim(substr($filePath, strlen($repoRoot)), '/');
        $code = file_get_contents($filePath);
        if ($code === false) {
            continue;
        }
        scan_file($code, $relPath, $classMethods, $callSites, $classExtends);
    }

    return [$classMethods, $callSites, $classExtends];
}

/**
 * @param-out array<string, array<string,bool>> $classMethods
 * @param-out list<array{file:string,line:int,class:string,method:string}> $callSites
 * @param-out array<string,string> $classExtends FQCN => parent FQCN
 */
function scan_file(
    string $code,
    string $relPath,
    array &$classMethods,
    array &$callSites,
    array &$classExtends
): void {
    $tokens = token_get_all($code);
    $count = count($tokens);
    $namespace = '';
    $useMap = []; // alias => FQCN
    $currentClass = null;
    $braceDepth = 0;
    $classBraceDepth = null;

    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];

        if (is_array($tok) && $tok[0] === T_NAMESPACE) {
            $namespace = read_namespace($tokens, $i);
            $useMap = []; // namespace boundary resets use aliases
            continue;
        }

        // `use Foo\Bar;` and `use Foo\Bar as Baz;`
        if (is_array($tok) && $tok[0] === T_USE && $currentClass === null) {
            collect_use_aliases($tokens, $i, $useMap);
            continue;
        }

        if (is_array($tok) && ($tok[0] === T_CLASS || $tok[0] === T_TRAIT)) {
            if ($tok[0] === T_CLASS) {
                $prev = previous_significant($tokens, $i);
                if ($prev !== null && is_array($tokens[$prev]) && $tokens[$prev][0] === T_NEW) {
                    continue;
                }
            }
            $name = read_class_name($tokens, $i);
            if ($name === null) {
                continue;
            }
            $currentClass = $namespace !== '' ? $namespace . '\\' . $name : $name;
            $classMethods[$currentClass] ??= [];
            $classBraceDepth = $braceDepth;
            // Look for `extends ParentClass` after the class name
            if ($tok[0] === T_CLASS) {
                $parentFqcn = read_extends($tokens, $i, $namespace, $useMap);
                if ($parentFqcn !== null) {
                    $classExtends[$currentClass] = $parentFqcn;
                }
            }
            continue;
        }

        if ($currentClass !== null && is_array($tok) && $tok[0] === T_FUNCTION) {
            $j = next_significant($tokens, $i);
            if ($j !== null && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $methodName = $tokens[$j][1];
                $classMethods[$currentClass][$methodName] = true;
            }
            continue;
        }

        if ($currentClass !== null && is_array($tok) && $tok[0] === T_VARIABLE && $tok[1] === '$this') {
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
            $callSites[] = [
                'file' => $relPath,
                'line' => $tokens[$k][2],
                'class' => $currentClass,
                'method' => $tokens[$k][1],
            ];
            continue;
        }

        if (is_array($tok)) {
            // String interpolation `{$var}` and `${var}` — the opening is an
            // array token (T_CURLY_OPEN / T_DOLLAR_OPEN_CURLY_BRACES) but the
            // matching close is a literal `}` char. Count the open here so
            // the literal `}` doesn't unbalance the class brace depth.
            if (
                $tok[0] === T_CURLY_OPEN
                || (defined('T_DOLLAR_OPEN_CURLY_BRACES') && $tok[0] === T_DOLLAR_OPEN_CURLY_BRACES)
            ) {
                $braceDepth++;
            }
            continue;
        }

        if ($tok === '{') {
            $braceDepth++;
            continue;
        }
        if ($tok === '}') {
            $braceDepth--;
            if ($currentClass !== null && $classBraceDepth !== null && $braceDepth === $classBraceDepth) {
                $currentClass = null;
                $classBraceDepth = null;
            }
            continue;
        }
    }
}

/**
 * Read a single `use Foo\Bar [as Baz];` statement starting at $i pointing at T_USE.
 * Adds an entry alias => FQCN to $useMap. Skips function/const uses and grouped uses.
 *
 * @param-out array<string,string> $useMap
 */
function collect_use_aliases(array $tokens, int $i, array &$useMap): void
{
    $count = count($tokens);
    // Skip `function` / `const` modifiers
    $j = next_significant($tokens, $i);
    if ($j === null) {
        return;
    }
    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_FUNCTION, T_CONST], true)) {
        return;
    }

    $fqcn = '';
    $alias = '';
    $sawAs = false;
    for ($k = $j; $k < $count; $k++) {
        $tok = $tokens[$k];
        if (is_array($tok)) {
            $id = $tok[0];
            if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if ($id === T_AS) {
                $sawAs = true;
                continue;
            }
            if ($id === T_STRING) {
                if ($sawAs) {
                    $alias = $tok[1];
                } else {
                    $fqcn .= $tok[1];
                }
                continue;
            }
            if ($id === T_NS_SEPARATOR) {
                $fqcn .= '\\';
                continue;
            }
            if (defined('T_NAME_QUALIFIED') && $id === T_NAME_QUALIFIED) {
                $fqcn .= $tok[1];
                continue;
            }
            if (defined('T_NAME_FULLY_QUALIFIED') && $id === T_NAME_FULLY_QUALIFIED) {
                $fqcn .= ltrim($tok[1], '\\');
                continue;
            }
            // Anything else (e.g. `{` for grouped uses) — bail. Grouped uses
            // are uncommon in this codebase; skip rather than parse them.
            return;
        }
        if ($tok === ';' || $tok === ',') {
            break;
        }
        if ($tok === '{') {
            // Grouped use — skip.
            return;
        }
    }

    $fqcn = ltrim($fqcn, '\\');
    if ($fqcn === '') {
        return;
    }
    if ($alias === '') {
        $parts = explode('\\', $fqcn);
        $alias = end($parts);
    }
    $useMap[$alias] = $fqcn;
}

/**
 * Read `extends ParentClass` after a class declaration, returning the parent FQCN.
 */
function read_extends(array $tokens, int $classTokenIndex, string $namespace, array $useMap): ?string
{
    $count = count($tokens);
    $extendsAt = null;
    // Walk forward until '{' (class body) or end
    for ($i = $classTokenIndex + 1; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (is_array($tok) && $tok[0] === T_EXTENDS) {
            $extendsAt = $i;
            break;
        }
        if ($tok === '{') {
            return null;
        }
    }
    if ($extendsAt === null) {
        return null;
    }
    // Read the next type name token
    $name = '';
    $isFullyQualified = false;
    for ($i = $extendsAt + 1; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (is_array($tok)) {
            $id = $tok[0];
            if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if ($id === T_STRING) {
                $name .= $tok[1];
                continue;
            }
            if ($id === T_NS_SEPARATOR) {
                if ($name === '') {
                    $isFullyQualified = true;
                }
                $name .= '\\';
                continue;
            }
            if (defined('T_NAME_QUALIFIED') && $id === T_NAME_QUALIFIED) {
                $name .= $tok[1];
                continue;
            }
            if (defined('T_NAME_FULLY_QUALIFIED') && $id === T_NAME_FULLY_QUALIFIED) {
                $isFullyQualified = true;
                $name .= ltrim($tok[1], '\\');
                continue;
            }
            // Anything else means we hit `implements` or `{`
            break;
        }
        if ($tok === '{' || $tok === ',') {
            break;
        }
    }
    $name = trim($name, '\\');
    if ($name === '') {
        return null;
    }
    return resolve_name_to_fqcn($name, $isFullyQualified, $namespace, $useMap);
}

function resolve_name_to_fqcn(string $name, bool $isFullyQualified, string $namespace, array $useMap): string
{
    if ($isFullyQualified) {
        return $name;
    }
    $parts = explode('\\', $name);
    $head = $parts[0];
    if (isset($useMap[$head])) {
        $rest = array_slice($parts, 1);
        return ltrim($useMap[$head] . ($rest === [] ? '' : '\\' . implode('\\', $rest)), '\\');
    }
    if ($namespace !== '') {
        return $namespace . '\\' . $name;
    }
    return $name;
}

function read_namespace(array $tokens, int $i): string
{
    $count = count($tokens);
    $name = '';
    for ($j = $i + 1; $j < $count; $j++) {
        $tok = $tokens[$j];
        if (is_array($tok)) {
            if (in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if ($tok[0] === T_STRING || $tok[0] === T_NS_SEPARATOR) {
                $name .= $tok[1];
                continue;
            }
            // T_NAME_QUALIFIED on PHP 8+
            if (defined('T_NAME_QUALIFIED') && $tok[0] === T_NAME_QUALIFIED) {
                $name .= $tok[1];
                continue;
            }
            if (defined('T_NAME_FULLY_QUALIFIED') && $tok[0] === T_NAME_FULLY_QUALIFIED) {
                $name .= ltrim($tok[1], '\\');
                continue;
            }
        }
        break;
    }
    return $name;
}

function read_class_name(array $tokens, int $i): ?string
{
    $j = next_significant($tokens, $i);
    if ($j === null || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
        return null;
    }
    return $tokens[$j][1];
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

function previous_significant(array $tokens, int $i): ?int
{
    for ($j = $i - 1; $j >= 0; $j--) {
        $tok = $tokens[$j];
        if (is_array($tok) && in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        return $j;
    }
    return null;
}

/**
 * @param list<array{file:string,line:int,class:string,method:string}> $callSites
 * @param array<string, array<string,bool>> $classMethods
 * @param array<string,string> $classExtends
 * @return list<array{file:string,line:int,class:string,method:string}>
 */
function filter_findings(
    array $callSites,
    array $classMethods,
    array $classExtends
): array {
    $findings = [];
    $resolvedCache = [];
    foreach ($callSites as $site) {
        $method = $site['method'];
        if ($method === '' || $method[0] === '_') {
            continue;
        }
        $underscoreName = '_' . $method;
        $class = $site['class'];
        if (!isset($classMethods[$class])) {
            continue;
        }
        $resolved = $resolvedCache[$class] ??= resolve_inherited_methods($class, $classMethods, $classExtends);
        // A call site is a violation iff the class chain has both the
        // underscore form and the non-underscore form defined — i.e. a
        // BC-shim pair is live for this class.
        if (!isset($resolved[$method]) || !isset($resolved[$underscoreName])) {
            continue;
        }
        $findings[] = $site;
    }

    usort($findings, function (array $a, array $b): int {
        $cmp = strcmp($a['file'], $b['file']);
        if ($cmp !== 0) {
            return $cmp;
        }
        return $a['line'] <=> $b['line'];
    });

    return $findings;
}

/**
 * Walk the `extends` chain and return the union of method names declared on
 * the class and any of its in-scope ancestors.
 *
 * Handles the OXID virtual namespace: source files use'd parents as
 * `OxidEsales\Eshop\...` (the unified runtime alias) but the concrete classes
 * are declared under `OxidEsales\EshopCommunity\...`. When a parent FQCN
 * doesn't resolve directly, we retry under the concrete prefix.
 *
 * @param array<string, array<string,bool>> $classMethods
 * @param array<string,string> $classExtends
 * @return array<string,bool>
 */
function resolve_inherited_methods(string $class, array $classMethods, array $classExtends): array
{
    $methods = $classMethods[$class] ?? [];
    $seen = [$class => true];
    $current = $class;
    while (isset($classExtends[$current])) {
        $parent = canonicalize_fqcn($classExtends[$current], $classMethods);
        if (isset($seen[$parent])) {
            break;
        }
        $seen[$parent] = true;
        if (isset($classMethods[$parent])) {
            $methods += $classMethods[$parent];
        } else {
            break; // Parent not in source/ scope — stop walking.
        }
        $current = $parent;
    }
    return $methods;
}

/**
 * Translate an extends-resolved FQCN to the concrete `OxidEsales\EshopCommunity\...`
 * form when the source file used'd the virtual `OxidEsales\Eshop\...` alias.
 *
 * @param array<string, array<string,bool>> $classMethods
 */
function canonicalize_fqcn(string $fqcn, array $classMethods): string
{
    if (isset($classMethods[$fqcn])) {
        return $fqcn;
    }
    if (str_starts_with($fqcn, 'OxidEsales\\Eshop\\')) {
        $concrete = 'OxidEsales\\EshopCommunity\\' . substr($fqcn, strlen('OxidEsales\\Eshop\\'));
        if (isset($classMethods[$concrete])) {
            return $concrete;
        }
    }
    return $fqcn;
}

function write_findings(string $path, array $findings): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $json = json_encode($findings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    if ($json === false) {
        fwrite(STDERR, "error: failed to encode findings JSON\n");
        exit(3);
    }
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json) === false) {
        fwrite(STDERR, "error: failed to write $tmp\n");
        exit(3);
    }
    rename($tmp, $path);
}
