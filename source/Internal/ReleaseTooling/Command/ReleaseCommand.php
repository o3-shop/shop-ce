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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Command;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\HttpsRawComposerJsonFetcher;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\HttpsRawRepoFileFetcher;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint\ConstraintUpdater;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ComposerJsonConstraintWriter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\BranchGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\ComposerInstallGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\IncomingPrGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\MergeBackPrGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\TestSuiteGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\UpToDateGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\WorkingTreeGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\LiveExecutor;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PerRepoActions;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightRunner;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\RepoCloneUrlResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\RepoPathDiscovery;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\SymfonyProcessExecutor;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ThemeFileVersionWriter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph\DepTreeWalker;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\GhCliReleaseNotesProvider;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\ReleaseNotesAggregator;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DefaultBranchResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DryRunPrinter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlanner;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshotBuilder;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\CandidateVersionResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\GitLsRemoteRepoIntrospector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Drives a tier-by-tier release across the o3-shop repo network.
 *
 * §11 wired the dry-run path; §15 wires live execution via
 * `LiveExecutor` (commit constraint changes, cut tags, create
 * draft releases, optionally open merge-back PRs).
 *
 * Pre-flight gates fire whenever any `--repo-path` is supplied. With
 * no paths, pre-flight is skipped and dry-run output is the same as
 * before.
 *
 * See: openspec/changes/automate-release-procedure/specs/release-orchestration/spec.md
 */
class ReleaseCommand extends Command
{
    /**
     * Bump-level pattern accepted in --bump <repo>=<level>:
     *   patch | minor | major | v<semver> (e.g. v2.0.0, v1.6.1-RC1)
     */
    public const BUMP_LEVEL_PATTERN
        = '/^(patch|minor|major|v\d+\.\d+\.\d+(-[A-Za-z0-9.-]+)?)$/';

    /**
     * Repo slug pattern (left side of --bump <slug>=<level>).
     * Matches lower-case alphanumerics, hyphens, dots — no slashes
     * (the o3-shop/ prefix is implied).
     */
    public const BUMP_REPO_PATTERN = '/^[a-z0-9][a-z0-9.-]*$/';

    public const EXIT_OK = 0;
    public const EXIT_USAGE_ERROR = 2;
    public const EXIT_PRE_FLIGHT_ABORT = 3;
    public const EXIT_PLAN_ERROR = 4;

    /** @var string|null */
    protected static $defaultName = 'release';

    private ?ReleasePlanner $planner;
    private DryRunPrinter $printer;
    private ?LiveExecutor $liveExecutor;

    /**
     * All three arguments are optional so production invocations build
     * defaults inline; tests inject fakes via the constructor.
     */
    public function __construct(
        ?ReleasePlanner $planner = null,
        ?DryRunPrinter $printer = null,
        ?LiveExecutor $liveExecutor = null
    ) {
        parent::__construct();
        $this->planner = $planner;
        $this->printer = $printer ?? new DryRunPrinter();
        $this->liveExecutor = $liveExecutor;
    }

