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
 * Generator for the baseline inventory of protected/public _method() names,
 * consumed by InheritanceContractTest. The inventory is pinned to a specific
 * historical revision — see design doc D1/D2 — so that later refactors do not
 * shrink the test's coverage.
 *
 * Design: openspec/changes/fix-underscore-method-inheritance/design.md (D1, D2)
 * Spec:   openspec/changes/fix-underscore-method-inheritance/specs/
 *         legacy-method-inheritance-contract/spec.md
 */

declare(strict_types=1);

const DEFAULT_REVISION = 'ebe86dc08875034d5a3d0533b7cbdede7cc6abff';
const DEFAULT_OUTPUT_RELATIVE = 'tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json';

exit(main($argv));

function main(array $argv): int
{
    $args = parse_args($argv);

    if ($args['help']) {
        echo usage_text();
        return 0;
    }

    $scriptDir = __DIR__;
    $repoRoot = resolve_repo_root($scriptDir);
    validate_revision($repoRoot, $args['revision']);

    $cwd = getcwd() ?: $repoRoot;
    $outputPath = resolve_output_path($args['output'], $cwd, $repoRoot);

    $tempDir = make_temp_dir();
    register_shutdown_function(fn () => cleanup_temp_dir($tempDir));

    extract_archive($repoRoot, $args['revision'], $tempDir);

    $entries = [];
    $snapshotPrefix = rtrim($tempDir, '/') . '/';
    foreach (walk_php_files($tempDir) as $phpFile) {
        $relative = substr($phpFile, strlen($snapshotPrefix));
        // Inventory is scoped to production code under source/. Tests, bin, and
        // tooling are not part of the shop's module BC surface and are
        // excluded (see spec: "Pinned baseline inventory").
        if (!str_starts_with($relative, 'source/')) {
            continue;
        }
        foreach (extract_entries($phpFile, $tempDir) as $entry) {
            $entries[] = $entry;
        }
    }

    $entries = filter_unprobable_entries($entries, $repoRoot);

    usort($entries, function (array $a, array $b): int {
        return [$a['class'], $a['method']] <=> [$b['class'], $b['method']];
    });

    $json = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    write_atomic($outputPath, $json);

    fprintf(STDERR, "wrote %d entries to %s\n", count($entries), $outputPath);
    return 0;
}

/**
 * Drop baseline entries whose class has no current unified-namespace alias,
 * because the contract test reflects via the unified namespace and cannot
 * probe them.
 *
 * Two known causes for a missing alias:
 *  - the class was removed from the current tree after the baseline revision
 *    (e.g. Core\CreditCardValidator, removed in e18ac787 on 2023-03-26), or
 *  - the class lives in a source tree that is excluded from the unified
 *    namespace generator by design (source/Setup/ runs before the shop is
 *    bootstrapped, so no virtual aliases are generated for it).
 *
 * Both cases produce PHPUnit `skipped` outcomes at runtime with no useful
 * signal; filtering here keeps the inventory to entries the test can actually
 * exercise.
 *
 * @param array<int, array<string, mixed>> $entries
 * @return array<int, array<string, mixed>>
 */
function filter_unprobable_entries(array $entries, string $repoRoot): array
{
    $aliasRoot = $repoRoot . '/vendor/o3-shop/shop-unified-namespace-generator/generated';
    $currentMethods = [];
    $kept = [];
    foreach ($entries as $entry) {
        $class = (string) $entry['class'];
        $method = (string) $entry['method'];

        // Translate OxidEsales\EshopCommunity\... → OxidEsales\Eshop\...
        $unified = preg_replace('/^OxidEsales\\\\EshopCommunity\\\\/', 'OxidEsales\\\\Eshop\\\\', $class);
        $aliasFile = $aliasRoot . '/' . str_replace('\\', '/', $unified) . '.php';
        if (!is_file($aliasFile)) {
            continue;
        }

        // Also drop if the underscore method no longer exists in the concrete
        // current source. Method-level removal (e.g. e18ac787 stripped a set
        // of credit-card-related helpers) has no probe target.
        $concreteFile = $repoRoot . '/source/' . str_replace('\\', '/', substr($class, strlen('OxidEsales\\EshopCommunity\\'))) . '.php';
        if (!isset($currentMethods[$concreteFile])) {
            $currentMethods[$concreteFile] = is_file($concreteFile)
                ? extract_method_names($concreteFile)
                : [];
        }
        if (!in_array($method, $currentMethods[$concreteFile], true)) {
            continue;
        }

        $kept[] = $entry;
    }
    return $kept;
}

/**
 * Return the list of method names declared in a PHP file.
 *
 * @return array<int, string>
 */
function extract_method_names(string $file): array
{
    $source = @file_get_contents($file);
    if ($source === false) {
        return [];
    }
    if (!preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/i', $source, $m)) {
        return [];
    }
    return array_values(array_unique($m[1]));
}

