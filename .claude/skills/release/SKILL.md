---
name: release
description: Cut an O3-Shop release or release candidate with bin/release. Derives --from/--to, runs the dry-run with pre-flight gates, summarises the plan, and stops for explicit approval before every git write or public step (clone fast-forwards, the live cut, publishing the drafts, merge-backs, the milestone, the docs.o3-shop.com PR). Use when the user says "/release vX.Y.Z", "cut a release", "cut RC<n>", or "publish the release".
---

# /release — cut an O3-Shop release

`bin/release` walks the whole o3-shop repo network. This skill runs it the way a
maintainer should: dry-run with gates, read the plan, and **stop for explicit
approval** before every git write and every public step.

The runbook this follows is *How to contribute → Steps 11–16* on
docs.o3-shop.com (`o3-documentation/source/Developer/HowToContribute.md`). Read
these memory entries before the first run in a session; they explain the traps
the steps below guard against:

- `.claude/memory/release-tooling-branch-selection.md`: branch selection, live
  mode without confirmation, bare dry-run skips the gates, `UpToDateGate`
  fast-forwards, merge-back PRs.
- `.claude/memory/release-tooling-intermediate-node-retag-gap.md`: why an
  intermediate package must be re-tagged when its own `composer.json` changes.
- `.claude/memory/cs-fixer-pollutes-nested-clones.md`: a cs-fixer run dirties
  the nested clones and aborts the gates.
- `.claude/memory/known-pitfalls.md`, sections *Packagist sync delay*, *Use
  shop-ce's bundled composer* and *CI's "Code Quality" workflow does not run the
  unit suite*.

## Approval gates

Ask each of these as an explicit question that names the actions, and wait for a
yes. Approval of one gate never covers the next, and approval from an earlier
session never counts.

| Gate | Before | Why |
|---|---|---|
| A | the gated dry-run | `UpToDateGate` runs `git merge --ff-only` in the listed clones |
| B | the live `bin/release` run | it pushes commits and tags with no prompt of its own |
| C | publishing the draft releases | public; Packagist picks the tags up |
| D | merging the merge-back PRs (final only) | writes to `main` |
| E | closing the milestone (final only) | public bookkeeping |
| F | the docs branch and commit (final only) | git writes |
| G | pushing the docs branch and opening the PR | outward-facing |

Never run `bin/release` without `--dry-run` before gate B.

## Names: package, slug, GitHub repo

`bin/release` uses three spellings:

- `--repo-path`, the plan and the printed draft and merge-back lists use the
  **composer package name**: `o3-shop/shop-facts`.
- `--bump` uses the **short slug**, without `o3-shop/` (slashes are rejected):
  `--bump shop-facts=minor`.
- `gh` and `git ls-remote` need the **GitHub repo**. Three differ from the
  package (`source/Internal/ReleaseTooling/Composer/PackageRepoSlug.php`):

| Package | GitHub repo |
|---|---|
| `o3-shop/o3-theme` | `o3-shop/o3-Theme` |
| `o3-shop/mink-selenium-driver` | `o3-shop/MinkSeleniumDriver` |
| `o3-shop/php-selenium` | `o3-shop/PHP-Selenium` |

For a draft or PR, take the GitHub repo from its URL.

## Step 1: preconditions and arguments

Run from the **main shop-ce checkout**, not a git worktree: `--repo-path`
requires a `.git` directory (in a worktree it is a file, and the run exits `2`),
and the sibling clones are found next to the main checkout.

Shell variables don't survive between Bash tool calls: re-set the ones a
command uses at the top of that call, or substitute the literal values.

```bash
SHOP_CE=$(git rev-parse --show-toplevel)        # the shop-ce checkout running bin/release
[ "$(git -C "$SHOP_CE" rev-parse --git-dir)" = .git ] || echo "STOP: worktree"
BASE=$(dirname "$SHOP_CE")                      # sibling clones live here
LOG_DIR=/abs/path/to/session/scratchpad         # never inside a repo
GH=https://github.com/o3-shop
TO=v1.7.3-RC2                                   # the version the user asked for
echo "$TO" | grep -Eq '^v[0-9]+\.[0-9]+\.[0-9]+(-RC[0-9]+)?$' || echo "STOP: unexpected --to"
BASE_VERSION=${TO%%-*}                          # v1.7.3
cd "$SHOP_CE"                                   # bin/release below is relative to it
```

