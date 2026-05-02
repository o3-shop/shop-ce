<?php

/**
 * Companion to apply-internal-shim-call-site-sweep.php — updates test
 * mock-string targets when the corresponding production call sites were
 * rewritten from $this->method( to $this->_method(.
 *
 * Tokenizer-based: looks for getMock() / createMock() / createPartialMock()
 * invocations and rewrites string literals inside their method-name array
 * argument when the bare name has its underscore counterpart in the swept
 * set. Other occurrences of the same string are left alone.
 *
 * Usage:
 *   apply-test-mock-name-sweep.php [--findings=<path>] [--exclusions=<path>]
 *                                   [--prefix=<dir>] [--file-pattern=<regex>]
 *                                   [--tests-root=<dir>] [--dry-run] [--quiet]
 *
 * Defaults:
 *   --findings    openspec/changes/fix-internal-shim-call-sites/findings.json
 *   --exclusions  tests/Unit/BackwardsCompatibility/internal-shim-call-sites-exclusions.json
 *   --tests-root  tests/Unit/
 *   --prefix      (none — applies to whole tests root)
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
    $exclusionsPath = resolve_path($repoRoot, $opts['exclusions']);
    $testsRoot = resolve_path($repoRoot, $opts['testsRoot']);

    $findings = load_findings_subset($findingsPath, $exclusionsPath, $opts['prefix'], $opts['filePattern']);
    if ($findings === []) {
        if (!$opts['quiet']) {
            echo "No in-scope findings under prefix/pattern; nothing to do.\n";
        }
        return 0;
    }

    // Build per-class swept-name sets. A test mock 'method' is rewritten
    // only when the mocked class matches one of these and the method
    // appears in its swept set. A class is keyed by its short name (for
    // matching against `Class::class` in tests, which references the
    // unified `\OxidEsales\Eshop\...` namespace), and we accept the FQCN
    // suffix match.
    /** @var array<string, array<string,bool>> $sweptByClass */
    $sweptByClass = [];
    foreach ($findings as $f) {
        $sweptByClass[$f['class']][$f['method']] = true;
    }
    if (!$opts['quiet']) {
        printf("Swept classes in batch: %d (across %d findings)\n", count($sweptByClass), count($findings));
    }

    // Walk every .php under testsRoot and rewrite mock string targets.
    $touched = 0;
    $rewrites = 0;
    $iter = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($testsRoot, \FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $info) {
        if (!$info->isFile() || $info->getExtension() !== 'php') {
            continue;
        }
        $path = $info->getPathname();
        $code = file_get_contents($path);
        if ($code === false) {
            continue;
        }
        [$newCode, $count] = rewrite_mocks_in_file($code, $sweptByClass);
        if ($count > 0) {
            $rewrites += $count;
            $touched++;
            $rel = ltrim(substr($path, strlen($repoRoot)), '/');
            if (!$opts['quiet']) {
                printf("  %s: %d mock rewrite%s\n", $rel, $count, $count === 1 ? '' : 's');
            }
            if (!$opts['dryRun']) {
                file_put_contents($path, $newCode);
            }
        }
    }

    if (!$opts['quiet']) {
        printf(
            "%s: %d mock rewrite%s across %d test file%s.\n",
            $opts['dryRun'] ? 'Dry run' : 'Applied',
            $rewrites,
            $rewrites === 1 ? '' : 's',
            $touched,
            $touched === 1 ? '' : 's'
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
        'testsRoot' => 'tests/Unit/',
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
        if (str_starts_with($arg, '--exclusions=')) {
            $opts['exclusions'] = substr($arg, strlen('--exclusions='));
            continue;
        }
        if (str_starts_with($arg, '--tests-root=')) {
            $opts['testsRoot'] = substr($arg, strlen('--tests-root='));
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
        fwrite(STDERR, "error: unknown argument: $arg (try --help)\n");
        exit(2);
    }
    return $opts;
}

function usage_text(): string
{
    return <<<TXT
apply-test-mock-name-sweep.php — update getMock() string targets to match
the production call-site sweep done by apply-internal-shim-call-site-sweep.php.

When production code is rewritten from \$this->method( to \$this->_method(,
unit tests that mock 'method' must also flip to '_method', otherwise the
mock no longer intercepts the call. This script does that update,
constrained to the same batch (--prefix / --file-pattern as the sweep).

Tokenizer-based: only string literals inside getMock / createMock /
createPartialMock array arguments are touched.

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
 * @return list<array{file:string, line:int, method:string}>
 */
function load_findings_subset(string $findingsPath, string $exclusionsPath, string $prefix, string $filePattern): array
{
    $findings = json_decode((string) file_get_contents($findingsPath), true);
    if (!is_array($findings)) {
        return [];
    }

    $excluded = [];
    if (is_file($exclusionsPath)) {
        $exDecoded = json_decode((string) file_get_contents($exclusionsPath), true);
        if (is_array($exDecoded) && isset($exDecoded['exclusions']) && is_array($exDecoded['exclusions'])) {
            foreach ($exDecoded['exclusions'] as $e) {
                if (isset($e['file'], $e['line'], $e['method'])) {
                    $excluded[$e['file'] . '|' . $e['line'] . '|' . $e['method']] = true;
                }
            }
        }
    }

    $out = [];
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
        if (isset($excluded[$key])) {
            continue;
        }
        $out[] = $f;
    }
    return $out;
}

/**
 * Two-pass rewrite per file:
 * 1. Walk tokens; for each getMock/createMock/createPartialMock call whose
 *    first arg is a swept class, collect the set of swept method names
 *    that appear as string literals in that call (the mock array).
 * 2. With the union of all such names for this file, rewrite every
 *    matching string literal `'name'` / `"name"` to its underscore form
 *    throughout the file. This catches both the array entry and the
 *    chained `->method('name')` references.
 *
 * @param array<string, array<string,bool>> $sweptByClass FQCN => method-set
 * @return array{0:string, 1:int}
 */
function rewrite_mocks_in_file(string $code, array $sweptByClass): array
{
    $tokens = token_get_all($code);
    $count = count($tokens);

    $mockMethods = ['getMock' => true, 'createMock' => true, 'createPartialMock' => true];

    // Lookup keyed by short class name (tests usually reference classes
    // via `Class::class`, which yields the short name).
    $sweptByShort = [];
    foreach ($sweptByClass as $fqcn => $methods) {
        $parts = explode('\\', $fqcn);
        $short = end($parts);
        if (isset($sweptByShort[$short])) {
            $sweptByShort[$short] = $sweptByShort[$short] + $methods;
        } else {
            $sweptByShort[$short] = $methods;
        }
    }

    // Pass 1: discover which method names this file mocks-on-swept-class.
    $namesToRewrite = [];
    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (!is_array($tok) || $tok[0] !== T_STRING || !isset($mockMethods[$tok[1]])) {
            continue;
        }
        $j = next_significant($tokens, $i);
        if ($j === null || $tokens[$j] !== '(') {
            continue;
        }

        $shortName = read_first_arg_class($tokens, $j + 1);
        if ($shortName === null) {
            continue;
        }
        $sweptForThisClass = $sweptByShort[$shortName] ?? null;
        if ($sweptForThisClass === null) {
            continue;
        }

        // Walk through the call args collecting string literals inside
        // array contexts; mark any whose unquoted value is in swept set.
        $parenDepth = 1;
        $bracketDepth = 0;
        $inArrayArg = false;
        $k = $j + 1;
        while ($k < $count && $parenDepth > 0) {
            $tk = $tokens[$k];
            if (is_array($tk)) {
                if ($tk[0] === T_ARRAY) {
                    $inArrayArg = true;
                } elseif ($tk[0] === T_CONSTANT_ENCAPSED_STRING && ($inArrayArg || $bracketDepth > 0)) {
                    $unquoted = unquote_literal($tk[1]);
                    if ($unquoted !== null && isset($sweptForThisClass[$unquoted])) {
                        $namesToRewrite[$unquoted] = true;
                    }
                }
            } else {
                if ($tk === '(') {
                    $parenDepth++;
                } elseif ($tk === ')') {
                    $parenDepth--;
                    if ($parenDepth === 0) {
                        break;
                    } if ($inArrayArg && $bracketDepth === 0) {
                        $inArrayArg = false;
                    }
                } elseif ($tk === '[') {
                    $bracketDepth++;
                } elseif ($tk === ']') {
                    $bracketDepth--;
                }
            }
            $k++;
        }
    }

    if ($namesToRewrite === []) {
        return [$code, 0];
    }

    // Pass 2: rewrite every matching string literal in the file. Tokens
    // already loaded; just scan once more.
    $rewrites = 0;
    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (!is_array($tok) || $tok[0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;
        }
        $unquoted = unquote_literal($tok[1]);
        if ($unquoted === null || !isset($namesToRewrite[$unquoted])) {
            continue;
        }
        $newLiteral = requote_literal($tok[1], '_' . $unquoted);
        $tokens[$i] = [$tok[0], $newLiteral, $tok[2]];
        $rewrites++;
    }

    if ($rewrites === 0) {
        return [$code, 0];
    }

    $rebuilt = '';
    foreach ($tokens as $tok) {
        $rebuilt .= is_array($tok) ? $tok[1] : $tok;
    }
    return [$rebuilt, $rewrites];
}

/**
 * Read the first argument of a getMock-like call starting at the given
 * index (just inside the opening paren). Return the short class name if
 * the first arg is a `Class::class` reference or a string literal with a
 * class name; otherwise null.
 */
function read_first_arg_class(array $tokens, int $start): ?string
{
    $count = count($tokens);
    // Read tokens up to the first comma, ignoring whitespace/comments.
    $segment = [];
    $depth = 0;
    for ($i = $start; $i < $count; $i++) {
        $tk = $tokens[$i];
        if (is_array($tk)) {
            if (in_array($tk[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $segment[] = $tk;
        } else {
            if ($tk === '(') {
                $depth++;
                $segment[] = $tk;
            } elseif ($tk === ')') {
                if ($depth === 0) {
                    break;
                } $depth--;
                $segment[] = $tk;
            } elseif ($tk === ',' && $depth === 0) {
                break;
            } else {
                $segment[] = $tk;
            }
        }
    }
    // Look for `Class::class`: T_STRING/qualified name followed by ::class
    $name = '';
    foreach ($segment as $tok) {
        if (!is_array($tok)) {
            continue;
        }
        $id = $tok[0];
        if ($id === T_STRING) {
            $name .= $tok[1];
            continue;
        }
        if ($id === T_NS_SEPARATOR) {
            $name .= '\\';
            continue;
        }
        if (defined('T_NAME_QUALIFIED') && $id === T_NAME_QUALIFIED) {
            $name .= $tok[1];
            continue;
        }
        if (defined('T_NAME_FULLY_QUALIFIED') && $id === T_NAME_FULLY_QUALIFIED) {
            $name .= ltrim($tok[1], '\\');
            continue;
        }
        if ($id === T_DOUBLE_COLON || $id === T_CLASS_C) {
            continue;
        }
        if ($id === T_CONSTANT_ENCAPSED_STRING) {
            $unq = unquote_string_with_namespace($tok[1]);
            if ($unq !== null) {
                $name = $unq;
            }
            continue;
        }
    }
    if ($name === '') {
        return null;
    }
    $parts = explode('\\', trim($name, '\\'));
    return end($parts) ?: null;
}

function unquote_string_with_namespace(string $literal): ?string
{
    if (strlen($literal) < 2) {
        return null;
    }
    $first = $literal[0];
    if ($first !== "'" && $first !== '"') {
        return null;
    }
    $inner = substr($literal, 1, -1);
    if (preg_match('/^[\\\\A-Za-z_][\\\\A-Za-z0-9_]*$/', $inner)) {
        return $inner;
    }
    return null;
}

function unquote_literal(string $literal): ?string
{
    if (strlen($literal) < 2) {
        return null;
    }
    $first = $literal[0];
    $last = $literal[strlen($literal) - 1];
    if ($first !== $last) {
        return null;
    }
    if ($first !== "'" && $first !== '"') {
        return null;
    }
    $inner = substr($literal, 1, -1);
    // Reject anything with escapes or quotes inside; we only care about
    // simple identifier-like literals.
    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $inner)) {
        return $inner;
    }
    return null;
}

function requote_literal(string $literal, string $newInner): string
{
    $first = $literal[0];
    return $first . $newInner . $first;
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
