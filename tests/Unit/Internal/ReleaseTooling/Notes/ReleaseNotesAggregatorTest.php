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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Notes;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\CandidateState;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\ReleaseNotesAggregator;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\ReleaseNotesProvider;
use PHPUnit\Framework\TestCase;

class ReleaseNotesAggregatorTest extends TestCase
{
    public function testSingleChangedRepoEmitsOneH2Section(): void
    {
        $provider = new RecordingProvider([
            'o3-shop/shop-ce|v1.6.0|v1.6.1-RC1' => "## What's Changed\n* feat: foo\n",
        ]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $body = $aggregator->aggregate([
            new CandidateState('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
        ]);

        $this->assertStringContainsString('## o3-shop/shop-ce', $body);
        $this->assertStringContainsString("## What's Changed", $body);
        $this->assertStringContainsString('* feat: foo', $body);
        $this->assertStringNotContainsString(ReleaseNotesAggregator::UNCHANGED_HEADING, $body);
    }

    public function testCallShapePassesFromPinAndChosenAsTagArgs(): void
    {
        $provider = new RecordingProvider([
            'o3-shop/shop-facts|v1.0.4|v1.0.5' => 'body-shop-facts',
        ]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $aggregator->aggregate([
            new CandidateState('o3-shop/shop-facts', 'v1.0.4', 'v1.0.5'),
        ]);

        $this->assertCount(1, $provider->calls);
        $this->assertSame('o3-shop/shop-facts', $provider->calls[0]['package']);
        $this->assertSame('v1.0.4', $provider->calls[0]['previousTag']);
        $this->assertSame('v1.0.5', $provider->calls[0]['newTag']);
    }

    public function testCaretFromPinIsNormalizedToTagBeforeProviderCall(): void
    {
        // GitHub's generate-notes API rejects caret/tilde constraints
        // with HTTP 400. The aggregator normalizes from_pin to the
        // canonical o3-shop tag form (v-prefixed) before passing it
        // along.
        $provider = new RecordingProvider([
            'o3-shop/shop-ide-helper|v1.0.0|v1.0.1' => 'normalized-body',
        ]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $body = $aggregator->aggregate([
            new CandidateState('o3-shop/shop-ide-helper', '^1.0.0', 'v1.0.1'),
        ]);

        $this->assertCount(1, $provider->calls);
        $this->assertSame('v1.0.0', $provider->calls[0]['previousTag']);
        $this->assertStringContainsString('normalized-body', $body);
    }

    public function testTildeFromPinIsAlsoNormalized(): void
    {
        $provider = new RecordingProvider([]);
        $aggregator = new ReleaseNotesAggregator($provider);
        $aggregator->aggregate([
            new CandidateState('o3-shop/smarty', '~2.6.34', 'v2.6.35'),
        ]);
        $this->assertSame('v2.6.34', $provider->calls[0]['previousTag']);
    }

    public function testUnresolvableFromPinIsPassedThroughUntouched(): void
    {
        // dev-master / wildcards / empty strings can't be normalized.
        // The aggregator passes them through as-is so the provider
        // can decide how to handle (typically: stub markdown on
        // GitHub error).
        $provider = new RecordingProvider([]);
        $aggregator = new ReleaseNotesAggregator($provider);
        $aggregator->aggregate([
            new CandidateState('o3-shop/some-pkg', 'dev-master', 'v1.0.0'),
        ]);
        $this->assertSame('dev-master', $provider->calls[0]['previousTag']);
    }

    public function testUnchangedRepoSkipsApiCallAndAppearsInSummary(): void
    {
        $provider = new RecordingProvider([]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $body = $aggregator->aggregate([
            new CandidateState('o3-shop/o3-theme', '^v1.3.0', '^v1.3.0'),
        ]);

        $this->assertSame([], $provider->calls);
        $this->assertStringContainsString(ReleaseNotesAggregator::UNCHANGED_HEADING, $body);
        $this->assertStringContainsString('`o3-shop/o3-theme` continues at `^v1.3.0`', $body);
    }

    public function testMultiRepoMixOfChangedAndUnchanged(): void
    {
        $provider = new RecordingProvider([
            'o3-shop/shop-ce|v1.6.0|v1.6.1-RC1' => "## What's Changed\n* shop-ce change\n",
            'o3-shop/testing-library|v1.2.0|v1.2.6' => "## What's Changed\n* testing-library change\n",
        ]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $body = $aggregator->aggregate([
            new CandidateState('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
            new CandidateState('o3-shop/testing-library', '^1.2.0', 'v1.2.6'),
            new CandidateState('o3-shop/o3-theme', '^v1.3.0', '^v1.3.0'),
            new CandidateState('o3-shop/wave-theme', '^v1.2.0', '^v1.2.0'),
            new CandidateState('o3-shop/shop-facts', 'v1.0.4', 'v1.0.4'),
        ]);

        // Two changed sections present
        $this->assertStringContainsString('## o3-shop/shop-ce', $body);
        $this->assertStringContainsString('## o3-shop/testing-library', $body);
        $this->assertStringContainsString('* shop-ce change', $body);
        $this->assertStringContainsString('* testing-library change', $body);

        // Unchanged section present with all three
        $this->assertStringContainsString(ReleaseNotesAggregator::UNCHANGED_HEADING, $body);
        $this->assertStringContainsString('`o3-shop/o3-theme` continues at `^v1.3.0`', $body);
        $this->assertStringContainsString('`o3-shop/wave-theme` continues at `^v1.2.0`', $body);
        $this->assertStringContainsString('`o3-shop/shop-facts` continues at `v1.0.4`', $body);

        // Provider called once per CHANGED repo, never for unchanged
        $this->assertCount(2, $provider->calls);
    }

    public function testChangedSectionsPrecedeUnchangedSection(): void
    {
        $provider = new RecordingProvider([
            'o3-shop/shop-ce|v1.6.0|v1.6.1-RC1' => 'changed-body',
        ]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $body = $aggregator->aggregate([
            new CandidateState('o3-shop/o3-theme', '^v1.3.0', '^v1.3.0'),
            new CandidateState('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
        ]);

        $changedPos = strpos($body, '## o3-shop/shop-ce');
        $unchangedPos = strpos($body, ReleaseNotesAggregator::UNCHANGED_HEADING);
        $this->assertNotFalse($changedPos);
        $this->assertNotFalse($unchangedPos);
        $this->assertLessThan($unchangedPos, $changedPos);
    }

    public function testAllUnchangedYieldsOnlyTheSummarySection(): void
    {
        $provider = new RecordingProvider([]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $body = $aggregator->aggregate([
            new CandidateState('o3-shop/shop-ce', 'v1.6.0', 'v1.6.0'),
            new CandidateState('o3-shop/testing-library', '^1.2.0', '^1.2.0'),
        ]);

        $this->assertStringContainsString(ReleaseNotesAggregator::UNCHANGED_HEADING, $body);
        $this->assertStringNotContainsString('## o3-shop/shop-ce', $body); // would be a heading line, not a list bullet
        $this->assertStringNotContainsString('## o3-shop/testing-library', $body);
    }

    public function testEmptyCandidateListYieldsEmptyString(): void
    {
        $provider = new RecordingProvider([]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $this->assertSame('', $aggregator->aggregate([]));
    }

    public function testProviderBodyIsTrimmedBeforeStitching(): void
    {
        $provider = new RecordingProvider([
            'o3-shop/shop-ce|v1.6.0|v1.6.1-RC1' => "\n\n   body-with-padding   \n\n",
        ]);
        $aggregator = new ReleaseNotesAggregator($provider);

        $body = $aggregator->aggregate([
            new CandidateState('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
        ]);
        // No leading or trailing whitespace inside the section
        $this->assertStringContainsString("## o3-shop/shop-ce\n\nbody-with-padding", $body);
    }

    public function testCandidateStateChangedDetection(): void
    {
        $changed = new CandidateState('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1');
        $this->assertTrue($changed->isChanged());

        $unchanged = new CandidateState('o3-shop/o3-theme', '^v1.3.0', '^v1.3.0');
        $this->assertFalse($unchanged->isChanged());
    }
}

/**
 * Test double: returns canned bodies keyed by "<package>|<previous>|<new>"
 * and records each call so the test can assert call shape.
 */
final class RecordingProvider implements ReleaseNotesProvider
{
    /** @var array<string,string> */
    private array $bodies;
    /** @var array<int,array{package:string,previousTag:string,newTag:string}> */
    public array $calls = [];

    /**
     * @param array<string,string> $bodies
     */
    public function __construct(array $bodies)
    {
        $this->bodies = $bodies;
    }

    public function notesFor(string $package, string $previousTag, string $newTag): string
    {
        $this->calls[] = [
            'package' => $package,
            'previousTag' => $previousTag,
            'newTag' => $newTag,
        ];
        $key = sprintf('%s|%s|%s', $package, $previousTag, $newTag);
        return $this->bodies[$key] ?? '';
    }
}