function usage_text(): string
{
    $rev = DEFAULT_REVISION;
    $out = DEFAULT_OUTPUT_RELATIVE;

    return <<<USAGE
Usage: generate-underscore-method-snapshot.php [OPTIONS]

Extract the inventory of protected/public methods whose name begins with an
underscore, from a pinned git revision, as the authoritative input to the
inheritance-contract test.

Options:
  --revision=<rev>   Git revision (SHA, tag, branch) to extract from.
                     Default: {$rev}
                     (the baseline before e4e180cc introduced the broken
                     BC-shim pattern).
  --output=<path>    Where to write the inventory JSON.
                     Absolute paths are used as-is. Relative paths are
                     resolved against the caller's current working directory
                     (POSIX convention).
                     Default: <repo-root>/{$out}
  -h, --help         Print this help and exit 0. No git calls, no file
                     writes, no directory changes. Takes precedence over all
                     other flags.

Example (from outside the repo):
  php /path/to/shop-ce/tests/Unit/BackwardsCompatibility/generate-underscore-method-snapshot.php

The script resolves the repo root from its own location
(git -C __DIR__ rev-parse --show-toplevel) and does not trust the caller's
current working directory.

Design: openspec/changes/fix-underscore-method-inheritance/design.md (D2)

USAGE;
}

function parse_args(array $argv): array
{
    $help = false;
    $revision = DEFAULT_REVISION;
    $output = null;

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $help = true;
        } elseif (str_starts_with($arg, '--revision=')) {
            $value = substr($arg, strlen('--revision='));
            if ($value === '') {
                die_err('--revision requires a non-empty value');
            }
            $revision = $value;
        } elseif (str_starts_with($arg, '--output=')) {
            $value = substr($arg, strlen('--output='));
            if ($value === '') {
                die_err('--output requires a non-empty value');
            }
            $output = $value;
        } else {
            die_err("unknown argument: {$arg} (try --help)");
        }
    }

    return ['help' => $help, 'revision' => $revision, 'output' => $output];
}

function resolve_repo_root(string $scriptDir): string
{
    $r = run_process(['git', '-C', $scriptDir, 'rev-parse', '--show-toplevel']);
    if ($r['code'] !== 0) {
        die_err(
            "script location '{$scriptDir}' is not inside a git work tree.\n" .
            "This script must be run from within the shop-ce repository.\n" .
            trim($r['stderr'])
        );
    }
    return rtrim($r['stdout']);
}

function validate_revision(string $repoRoot, string $revision): void
{
    $r = run_process(['git', '-C', $repoRoot, 'rev-parse', '--verify', $revision . '^{commit}']);
    if ($r['code'] !== 0) {
        die_err(
            "revision '{$revision}' does not exist in repo at {$repoRoot}.\n" .
            "Are you in the correct clone of o3-shop/shop-ce?\n" .
            trim($r['stderr'])
        );
    }
}

function resolve_output_path(?string $outputArg, string $cwd, string $repoRoot): string
{
    if ($outputArg === null) {
        return $repoRoot . '/' . DEFAULT_OUTPUT_RELATIVE;
    }
    if (str_starts_with($outputArg, '/')) {
        return $outputArg;
    }
    return $cwd . '/' . $outputArg;
}

function make_temp_dir(): string
{
    $dir = sys_get_temp_dir() . '/o3-underscore-inventory-' . bin2hex(random_bytes(6));
    if (!mkdir($dir, 0700, true)) {
        die_err("could not create temp dir {$dir}");
    }
    return $dir;
}

function cleanup_temp_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $path) {
        /** @var SplFileInfo $path */
        if ($path->isDir()) {
            @rmdir($path->getPathname());
        } else {
            @unlink($path->getPathname());
        }
    }
    @rmdir($dir);
}

function extract_archive(string $repoRoot, string $revision, string $tempDir): void
{
    $tarPath = $tempDir . '/snapshot.tar';
    $r = run_process(['git', '-C', $repoRoot, 'archive', '--format=tar', '-o', $tarPath, $revision]);
    if ($r['code'] !== 0) {
        die_err("git archive failed for revision {$revision}: " . trim($r['stderr']));
    }
    $r = run_process(['tar', '-xf', $tarPath, '-C', $tempDir]);
    if ($r['code'] !== 0) {
        die_err('tar extract failed: ' . trim($r['stderr']));
    }
    @unlink($tarPath);
}

function walk_php_files(string $root): iterable
{
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $path) {
        /** @var SplFileInfo $path */
        if ($path->isFile() && strtolower($path->getExtension()) === 'php') {
            yield $path->getPathname();
        }
    }
}