1. **Refuse an existing `--to`.** Check the three repos tagged at `--to`; after
   a half-failed run, `shop-ce` or the metapackage can have it while
   `o3-shop` doesn't:
   ```bash
   for r in o3-shop shop-ce shop-metapackage-ce; do
     git ls-remote --tags --refs "$GH/$r.git" "refs/tags/$TO"
   done
   ```
   Any output → stop and suggest the next free RC (highest existing + 1):
   `git ls-remote --tags --refs "$GH/o3-shop.git" "refs/tags/$BASE_VERSION-RC*" | sed 's#.*-RC##' | sort -n | tail -1`.
   For an RC, also stop if the final `$BASE_VERSION` already exists.
2. **`--from` is the previous final shop tag**, also when cutting an RC. Never an
   RC:
   ```bash
   FROM=$( (git ls-remote --tags --refs "$GH/o3-shop.git" | sed 's#.*refs/tags/##' \
       | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | grep -vx "$BASE_VERSION"; \
       echo "$BASE_VERSION") | sort -V | grep -B1 -x "$BASE_VERSION" | head -1)
   ```
   `FROM` equal to `$BASE_VERSION` means there is no earlier final: stop and ask.
   Show `FROM` to the user if it isn't the tag they expected.
3. **Release line.** The line is `b-MAJOR.MINOR` of `--to`. It must equal the
   value for `o3-shop/shop-ce` in
   `source/Internal/ReleaseTooling/Planning/DefaultBranchResolver.php`; `--to`
   does not select branches, only that map does. If they differ, stop: either
   this is the first release of a new line (the branches must exist on the
   remote first) or a maintenance release on an older line. Both need the map
   changed in its own PR. Don't edit it here.
4. **shop-ce itself** is on that line, clean, and level with `origin`:
   `git -C "$SHOP_CE" rev-parse --abbrev-ref HEAD` prints the line,
   `git -C "$SHOP_CE" status --porcelain` is empty, and after `git fetch`,
   `git -C "$SHOP_CE" rev-list --left-right --count HEAD...@{u}` prints `0	0`.
5. **Milestone (Step 11).** Find the milestone of this release:
   `gh api repos/o3-shop/o3-shop/milestones --jq '.[] | "\(.number) \(.title) open=\(.open_issues)"'`.
   None found → tell the user and go on. For a **final** release, open issues
   left → list them and stop; the user resolves or moves them. For an RC, list
   them as information only.
6. **Tests.** `bin/release` never runs test suites (`TestSuiteGate` is wired to
   skip), and CI's *Code Quality* workflow doesn't run the unit suite either.
   Ask the user whether the full suite passed on the current head of the
   release line. If nobody knows, ask before running `./docker.sh test` (only
   with the containers already up: `./docker.sh start` on a fresh setup clones
   missing nested repos, `testing-library` at an old branch; not
   `test-all`/`test-all-coverage`: those run cs-fixer first, which may rewrite
   files and then makes `WorkingTreeGate` abort).
   Afterwards, `git status --porcelain` must be empty in shop-ce and every
   nested clone.

## Step 2: dry-run, first remote-only, then with the gates

A plain `--dry-run` resolves the plan from the remotes but **skips every
pre-flight gate** (`Pre-flight gates: skipped (no local repo paths supplied).`).
Gates run only for packages given with `--repo-path`. So run twice.

**2a. Remote-only plan**, to learn which packages are involved:

```bash
bin/release --from "$FROM" --to "$TO" --dry-run > "$LOG_DIR/release-plan.log" 2>&1; echo "REAL_EXIT=$?"
```

