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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Renders a `ReleasePlan` as readable text. Used by `bin/release
 * --dry-run` and (in the future) any preview command. Produces
 * deterministic output so the plan can be diffed across runs.
 */
class DryRunPrinter
{
    public function print(ReleasePlan $plan, OutputInterface $output): void
    {
        $output->writeln(sprintf(
            '<info>Release plan: --from %s --to %s</info>',
            $plan->fromTag(),
            $plan->toTag()
        ));

        $snapshot = $plan->fromSnapshot();
        if ($snapshot->usedPreFoldInIndirection()) {
            $output->writeln(sprintf(
                '<comment>Step 1: pre-fold-in --from detected; '
                . 'harvested tier-0 pins from o3-shop/shop-metapackage-ce@%s</comment>',
                (string) $snapshot->preFoldInMetapackageVersion()
            ));
        }
        $output->writeln('');

        $this->printBackEdges($plan, $output);
        $this->printCandidates($plan, $output);
        $this->printConstraintEdits($plan, $output);
        $this->printAggregatedNotes($plan, $output);
        $this->printPreFlight($plan, $output);
    }

    private function printBackEdges(ReleasePlan $plan, OutputInterface $output): void
    {
        $backEdges = $plan->backEdges();
        if ($backEdges === []) {
            return;
        }
        $output->writeln('<comment>Back-edges (informational; treated as peer-constraint pins, not ordering deps):</comment>');
        foreach ($backEdges as $edge) {
            $output->writeln(sprintf('  %s -> %s', $edge['from'], $edge['to']));
        }
        $output->writeln('');
    }

    private function printCandidates(ReleasePlan $plan, OutputInterface $output): void
    {
        $output->writeln('<info>Per-repo plan:</info>');
        if ($plan->candidates() === []) {
            $output->writeln('  (no candidates discovered)');
            return;
        }
        foreach ($plan->candidates() as $candidate) {
            $output->writeln(sprintf(
                '  %s  [%s]  %s -> %s%s',
                $candidate->package(),
                $candidate->caseLabel(),
                $candidate->fromPin() === '' ? '(new)' : $candidate->fromPin(),
                $candidate->chosenVersion(),
                $candidate->tagCut() !== null
                    ? sprintf(
                        '  (cut via %s%s)',
                        $candidate->tagCut()->source(),
                        $candidate->tagCut()->deleteNextBumpFile() ? '; .next-bump consumed' : ''
                    )
                    : ''
            ));
            foreach ($candidate->resolution()->notes() as $note) {
                $output->writeln('      • ' . $note);
            }
            if ($candidate->tagCut() !== null) {
                foreach ($candidate->tagCut()->notes() as $note) {
                    $output->writeln('      ⚠ ' . $note);
                }
            }
        }
        $output->writeln('');
    }

    private function printConstraintEdits(ReleasePlan $plan, OutputInterface $output): void
    {
        $output->writeln('<info>Planned constraint edits:</info>');
        if ($plan->constraintEdits() === []) {
            $output->writeln('  (none — every existing constraint already satisfies the chosen version)');
            $output->writeln('');
            return;
        }
        foreach ($plan->constraintEdits() as $edit) {
            $output->writeln(sprintf(
                '  %s/composer.json [%s]: %s "%s" -> "%s"  (%s)',
                $edit->parentPackage(),
                $edit->key(),
                $edit->depPackage(),
                $edit->update()->oldConstraint(),
                $edit->update()->newConstraint(),
                $edit->update()->shape()
            ));
        }
        $output->writeln('');
    }

    private function printAggregatedNotes(ReleasePlan $plan, OutputInterface $output): void
    {
        $output->writeln('<info>Aggregated o3-shop release notes (would be attached to the draft release):</info>');
        $notes = $plan->aggregatedNotes();
        if ($notes === '') {
            $output->writeln('  (no notes; all candidates unchanged or aggregator empty)');
        } else {
            foreach (explode("\n", rtrim($notes, "\n")) as $line) {
                $output->writeln('  ' . $line);
            }
        }
        $output->writeln('');
    }

    private function printPreFlight(ReleasePlan $plan, OutputInterface $output): void
    {
        $reports = $plan->preFlightReports();
        if ($reports === []) {
            $output->writeln('<comment>Pre-flight gates: skipped (no local repo paths supplied).</comment>');
            return;
        }
        $output->writeln('<info>Pre-flight gates:</info>');
        foreach ($reports as $package => $report) {
            $verdict = $report->shouldAbort()
                ? '<error>ABORT</error>'
                : ($report->hasWarnings() ? '<comment>WARN</comment>' : '<info>OK</info>');
            $output->writeln(sprintf('  %s  %s', $package, $verdict));
            foreach ($report->allMessages() as $line) {
                $output->writeln('    ' . $line);
            }
        }
        $output->writeln('');
        if ($plan->shouldAbort()) {
            $output->writeln('<error>Plan would abort: pre-flight gates failed for one or more repos.</error>');
        }
    }
}
