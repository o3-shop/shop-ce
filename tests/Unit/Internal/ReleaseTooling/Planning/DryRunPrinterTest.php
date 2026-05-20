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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Planning;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint\ConstraintUpdate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightReport;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\CandidatePlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ConstraintEditPlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DryRunPrinter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshot;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutResult;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\VersionResolution;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class DryRunPrinterTest extends TestCase
{
    public function testEmptyPlanRendersHeaderAndSkippedSentinels(): void
    {
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($this->emptyPlan(), $output);
        $rendered = $output->fetch();

        $this->assertStringContainsString('Release plan: --from v1.6.0 --to v1.6.1-RC1', $rendered);
        $this->assertStringContainsString('(no candidates discovered)', $rendered);
        $this->assertStringContainsString('every existing constraint already satisfies', $rendered);
        $this->assertStringContainsString('all candidates unchanged or aggregator empty', $rendered);
        $this->assertStringContainsString('Pre-flight gates: skipped', $rendered);
    }

    public function testPreFoldInIndirectionLineRenderedWhenSnapshotUsedIndirection(): void
    {
        $plan = new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], true, 'v1.6.0'),
            [],
            [],
            '',
            []
        );
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($plan, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('pre-fold-in --from detected', $rendered);
        $this->assertStringContainsString('shop-metapackage-ce@v1.6.0', $rendered);
    }

    public function testBackEdgesSectionPrintedWhenPresent(): void
    {
        $plan = new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
            [],
            [],
            '',
            [],
            [
                ['from' => 'o3-shop/testing-library', 'to' => 'o3-shop/shop-ce'],
                ['from' => 'o3-shop/shop-composer-plugin', 'to' => 'o3-shop/shop-ce'],
            ]
        );
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($plan, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('Back-edges (informational', $rendered);
        $this->assertStringContainsString('o3-shop/testing-library -> o3-shop/shop-ce', $rendered);
        $this->assertStringContainsString('o3-shop/shop-composer-plugin -> o3-shop/shop-ce', $rendered);
    }

    public function testCandidatesSectionRendersTagCutNotesAndConsumedNextBump(): void
    {
        $candidate = new CandidatePlan(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.0.5',
            new VersionResolution(
                'o3-shop/shop-facts',
                VersionResolution::CASE_NEEDS_NEW_TAG,
                null,
                ['commits exist on b-1.6 beyond v1.0.4'],
                'v1.0.4'
            ),
            new TagCutResult('v1.0.5', true, TagCutResult::SOURCE_NEXT_BUMP_FILE, ['malformed .next-bump ignored'])
        );
        $plan = new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
            [$candidate],
            [],
            '',
            []
        );
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($plan, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('o3-shop/shop-facts', $rendered);
        $this->assertStringContainsString('v1.0.4 -> v1.0.5', $rendered);
        $this->assertStringContainsString('cut via next-bump-file', $rendered);
        $this->assertStringContainsString('.next-bump consumed', $rendered);
        $this->assertStringContainsString('• commits exist on b-1.6 beyond v1.0.4', $rendered);
        $this->assertStringContainsString('⚠ malformed .next-bump ignored', $rendered);
    }

    public function testNewCandidateMarkedWithNewSentinelWhenFromPinEmpty(): void
    {
        $candidate = new CandidatePlan(
            'o3-shop/brand-new-dep',
            '',
            'v1.0.0',
            new VersionResolution('o3-shop/brand-new-dep', VersionResolution::CASE_USABLE_TAG, 'v1.0.0', []),
            null
        );
        $plan = new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
            [$candidate],
            [],
            '',
            []
        );
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($plan, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('o3-shop/brand-new-dep', $rendered);
        $this->assertStringContainsString('(new) -> v1.0.0', $rendered);
    }

    public function testConstraintEditsRenderedWithShape(): void
    {
        $plan = new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
            [],
            [
                new ConstraintEditPlan(
                    'o3-shop/o3-shop',
                    'require',
                    'o3-shop/shop-ce',
                    new ConstraintUpdate('v1.6.0', 'v1.6.1-RC1', ConstraintUpdate::SHAPE_EXACT_REPLACED)
                ),
            ],
            '',
            []
        );
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($plan, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('o3-shop/o3-shop/composer.json [require]: o3-shop/shop-ce', $rendered);
        $this->assertStringContainsString('"v1.6.0" -> "v1.6.1-RC1"', $rendered);
        $this->assertStringContainsString('exact-replaced', $rendered);
    }

    public function testAggregatedNotesIndentedUnderHeading(): void
    {
        $plan = new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
            [],
            [],
            "## o3-shop/shop-ce\n\nWhat's Changed\n* foo by @bar\n",
            []
        );
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($plan, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('  ## o3-shop/shop-ce', $rendered);
        $this->assertStringContainsString('  * foo by @bar', $rendered);
    }

    public function testPreFlightReportRendersOkWarnAndAbortVerdicts(): void
    {
        $okReport = new PreFlightReport([
            GateOutcome::passed('clean-working-tree'),
        ]);
        $warnReport = new PreFlightReport([
            GateOutcome::warning('incoming-prs', ['1 incoming PR — proceeding']),
        ]);
        $abortReport = new PreFlightReport([
            GateOutcome::abort('on-release-branch', ['expected b-1.6, got feature-x']),
        ]);

        $plan = new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
            [],
            [],
            '',
            [
                'o3-shop/shop-ce' => $okReport,
                'o3-shop/testing-library' => $warnReport,
                'o3-shop/shop-facts' => $abortReport,
            ]
        );
        $printer = new DryRunPrinter();
        $output = new BufferedOutput();
        $printer->print($plan, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('OK', $rendered);
        $this->assertStringContainsString('WARN', $rendered);
        $this->assertStringContainsString('ABORT', $rendered);
        $this->assertStringContainsString('1 incoming PR — proceeding', $rendered);
        $this->assertStringContainsString('expected b-1.6, got feature-x', $rendered);
        $this->assertStringContainsString('Plan would abort', $rendered);
    }

    private function emptyPlan(): ReleasePlan
    {
        return new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
            [],
            [],
            '',
            []
        );
    }
}
