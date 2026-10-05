<?php

/**
 * Phase 2 Batch 4 — rewrite the PHPDoc blocks of every BC-shim pair from
 * the original PR #104 wording to the new transitional framing.
 *
 * The original wording told modules NOT to override _method() ("new code …
 * MUST NOT call or override _method()"). After #107's call-site sweep,
 * internal code now dispatches direct to _method(), so module overrides
 * of _method() are the path that wins. The new wording reflects that AND
 * flags it as transitional, pointing #108 (template-method refactor) as
 * the long-term direction that re-inverts the contract.
 *
 * Mechanical text replace. Two patterns:
 *
 * @deprecated block on _method():
 *   OLD: "Use METHOD() instead. This underscore-prefixed name is retained only
 *         for backward compatibility with module subclasses that already override
 *         it; new code, including new modules, MUST NOT call or override _METHOD()."
 *   NEW: "Transitional during #107. Modules SHOULD override _METHOD()
 *         for now — internal call paths route through it. The longer-term
 *         direction (issue #108) is a template-method refactor that
 *         promotes METHOD() to the canonical override target and retires
 *         _METHOD(); until then, _METHOD() is the safe override target.
 *         Plan extension work with both stages in mind."
 *
 * @internal block on method():
 *   OLD: "If your override does not fully replace the behavior, call parent::METHOD()
 *         (not the deprecated _METHOD()) so downstream overrides in the class chain
 *         are preserved. Template-method refactor tracked in o3-shop/o3-shop#108."
 *   NEW: "Public delegate during the #107 transition. Module subclasses
 *         SHOULD override _METHOD(), not this — internal call paths
 *         bypass this name. Issue #108 will eventually invert this and
 *         make METHOD() the canonical override target."
 *
 * Usage:
 *   apply-shim-phpdoc-transitional-rewrite.php [--dry-run]
 *
 * Walks source/ and applies the two regex replacements per file.
 */

declare(strict_types=1);

exit(main($argv));

function main(array $argv): int
{
    $dryRun = in_array('--dry-run', $argv, true);
    $repoRoot = trim((string) shell_exec('git -C ' . escapeshellarg(__DIR__) . ' rev-parse --show-toplevel 2>/dev/null'));
    $sourceRoot = $repoRoot . '/source';

    $deprecatedReplacements = 0;
    $internalReplacements = 0;
    $filesTouched = 0;

    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $info) {
        if (!$info->isFile() || $info->getExtension() !== 'php') {
            continue;
        }
        $path = $info->getPathname();
        $rel = ltrim(substr($path, strlen($repoRoot)), '/');
        $code = file_get_contents($path);
        if ($code === false) {
            continue;
        }
        $original = $code;

        // 1) @deprecated block rewrite. Pattern matches the canonical PR #104 wording.
        $code = preg_replace_callback(
            '/^([ \t]*\*) @deprecated Use ([a-zA-Z][a-zA-Z0-9_]*)\(\) instead\. This underscore-prefixed name is retained only\n\1\s+for backward compatibility with module subclasses that already override\n\1\s+it; new code, including new modules, MUST NOT call or override _\2\(\)\.$/m',
            function (array $m): string {
                $star = $m[1];
                $bare = $m[2];
                $under = '_' . $bare;
                $indent = $star . ' ';
                $contIndent = preg_replace('/\* $/', ' *  ', $indent);
                return implode("\n", [
                    "{$indent}@deprecated Transitional during #107. Modules SHOULD override {$under}()",
                    "{$contIndent}           for now — internal call paths route through it. The",
                    "{$contIndent}           longer-term direction (issue #108) is a template-method",
                    "{$contIndent}           refactor that promotes {$bare}() to the canonical override",
                    "{$contIndent}           target and retires {$under}(); until then, {$under}() is the",
                    "{$contIndent}           safe override target. Plan extension work with both stages",
                    "{$contIndent}           in mind.",
                ]);
            },
            $code,
            -1,
            $deprecatedCount
        );
        $deprecatedReplacements += $deprecatedCount;

        // 2) @internal block rewrite.
        $code = preg_replace_callback(
            '/^([ \t]*\*) @internal If your override does not fully replace the behavior, call parent::([a-zA-Z][a-zA-Z0-9_]*)\(\)\n\1\s+\(not the deprecated _\2\(\)\) so downstream overrides in the class chain\n\1\s+are preserved\. Template-method refactor tracked in o3-shop\/o3-shop#108\.$/m',
            function (array $m): string {
                $star = $m[1];
                $bare = $m[2];
                $under = '_' . $bare;
                $indent = $star . ' ';
                $contIndent = preg_replace('/\* $/', ' *  ', $indent);
                return implode("\n", [
                    "{$indent}@internal Public delegate during the #107 transition. Module subclasses",
                    "{$contIndent}         SHOULD override {$under}(), not this — internal call paths",
                    "{$contIndent}         bypass this name. Issue #108 will eventually invert this and",
                    "{$contIndent}         make {$bare}() the canonical override target.",
                ]);
            },
            $code,
            -1,
            $internalCount
        );
        $internalReplacements += $internalCount;

        if ($code !== $original) {
            $filesTouched++;
            if (!$dryRun) {
                file_put_contents($path, $code);
            }
            printf("  %s: %d @deprecated, %d @internal\n", $rel, $deprecatedCount, $internalCount);
        }
    }

    printf(
        "%s: %d @deprecated + %d @internal block rewrites across %d files.\n",
        $dryRun ? 'Dry run' : 'Applied',
        $deprecatedReplacements,
        $internalReplacements,
        $filesTouched
    );
    return 0;
}