    protected function configure(): void
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription(
                'Drive a tier-by-tier release across the o3-shop repo network.'
            )
            ->setHelp(
                "Mandatory: --from <previous-shop-tag> --to <shop-version-to-cut>.\n"
                . "Both anchor the per-repo \"did anything change?\" check and the\n"
                . "cross-repo release-notes generation.\n\n"
                . "Override per-repo bump levels via repeatable --bump <repo>=<level>\n"
                . "where <level> is patch | minor | major | v<exact-semver>.\n\n"
                . "--dry-run prints the plan without performing any state-changing\n"
                . 'action (no commits, no tags, no GitHub releases).'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Previous shop release tag (e.g. v1.6.0). Required.'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'Shop version to cut (e.g. v1.6.1-RC1). Required.'
            )
            ->addOption(
                'bump',
                'b',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Override per-repo bump level. Format: <repo>=<level>; '
                . 'level is patch | minor | major | v<semver>. Repeatable.'
            )
            ->addOption(
                'repo-base',
                null,
                InputOption::VALUE_REQUIRED,
                'Base directory containing local clones of every '
                . 'release-eligible repo. Defaults to the parent of '
                . 'the running shop-ce checkout (the conventional '
                . 'sibling layout). Existing clones are reused; '
                . 'missing ones are auto-cloned into <base>/<repo>.'
            )
            ->addOption(
                'repo-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Override discovery for a specific package. '
                . 'Format: <package>=/abs/path. Repeatable. Useful '
                . 'when a clone lives outside the conventional layout.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Print the plan without performing any state-changing action.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $input->getOption('from');
        $to = $input->getOption('to');
        $bumps = $input->getOption('bump') ?: [];
        $dryRun = (bool) $input->getOption('dry-run');

        if (!is_string($from) || $from === '') {
            $this->writeUsageError($output, '--from is required.');
            return self::EXIT_USAGE_ERROR;
        }
        if (!is_string($to) || $to === '') {
            $this->writeUsageError($output, '--to is required.');
            return self::EXIT_USAGE_ERROR;
        }

        $bumpFlags = [];
        foreach ($bumps as $bump) {
            $error = $this->validateBumpValue($bump);
            if ($error !== null) {
                $this->writeUsageError($output, $error);
                return self::EXIT_USAGE_ERROR;
            }
            [$slug, $level] = explode('=', $bump, 2);
            $bumpFlags[$slug] = $level;
        }

        $explicitPaths = [];
        foreach ($input->getOption('repo-path') ?: [] as $arg) {
            $error = $this->parseRepoPath($arg, $explicitPaths);
            if ($error !== null) {
                $this->writeUsageError($output, $error);
                return self::EXIT_USAGE_ERROR;
            }
        }

        $repoBaseOpt = $input->getOption('repo-base');
        $progress = static function (string $message) use ($output): void {
            $output->writeln('<comment>' . $message . '</comment>');
        };

        try {
            $repoPaths = $this->resolveRepoPaths(
                $explicitPaths,
                is_string($repoBaseOpt) ? $repoBaseOpt : null,
                $dryRun,
                $progress
            );
        } catch (Throwable $e) {
            $this->writeUsageError($output, $e->getMessage());
            return self::EXIT_USAGE_ERROR;
        }

        $planner = $this->planner ?? $this->buildDefaultPlanner($progress, $repoPaths !== []);

        $output->writeln(sprintf(
            '<info>Planning release: --from %s --to %s%s</info>',
            $from,
            $to,
            $dryRun ? ' (dry-run)' : ''
        ));
        try {
            $plan = $planner->plan($from, $to, $bumpFlags, $repoPaths);
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>Plan failed: %s</error>', $e->getMessage()));
            return self::EXIT_PLAN_ERROR;
        }
        $output->writeln('');

        $this->printer->print($plan, $output);

        if ($plan->shouldAbort()) {
            $output->writeln(
                '<error>Pre-flight gates aborted the release. '
                . 'Resolve the issues above and re-run.</error>'
            );
            return self::EXIT_PRE_FLIGHT_ABORT;
        }

        if ($dryRun) {
            $output->writeln('<info>Dry-run complete. No state-changing actions performed.</info>');
            return self::EXIT_OK;
        }

        $executor = $this->liveExecutor ?? $this->buildDefaultLiveExecutor($progress);
        try {
            $executor->execute($plan, $repoPaths);
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>Live execution failed: %s</error>', $e->getMessage()));
            $this->printPartialState($executor, $output);
            return self::EXIT_PLAN_ERROR;
        }
        $this->printPartialState($executor, $output);
        $output->writeln('<info>Live execution complete. Publish the draft GitHub releases manually.</info>');
        return self::EXIT_OK;
    }