Exit codes: `0` OK, `2` usage error, `3` pre-flight abort, `4` plan error
(`Plan failed: …`, e.g. a 404 on a branch that doesn't exist yet) or live
failure.

**2b. Locate the local clones** of every package with a cut row
(`cut-new-tag …`) or a constraint edit, plus `o3-shop/o3-shop`,
`o3-shop/shop-ce` and `o3-shop/shop-metapackage-ce`. Clones are siblings in
`BASE` or nested inside shop-ce (`testing-library`, `shop-demodata-ce`,
`tinymce-editor`, the themes under `source/Application/views/`). Key each one by
the `name` in its `composer.json`, which is the package name:

```bash
for d in "$SHOP_CE"/*/ "$SHOP_CE"/source/Application/views/*/ "$BASE"/*/; do
  d=${d%/}
  if [ -d "$d/.git" ] && [ -f "$d/composer.json" ]; then
    echo "$(jq -r .name "$d/composer.json") $d"
  fi
done
```

A package found twice (e.g. `tinymce-editor` nested and as a sibling): use the
nested clone, as the tool's own discovery does, and run the checks in 2c on
both. A plan package not found by name (a fork may keep its upstream name, e.g.
the `paypal-module` clone declares `oxid-solution-catalysts/paypal-module`):
match it by `git -C <dir> remote get-url origin` against its GitHub repo
instead. A package with no clone at all: tell the user and ask them to clone it
into `BASE`. Don't clone it yourself.

**2c. Gated dry-run (gate A).** `UpToDateGate` runs
`git merge --ff-only origin/<release branch>` on whatever branch is checked out,
even in a dry-run. So for every clone, first check:

- `git -C <path> rev-parse --abbrev-ref HEAD` equals that package's branch in
  `DefaultBranchResolver` (a feature branch would be fast-forwarded to the
  release branch; a detached `HEAD` prints `HEAD`);
- `git -C <path> status --porcelain` is empty;
- after `git -C <path> fetch origin <branch>`,
  `git -C <path> rev-list --count origin/<branch>..HEAD` prints `0` (no
  unpushed commits; this also works for a branch without an upstream).

Any hit: stop and show it; the user decides. Clean: ask gate A, listing the
clones the run may fast-forward. On a yes:

```bash
bin/release --from "$FROM" --to "$TO" --dry-run \
  --repo-path o3-shop/o3-shop=/abs/path/o3-shop \
  --repo-path o3-shop/shop-ce="$SHOP_CE" \
  --repo-path o3-shop/shop-metapackage-ce=/abs/path/shop-metapackage-ce \
  [--repo-path <package>=<path> for every other cut or edited package] \
  > "$LOG_DIR/release-gates.log" 2>&1; echo "REAL_EXIT=$?"
```

Never pipe it into `tail`: that reports `tail`'s exit code. Read the log, then
confirm the last line is `Dry-run complete.` or the abort message.

**Coverage check.** The planner silently skips the gates of a package without a
path, and the live run then fails at that package after earlier ones are
already tagged. Every package with a cut row, plus `o3-shop/o3-shop`, must have
its own entry under `Pre-flight gates:`. One missing → fix the `--repo-path`
list and repeat 2c. Never ask for gate B while one is missing.

## Step 3: summarise the plan

Give the user, from the gated log:

1. **Per-repo table** from the `Per-repo plan:` block: package, case
   (`cut-new-tag`, `cut-new-tag (downstream changed)`, `reuse-latest-tag`,
   `unchanged`), from-pin → chosen version, and the cut source
   (`shop-version-verbatim`, `flag`, `next-bump-file`, `default-patch`).
   Include the `•` and `⚠` notes.
2. **Constraint edits** from `Planned constraint edits:`.
3. **Gates**: each package's `OK` / `WARN` / `ABORT` with its messages, and the
   fix for each abort (commit or stash, switch branch, merge the pending
   merge-back PR, turn auto-delete off, …).
4. **Consistency check:** every package that receives a constraint edit to its
   own `composer.json` must have a cut row, except `o3-shop/o3-shop`, which is
   always tagged at `--to`. A `reuse-latest-tag` or `unchanged` package with an
   edit leaves that edit in no tag. The planner prevents this since shop-ce#157 (except
   across back-edges). If it shows up anyway, stop: no flag forces a cut
   (`--bump` only sets the version of a cut that already happens), so it needs a
   tooling fix or a decision from the user.
