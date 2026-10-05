<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, version 3.
 *
 * Convention-enforcement test for internal call sites of BC-shim methods.
 *
 * For every (Class, _method) entry in the pinned inventory, the test asserts
 * that no `$this->method(...)` call site exists in source/ inside a class that
 * declares both `_method()` and `method()` — every such call SHOULD be written
 * as `$this->_method(...)` so internal code dispatches direct to the BC anchor.
 *
 * Companion to InheritanceContractTest:
 *  - InheritanceContractTest enforces the structural contract: a subclass
 *    override of `_method()` is dispatched through every public entry point.
 *  - This test enforces the call-site convention: internal code uses the
 *    underscore form so dispatch lands directly on `_method()` overrides
 *    rather than detouring through the public delegate.
 *
 * A failure here is convention drift, not a correctness regression — both call
 * forms reach a `_method()` override via virtual dispatch today. The narrowing
 * is deliberate: the codebase commits to `_method()` as the canonical override
 * target during the BC-shim transition. Long-term direction (#108) will invert
 * this and promote `method()` to canonical via a template-method refactor.
 *
 * See:
 *   - openspec/changes/fix-internal-shim-call-sites/proposal.md
 *   - openspec/changes/fix-internal-shim-call-sites/design.md
 *   - bin/find-internal-shim-call-sites.php (the detector this test runs)
 *   - o3-shop/o3-shop#107 (call-site sweep), #108 (template-method refactor)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\BackwardsCompatibility;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class InternalShimCallSitesTest extends TestCase
{
    private const INVENTORY_PATH = __DIR__ . '/underscore-method-snapshot.json';
    private const SOURCE_ROOT = __DIR__ . '/../../../source';
    private const FINDINGS_PATH = __DIR__
        . '/../../../openspec/changes/fix-internal-shim-call-sites/findings.json';
    private const DETECTOR_SCRIPT = __DIR__ . '/../../../bin/find-internal-shim-call-sites.php';
    private const EXCLUSIONS_PATH = __DIR__ . '/internal-shim-call-sites-exclusions.json';

    /**
     * Run the detector and assert no findings remain.
     *
     * The detector script is the single source of truth for what constitutes a
     * violation; this test invokes it as a subprocess so the convention is
     * enforced identically whether a developer runs the script manually or the
     * test runs in CI. The script writes findings to FINDINGS_PATH; if the
     * file is non-empty after the run, the test fails with the violation list
     * inlined into the assertion message so it shows up directly in PHPUnit
     * output without requiring the developer to open the JSON file.
     */
    public function testInternalCallSitesUseUnderscoreForm(): void
    {
        $this->assertFileExists(
            self::INVENTORY_PATH,
            'Pinned baseline inventory missing — required input for the detector.'
        );
        $this->assertFileExists(
            self::DETECTOR_SCRIPT,
            'Detector script missing — bin/find-internal-shim-call-sites.php should exist.'
        );

        $repoRoot = realpath(__DIR__ . '/../../..');
        $cmd = sprintf(
            '%s %s --quiet --repo-root=%s --inventory=%s --source-root=%s --output=%s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(self::DETECTOR_SCRIPT),
            escapeshellarg($repoRoot),
            escapeshellarg(realpath(self::INVENTORY_PATH)),
            escapeshellarg(realpath(self::SOURCE_ROOT)),
            escapeshellarg(self::FINDINGS_PATH)
        );
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        $this->assertSame(
            0,
            $exitCode,
            sprintf(
                "Detector exited with code %d. Output:\n%s",
                $exitCode,
                implode("\n", $output)
            )
        );

        $findings = $this->loadFindings();
        $exclusions = $this->loadExclusions();
        $unexcluded = $this->subtractExclusions($findings, $exclusions);

        $this->assertCount(
            0,
            $unexcluded,
            $this->formatFindingsMessage($unexcluded)
        );
    }

    /**
     * @return list<array{file:string,line:int,class:string,method:string}>
     */
    private function loadFindings(): array
    {
        if (!is_file(self::FINDINGS_PATH)) {
            return [];
        }
        $json = file_get_contents(self::FINDINGS_PATH);
        if ($json === false) {
            throw new RuntimeException('Could not read findings file at ' . self::FINDINGS_PATH);
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Findings file is not a JSON array: ' . self::FINDINGS_PATH);
        }
        /** @var list<array{file:string,line:int,class:string,method:string}> $decoded */
        return $decoded;
    }

    /**
     * @return list<array{file:string,line:int,method:string}>
     */
    private function loadExclusions(): array
    {
        if (!is_file(self::EXCLUSIONS_PATH)) {
            return [];
        }
        $json = file_get_contents(self::EXCLUSIONS_PATH);
        if ($json === false) {
            throw new RuntimeException('Could not read exclusions at ' . self::EXCLUSIONS_PATH);
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !isset($decoded['exclusions']) || !is_array($decoded['exclusions'])) {
            throw new RuntimeException(
                'Exclusions file shape: expected {"exclusions": [...]} at ' . self::EXCLUSIONS_PATH
            );
        }
        /** @var list<array{file:string,line:int,method:string}> $entries */
        $entries = $decoded['exclusions'];
        return $entries;
    }

    /**
     * @param list<array{file:string,line:int,class:string,method:string}> $findings
     * @param list<array{file:string,line:int,method:string}> $exclusions
     * @return list<array{file:string,line:int,class:string,method:string}>
     */
    private function subtractExclusions(array $findings, array $exclusions): array
    {
        $excluded = [];
        foreach ($exclusions as $e) {
            $excluded[$e['file'] . '|' . $e['line'] . '|' . $e['method']] = true;
        }
        $remaining = [];
        foreach ($findings as $f) {
            $key = $f['file'] . '|' . $f['line'] . '|' . $f['method'];
            if (!isset($excluded[$key])) {
                $remaining[] = $f;
            }
        }
        return $remaining;
    }

    /**
     * @param list<array{file:string,line:int,class:string,method:string}> $findings
     */
    private function formatFindingsMessage(array $findings): string
    {
        $count = count($findings);
        if ($count === 0) {
            return '';
        }
        $lines = [
            sprintf(
                '%d internal call-site convention violation%s found.',
                $count,
                $count === 1 ? '' : 's'
            ),
            'Each entry below is a `$this->method(...)` call that SHOULD be `$this->_method(...)`:',
            '',
        ];
        foreach ($findings as $f) {
            $lines[] = sprintf('  %s:%d — %s::%s()', $f['file'], $f['line'], $f['class'], $f['method']);
        }
        $lines[] = '';
        $lines[] = sprintf(
            'Full list at %s. See bin/find-internal-shim-call-sites.php and o3-shop/o3-shop#107.',
            ltrim(str_replace(__DIR__ . '/../../../', '', self::FINDINGS_PATH), '/')
        );
        return implode("\n", $lines);
    }
}