    /**
     * Returns null when the value parses as <repo>=<level> with a valid
     * level; an error message otherwise.
     */
    public function validateBumpValue(string $value): ?string
    {
        $eq = strpos($value, '=');
        if ($eq === false || $eq === 0 || $eq === strlen($value) - 1) {
            return sprintf(
                'Malformed --bump value %s. Expected <repo>=<level> where '
                . 'level is patch | minor | major | v<semver>.',
                self::quote($value)
            );
        }
        $repo = substr($value, 0, $eq);
        $level = substr($value, $eq + 1);

        if (!preg_match(self::BUMP_REPO_PATTERN, $repo)) {
            return sprintf(
                'Malformed --bump repo slug %s. Expected lowercase '
                . 'alphanumerics, hyphens, dots; no slashes.',
                self::quote($repo)
            );
        }
        if (!preg_match(self::BUMP_LEVEL_PATTERN, $level)) {
            return sprintf(
                'Malformed --bump level %s. Expected patch | minor | major | '
                . 'v<semver>.',
                self::quote($level)
            );
        }
        return null;
    }

    private function buildDefaultPlanner(
        ?callable $progress = null,
        bool $withPreFlight = false
    ): ReleasePlanner {
        $exec = new SymfonyProcessExecutor();
        $composerJsonFetcher = new HttpsRawComposerJsonFetcher();
        $fileFetcher = new HttpsRawRepoFileFetcher();
        $branchResolver = new DefaultBranchResolver();

        $snapshotBuilder = new FromSnapshotBuilder($composerJsonFetcher, $progress);
        $walker = new DepTreeWalker($composerJsonFetcher, $branchResolver, $progress);
        $repos = new GitLsRemoteRepoIntrospector($exec);
        $versionResolver = new CandidateVersionResolver($repos);
        $tagCutter = new TagCutter($fileFetcher);
        $constraintUpdater = new ConstraintUpdater();
        $notesAggregator = new ReleaseNotesAggregator(new GhCliReleaseNotesProvider(), $progress);

        $preFlight = $withPreFlight ? $this->buildDefaultPreFlightRunner($exec) : null;

        return new ReleasePlanner(
            $snapshotBuilder,
            $walker,
            $versionResolver,
            $tagCutter,
            $constraintUpdater,
            $notesAggregator,
            $branchResolver,
            $preFlight,
            $progress
        );
    }

    private function buildDefaultPreFlightRunner(SymfonyProcessExecutor $exec): PreFlightRunner
    {
        // No per-repo test command resolver yet; TestSuiteGate is
        // registered with a resolver that always returns null (= skip
        // the gate without warning) so the default flow doesn't block
        // on tests when the maintainer has run them out-of-band.
        $skipTestsResolver = static function (string $_packageName, string $_repoPath): ?array {
            return null;
        };
        return new PreFlightRunner([
            new WorkingTreeGate($exec),
            new BranchGate($exec),
            new UpToDateGate($exec),
            new ComposerInstallGate($exec, $this->resolveBundledComposer()),
            new TestSuiteGate($exec, $skipTestsResolver),
            new IncomingPrGate($exec),
            new MergeBackPrGate($exec),
        ]);
    }

    /**
     * Returns the path to shop-ce's bundled composer
     * (`vendor/bin/composer`, composer 2.2.x via the transitive
     * `o3-shop/shop-composer-plugin` dep) when present, otherwise
     * falls back to `composer` from PATH. Using the bundled binary
     * makes the pre-flight gate's composer behavior consistent
     * across maintainer machines and identical to production
     * `o3-shop` installs.
     */
    private function resolveBundledComposer(): string
    {
        // From source/Internal/ReleaseTooling/Command/ → shop-ce root.
        $candidate = realpath(__DIR__ . '/../../../../vendor/bin/composer');
        if ($candidate !== false && is_executable($candidate)) {
            return $candidate;
        }
        return 'composer';
    }