5. **Commits to check:** for every row with the note
   `commits exist on <branch> beyond <tag>`, list the commits
   (`gh api repos/<github repo>/compare/<tag>...<branch> --jq '.commits[].commit.message'`)
   so the user can confirm they belong in this release. Unfinished work merged
   to a leaf's release branch ships otherwise.
6. **RC or final:** for an RC, no merge-back PRs are opened, the milestone stays
   open and the docs aren't updated.

Exit `3`, a missing gate entry, or a failed point 4: stop here. The user fixes
the cause; then repeat Step 2c.

## Step 4: live run (gate B)

Ask: "Run `bin/release --from $FROM --to $TO` live? It re-runs the gates
(fast-forwarding the listed clones again), pushes commits and these tags:
<list>, creates draft releases, and <for a final> pushes a
`merge-back-<tag>` branch and opens a merge-back PR in <list>." Only on a yes,
run the same command without `--dry-run`, with the same `--repo-path` and
`--bump` flags, logged the same way.

From the end of the log, collect `Draft GitHub releases created:` and
`Merge-back PRs opened:` (package → URL). A draft's URL never contains its tag
(it reads `…/releases/tag/untagged-<hash>`): take each package's tag from its
`[<package>] tag <version>` progress line in the same log.

If the last line doesn't start with `Live execution complete.`, stop and read
the log:

- **Exit `3`, `Pre-flight gates aborted the release.`:** the live run re-ran the
  gates and something changed since the dry-run. No commit, tag or release was
  created (clones may have been fast-forwarded). Fix the cause and go back to
  Step 2c.
- **`Live execution failed:` about a merge-back PR:** every tag and draft
  already exists. Follow the manual steps the error message gives for that
  repo, then go on to Step 5.
- **Any other `Live execution failed:`:** tags already pushed stay pushed. Show
  the partial state the tool printed and don't re-run on your own; a re-run
  treats the pushed tags as existing.

## Step 5: publish (gate C), merge-backs (gate D), Packagist

**Publish.** List every draft (package, GitHub repo from the URL, tag from the
progress line, URL) and
ask once for approval to publish them all. `bin/release` creates every draft
with `prerelease=false`, title = tag; the flags are set here, per tag, not per
shop version:

| Release | Flags | Title |
|---|---|---|
| pre-release tag (`-RC<n>`, `-alpha`, `-beta`, `-dev`, `-preview`, `-pre`, `-p`; `MergeBackPolicy::PRE_RELEASE_PATTERN`) | `--prerelease --latest=false` | tag |
| final tag, highest final in its repo | `--prerelease=false --latest` | tag |
| final tag on an older line | `--prerelease=false --latest=false` | tag |
| `o3-shop/o3-shop`, final | as above | `O3-Shop vX.Y.Z` |
| `o3-shop/o3-shop`, RC | `--prerelease --latest=false` | `O3-Shop vX.Y.Z-RC<n>` |

The `o3-shop` titles follow *How to contribute → Step 14*; past releases (e.g.
`v1.7.2`) kept the bare tag. Name the title you will set in the gate C question.

"Highest final in its repo": compare against
`git ls-remote --tags --refs <repo url>` filtered to `vX.Y.Z` tags and sorted
with `sort -V`. In an RC cut, leaf packages often get final tags; those publish
as normal releases.

```bash
gh release edit <tag> -R <github repo> --draft=false <flags> [--title "O3-Shop <tag>"]
```

Before each: `gh release view <tag> -R <github repo> --json isDraft,body` shows
the draft with a non-empty body. Publish in the order the run printed them
(leaves first), `o3-shop/o3-shop` **last**, so the project tag appears only
after everything it pins.

Afterwards, verify each:
`gh release view <tag> -R <github repo> --json isDraft,isPrerelease` matches
the table, and `gh release list -R o3-shop/o3-shop --limit 3` shows the right
*Latest* (never an RC).