function extract_entries(string $filePath, string $snapshotRoot): array
{
    $source = @file_get_contents($filePath);
    if ($source === false) {
        return [];
    }
    if (!preg_match('/function\s+_/i', $source)) {
        return [];
    }

    $tokens = @token_get_all($source);
    if (!is_array($tokens)) {
        return [];
    }

    $relativePath = ltrim(substr($filePath, strlen($snapshotRoot)), '/');

    $namespace = '';
    $depth = 0;
    $classStack = [];
    $modifiers = [];
    $pendingClass = null;
    $pendingIsClass = false;

    $entries = [];
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];

        if (is_string($tok)) {
            if ($tok === '{') {
                $depth++;
                if ($pendingClass !== null) {
                    $classStack[] = [
                        'name' => $pendingClass,
                        'depth' => $depth,
                        'isClass' => $pendingIsClass,
                    ];
                    $pendingClass = null;
                    $pendingIsClass = false;
                }
                $modifiers = [];
            } elseif ($tok === '}') {
                if (!empty($classStack)) {
                    $top = $classStack[array_key_last($classStack)];
                    if ($top['depth'] === $depth) {
                        array_pop($classStack);
                    }
                }
                $depth--;
                $modifiers = [];
            } elseif ($tok === ';') {
                $modifiers = [];
            }
            continue;
        }

        [$id] = [$tok[0]];

        if ($id === T_NAMESPACE) {
            $ns = '';
            $j = $i + 1;
            for (; $j < $count; $j++) {
                $t = $tokens[$j];
                if (is_string($t)) {
                    if ($t === ';' || $t === '{') {
                        break;
                    }
                    continue;
                }
                if (
                    $t[0] === T_STRING
                    || $t[0] === T_NS_SEPARATOR
                    || (defined('T_NAME_QUALIFIED') && $t[0] === T_NAME_QUALIFIED)
                    || (defined('T_NAME_FULLY_QUALIFIED') && $t[0] === T_NAME_FULLY_QUALIFIED)
                ) {
                    $ns .= $t[1];
                }
            }
            $namespace = trim($ns, '\\');
            $i = $j;
            continue;
        }

        if ($id === T_CLASS || $id === T_INTERFACE || $id === T_TRAIT) {
            $isAnonymous = false;
            for ($k = $i - 1; $k >= 0; $k--) {
                $prev = $tokens[$k];
                if (is_array($prev) && in_array($prev[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                if (is_array($prev) && $prev[0] === T_NEW) {
                    $isAnonymous = true;
                }
                break;
            }

            if ($isAnonymous) {
                $pendingClass = '__ANON__';
                $pendingIsClass = ($id === T_CLASS);
                $modifiers = [];
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $t = $tokens[$j];
                if (is_array($t) && $t[0] === T_STRING) {
                    $pendingClass = $t[1];
                    $pendingIsClass = ($id === T_CLASS);
                    $i = $j;
                    break;
                }
                if (is_string($t)) {
                    break;
                }
            }
            continue;
        }

        if (
            $id === T_PUBLIC
            || $id === T_PROTECTED
            || $id === T_PRIVATE
            || $id === T_STATIC
            || $id === T_ABSTRACT
            || $id === T_FINAL
            || (defined('T_READONLY') && $id === T_READONLY)
        ) {
            $modifiers[] = $id;
            continue;
        }

        if ($id === T_FUNCTION) {
            if (empty($classStack)) {
                $modifiers = [];
                continue;
            }
            $frame = $classStack[array_key_last($classStack)];

            if (!$frame['isClass'] || $frame['name'] === '__ANON__' || $frame['depth'] !== $depth) {
                $modifiers = [];
                continue;
            }

            $visibility = null;
            $isStatic = false;
            $isAbstract = false;
            foreach ($modifiers as $m) {
                if ($m === T_PUBLIC) {
                    $visibility = 'public';
                } elseif ($m === T_PROTECTED) {
                    $visibility = 'protected';
                } elseif ($m === T_PRIVATE) {
                    $visibility = 'private';
                } elseif ($m === T_STATIC) {
                    $isStatic = true;
                } elseif ($m === T_ABSTRACT) {
                    $isAbstract = true;
                }
            }
            $modifiers = [];
            if ($visibility === null) {
                $visibility = 'public';
            }

            $methodName = null;
            for ($j = $i + 1; $j < $count; $j++) {
                $t = $tokens[$j];
                if (is_array($t)) {
                    if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                        continue;
                    }
                    if ($t[0] === T_STRING) {
                        $methodName = $t[1];
                        $i = $j;
                    }
                }
                break;
            }
            if ($methodName === null) {
                continue;
            }

            if (
                str_starts_with($methodName, '_')
                && !str_starts_with($methodName, '__')
                && ($visibility === 'public' || $visibility === 'protected')
            ) {
                $className = $namespace !== ''
                    ? $namespace . '\\' . $frame['name']
                    : $frame['name'];
                $entries[] = [
                    'class' => $className,
                    'method' => $methodName,
                    'visibility' => $visibility,
                    'is_static' => $isStatic,
                    'is_abstract' => $isAbstract,
                    'baseline_file' => $relativePath,
                ];
            }
            continue;
        }
    }

    return $entries;
}

function write_atomic(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        die_err("could not create output directory {$dir}");
    }
    $tmp = $dir . '/.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $content) === false) {
        die_err("could not write to {$tmp}");
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        die_err("could not move {$tmp} to {$path}");
    }
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
        die_err('failed to start: ' . implode(' ', $cmd));
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