    private function buildDefaultLiveExecutor(?callable $progress = null): LiveExecutor
    {
        $exec = new SymfonyProcessExecutor();
        return new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver(),
            new ThemeFileVersionWriter(),
            $progress
        );
    }

    /**
     * Returns the per-package map of local clone paths.
     *
     * - When explicit `--repo-path` values are supplied, they are honored
     *   verbatim; no auto-discovery happens (this is the "I have a
     *   non-conventional layout" escape hatch).
     * - In dry-run mode without explicit paths, the result is an empty
     *   array — pre-flight skipped, current behavior preserved.
     * - In live mode without explicit paths, `RepoPathDiscovery` runs:
     *   shop-ce maps to the running clone; sibling repos map to
     *   `<repo-base>/<github-repo-name>` (existing) or get auto-cloned
     *   into that path (missing).
     *
     * @param  array<string,string> $explicitPaths package => abs path from --repo-path
     * @return array<string,string>                package => abs path (final)
     */
    private function resolveRepoPaths(
        array $explicitPaths,
        ?string $repoBaseOpt,
        bool $dryRun,
        callable $progress
    ): array {
        if ($explicitPaths !== []) {
            return $explicitPaths;
        }
        if ($dryRun) {
            // Dry-run with no explicit paths = skip pre-flight, no
            // discovery needed. Preserves the cheap remote-only
            // preview path.
            return [];
        }
        $shopCePath = realpath(__DIR__ . '/../../../..');
        if ($shopCePath === false) {
            throw new \RuntimeException(
                'could not resolve running shop-ce path '
                . '(realpath of bin/release origin returned false)'
            );
        }
        $baseDir = $repoBaseOpt !== null
            ? $repoBaseOpt
            : dirname($shopCePath);

        $exec = new SymfonyProcessExecutor();
        $urlResolver = RepoCloneUrlResolver::fromRepoOrigin($exec, $shopCePath);
        $discovery = new RepoPathDiscovery(
            $exec,
            $urlResolver,
            new DefaultBranchResolver(),
            $progress
        );
        $progress(sprintf(
            'Discovering release-eligible repos under %s (clone scheme: %s)',
            $baseDir,
            $urlResolver->scheme()
        ));
        return $discovery->discoverAll($baseDir, $shopCePath);
    }

    /**
     * @param array<string,string> &$repoPaths package => abs path; populated on success
     */
    private function parseRepoPath(string $arg, array &$repoPaths): ?string
    {
        $eq = strpos($arg, '=');
        if ($eq === false || $eq === 0 || $eq === strlen($arg) - 1) {
            return sprintf(
                'Malformed --repo-path %s. Expected <package>=/abs/path '
                . '(e.g. o3-shop/shop-ce=/Users/me/clones/shop-ce).',
                self::quote($arg)
            );
        }
        $package = substr($arg, 0, $eq);
        $path = substr($arg, $eq + 1);
        if ($package === '' || strpos($package, '/') === false) {
            return sprintf(
                'Malformed --repo-path package %s. Expected <vendor>/<repo> '
                . '(e.g. o3-shop/shop-ce).',
                self::quote($package)
            );
        }
        if ($path === '' || $path[0] !== '/') {
            return sprintf(
                'Malformed --repo-path absolute path %s. Use an absolute path '
                . 'so the orchestrator does not depend on shell cwd.',
                self::quote($path)
            );
        }
        if (!is_dir($path)) {
            return sprintf('--repo-path %s is not a directory.', self::quote($path));
        }
        if (!is_dir($path . '/.git')) {
            return sprintf(
                '--repo-path %s does not look like a git working tree '
                . '(no .git/ found).',
                self::quote($path)
            );
        }
        $repoPaths[$package] = $path;
        return null;
    }

    private function printPartialState(LiveExecutor $executor, OutputInterface $output): void
    {
        $releases = $executor->releaseUrls();
        if ($releases !== []) {
            $output->writeln('');
            $output->writeln('<info>Draft GitHub releases created:</info>');
            foreach ($releases as $package => $url) {
                $output->writeln(sprintf('  %s -> %s', $package, $url));
            }
        }
        $mergeBacks = $executor->mergeBackUrls();
        if ($mergeBacks !== []) {
            $output->writeln('');
            $output->writeln('<info>Merge-back PRs opened:</info>');
            foreach ($mergeBacks as $package => $url) {
                $output->writeln(sprintf('  %s -> %s', $package, $url));
            }
        }
    }

    private function writeUsageError(OutputInterface $output, string $message): void
    {
        $output->writeln('<error>' . $message . '</error>');
        $output->writeln(
            'Usage: bin/release --from <tag> --to <tag> '
            . '[--bump <repo>=<level> ...] [--dry-run]'
        );
    }

    private static function quote(string $value): string
    {
        return "'" . $value . "'";
    }
}