**Merge-backs (final only).** Repos released from `main` (the themes,
`shop-demodata-ce`, `tinymce-editor`, …) get none. List the PRs from the log.
Ask before merging (gate D). Merge with a merge commit, never squash or rebase:
the tag must become an ancestor of `main`. A conflict on the version pin in
`composer.json` is resolved in favour of the new release, which means a commit
on the `merge-back-<tag>` branch. The PR body asks to delete that branch
afterwards. Name the conflict commit (if any), the merge and the branch
deletion in the gate D question.

**Demo image.** Publishing the `o3-shop` release starts *Build and Push Demo
Docker Image* (~35 min):
`gh run list -R o3-shop/o3-shop --workflow docker-build.yml --limit 3`. When
green, `docker manifest inspect ghcr.io/o3-shop/o3-shop-demo:<tag>` succeeds.
`:latest` moves only for a release marked *Latest*: compare the digests of
`docker buildx imagetools inspect ghcr.io/o3-shop/o3-shop-demo:latest` and
`…:<tag>`; they match after a final release and differ after an RC.

**Packagist** needs 5–60 minutes. For every new tag:

```bash
curl -s https://repo.packagist.org/p2/<package>.json | grep -c '"version":"<tag>"'
```

Missing after an hour: give the user `https://packagist.org/packages/<package>`
to press *Update*.

**Smoke test (optional, after Packagist).** With shop-ce's bundled composer
(2.2.x, as in production), not the one on `PATH`:

```bash
"$SHOP_CE"/vendor/bin/composer create-project --no-scripts --no-interaction \
  o3-shop/o3-shop "$LOG_DIR/smoke-$TO" "$TO"
```

**Milestone (final only, gate E).** Ask, then close the milestone found in Step
1.5.

## Step 6: docs.o3-shop.com (final releases only, gates F and G)

The docs repo is `o3-shop/o3-documentation` (Sphinx, built by Read the Docs
from `main`). Follow *How to contribute → Step 13*; it holds the current file
list.

Its local clone must be on `main`, clean, and level with `origin/main` (same
checks as Step 1.4). Then ask gate F (create branch `docs-<TO>` off `main` and
commit the changes below), and on a yes:

1. `source/conf.py`: `version` = `MAJOR.MINOR`, `release` = full version.
2. `tools/repos.txt`: `anchor: shop-ce@<FROM>`. After the releases are
   published (drafts don't appear), run
   `GH_TOKEN=$(gh auth token) python3 tools/fetch_releases.py` and commit
   `source/WhatsChanged.md`.
3. Version constraints in `source/User/Installation/NewInstallation.md` on a
   new minor or major.
4. Every example version (Step 13, item 4). Then grep for the examples the
   previous docs update used: the old `--to` (= `FROM`), the old `--from` (the
   final before `FROM`) and the old RC example (the `-RC1` after `FROM`), e.g.
   for `TO=v1.7.3`: `grep -rnE 'v?1\.7\.(1|2)\b|1\.7\.3-RC1' source/User/Installation/DemoImage.md source/Developer/HowToContribute.md`.
   On a new minor or major, also grep the old line: `b-1\.7` and `'1\.7'`
   (`conf.py`, the README link in Step 5, the branch examples). List every hit
   for review: some must stay (e.g. "v1.7.2 and older" in a version note).
   Don't treat every hit as a leftover.
5. `Migration.md` and the system-requirements pages: list the release-notes
   entries that look relevant (PHP/MySQL minimums, upgrade steps, setup
   changes) and ask the user. Don't guess.
6. `make html` builds with no new warnings.

Then ask gate G (push the branch, open the PR against `main`). After merge,
check that https://docs.o3-shop.com/en/latest/ shows the new version and the
new *What's Changed* section (RTD needs a few minutes).

## Step 7: report

What was cut (tags), what was published with which flags, merge-back and docs PR
URLs, the demo image and Packagist status, and anything still open (unmerged
PRs, tags missing on Packagist, the announcement in *How to contribute → Step
16*).
