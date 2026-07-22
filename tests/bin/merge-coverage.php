<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

/**
 * Merges the coverage of the parallel run (--exclude-group parallel-unsafe) and
 * the serial run (--group parallel-unsafe) into ONE report set, so
 * ./docker.sh test-all-coverage produces a single clover XML + HTML + JUnit that
 * reflects BOTH runs.
 *
 * Inputs (produced by run-tests.sh, relative to the shop root):
 *   coverage/_parallel.cov        php-code-coverage dump of the parallel run
 *   coverage/_serial.cov          php-code-coverage dump of the serial run
 *   coverage/_junit_parallel.xml  JUnit of the parallel run
 *   coverage/_junit_serial.xml    JUnit of the serial run
 *
 * Outputs (the report set test-all-coverage / the coverage gate expect):
 *   coverage/coverage.xml         merged Clover
 *   coverage/html/                merged HTML
 *   coverage/junit.xml            combined JUnit
 *
 * Uses sebastian/code-coverage directly (ships with PHPUnit) so no extra
 * Composer dependency (e.g. phpcov) has to be resolved on the PHP 7.4 floor.
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Clover;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlFacade;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Replays the <coverage> include/exclude rules from a phpunit.xml onto a
 * CodeCoverage object's filter, so the merged report can add uncovered files for
 * exactly the same source set the normal (single-process) coverage run would.
 * Paths in phpunit.xml are relative to the config file's directory.
 */
function applyCoverageFilter(CodeCoverage $coverage, string $configFile): void
{
    if (!is_file($configFile)) {
        fwrite(STDERR, "merge-coverage: config '$configFile' not found; uncovered files not added.\n");
        return;
    }

    $configDir = dirname($configFile);
    $xml = simplexml_load_file($configFile);
    if ($xml === false || !isset($xml->coverage)) {
        fwrite(STDERR, "merge-coverage: no <coverage> section in '$configFile'.\n");
        return;
    }

    $filter = $coverage->filter();
    $resolve = static function (string $relative) use ($configDir): ?string {
        $path = realpath($configDir . '/' . $relative);
        return $path === false ? null : $path;
    };

    // Includes first, then excludes (mirrors PHPUnit's own ordering).
    foreach ($xml->coverage->include->directory ?? [] as $dir) {
        $suffix = (string) ($dir['suffix'] ?? '');
        $suffix = $suffix !== '' ? $suffix : '.php';
        if ($path = $resolve((string) $dir)) {
            $filter->includeDirectory($path, $suffix);
        }
    }
    foreach ($xml->coverage->include->file ?? [] as $file) {
        if ($path = $resolve((string) $file)) {
            $filter->includeFile($path);
        }
    }
    foreach ($xml->coverage->exclude->directory ?? [] as $dir) {
        $suffix = (string) ($dir['suffix'] ?? '');
        $suffix = $suffix !== '' ? $suffix : '.php';
        if ($path = $resolve((string) $dir)) {
            $filter->excludeDirectory($path, $suffix);
        }
    }
    foreach ($xml->coverage->exclude->file ?? [] as $file) {
        if ($path = $resolve((string) $file)) {
            $filter->excludeFile($path);
        }
    }
}

$root = dirname(__DIR__, 2);
$covDir = $root . '/coverage';

// --junit-only: the non-coverage CI legs (every PHP version except the single
// coverage leg) run the tests without pcov, so there are no .cov files to merge
// — they only need the combined JUnit for the "Parse Failed Tests" gate. Skip
// all coverage work and just concatenate the two JUnit logs.
if (in_array('--junit-only', $argv, true)) {
    mergeJUnit(
        [$covDir . '/_junit_parallel.xml', $covDir . '/_junit_serial.xml'],
        $covDir . '/junit.xml'
    );
    echo "merge-coverage: junit-only merge — wrote coverage/junit.xml\n";
    exit(0);
}

$covFiles = [
    $covDir . '/_parallel.cov',
    $covDir . '/_serial.cov',
];

/** @var CodeCoverage|null $merged */
$merged = null;
$loaded = 0;
foreach ($covFiles as $covFile) {
    if (!is_file($covFile)) {
        fwrite(STDERR, "merge-coverage: skipping missing '$covFile'.\n");
        continue;
    }
    /** @var CodeCoverage $coverage */
    $coverage = require $covFile;
    if (!$coverage instanceof CodeCoverage) {
        fwrite(STDERR, "merge-coverage: '$covFile' did not return a CodeCoverage object.\n");
        continue;
    }
    if ($merged === null) {
        $merged = $coverage;
    } else {
        $merged->merge($coverage);
    }
    $loaded++;
}

if ($merged === null) {
    fwrite(STDERR, "merge-coverage: FATAL — no coverage data files could be loaded.\n");
    exit(1);
}

// Add uncovered files EXACTLY ONCE, here, instead of in every parallel worker.
// The per-worker runs use a coverage config with includeUncoveredFiles=false and
// processUncoveredFiles=false, so their .cov files hold only executed code (no
// per-worker whole-source rescan). We now rebuild the coverage filter from
// tests/phpunit.xml and enable the LIGHT uncovered-files path
// (includeUncoveredFiles -> addUncoveredFilesFromFilter, which uses static
// analysis, not the xdebug driver). This keeps the report denominator identical
// (all filtered source files counted) without paying the rescan N times.
applyCoverageFilter($merged, $root . '/tests/phpunit.xml');
$merged->includeUncoveredFiles();
$merged->doNotProcessUncoveredFiles();

// Clover (the file the coverage-threshold gate reads).
(new Clover())->process($merged, $covDir . '/coverage.xml');

// HTML (for humans).
(new HtmlFacade())->process($merged, $covDir . '/html');

// Combine the two JUnit logs into one <testsuites> document.
mergeJUnit(
    [$covDir . '/_junit_parallel.xml', $covDir . '/_junit_serial.xml'],
    $covDir . '/junit.xml'
);

$pct = round($merged->getReport()->percentageOfExecutedLines()->asFloat(), 2);
echo "merge-coverage: merged $loaded coverage file(s). Line coverage: {$pct}%\n";
echo "merge-coverage: wrote coverage/coverage.xml, coverage/html/, coverage/junit.xml\n";
exit(0);

/**
 * Concatenates the <testsuite> children of several JUnit files under a single
 * <testsuites> root.
 *
 * @param string[] $inputs
 */
function mergeJUnit(array $inputs, string $output): void
{
    $out = new DOMDocument('1.0', 'UTF-8');
    $out->formatOutput = true;
    $root = $out->createElement('testsuites');
    $out->appendChild($root);

    foreach ($inputs as $input) {
        if (!is_file($input)) {
            continue;
        }
        $in = new DOMDocument();
        if (!@$in->load($input)) {
            continue;
        }
        foreach ($in->getElementsByTagName('testsuite') as $suite) {
            // Only lift top-level <testsuite> nodes (direct children of the root).
            if ($suite->parentNode !== null && $suite->parentNode->nodeName === 'testsuites') {
                $root->appendChild($out->importNode($suite, true));
            }
        }
    }

    $out->save($output);
}
