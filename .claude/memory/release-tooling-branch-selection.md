---
name: release-tooling-branch-selection
description: bin/release picks branches only from DefaultBranchResolver, runs pre-flight last, and fires live with no confirmation
type: reference
---

# `bin/release` — branch selection and execution gotchas

Learned opening the `b-1.7` line (2026-07-27). All of these cost real
debugging time and none are visible from the CLI help.

## `--to` does not choose the branch

`DefaultBranchResolver::PACKAGE_TO_BRANCH` is the **only** input to branch
selection. `--to=1.7.0-RC1` has zero influence — passing a 1.7 version while
the map still says `b-1.6` aborts on `BranchGate` with
`expected 'b-1.6' (release branch for this repo)`. There is no CLI override;
the map must be edited.

The nine shop-line repos (`o3-shop`, `shop-ce`, `shop-metapackage-ce`,
`testing-library`, `shop-facts`, `shop-ide-helper`,
`shop-unified-namespace-generator`, `shop-doctrine-migration-wrapper`,
`shop-demodata-installer`) branch per shop minor and move as a group. The
`b-1.0` / `b-7.0.x` / `b-6.5.x` / `support/2.6` / `main` groups version
independently and never move with the shop line.

## Pre-flight runs LAST, so a missing branch is not a gate error

Pre-flight is the final step inside `ReleasePlanner::plan()`, after the
dep-tree walk. Bumping the map before the branches exist on the remote means
`DepTreeWalker`'s raw `composer.json` fetch 404s first — you get
`Plan failed: ...` and **no gate ever runs**. Cut the branches, then bump the
map. `BranchGate` only covers the opposite case: a local checkout sitting on
the wrong branch.

## Live mode executes with no confirmation prompt

Omitting `--dry-run` means: gates pass → `LiveExecutor::execute()` runs
immediately. Tags are cut, PRs opened, draft releases created. There is no
"are you sure".

To exercise the gates *without* state changes, use `--dry-run` **plus**
explicit `--repo-path <pkg>=<abs path>` for each package. `--dry-run` alone
returns an empty repo-path map and therefore **skips pre-flight entirely** —
a clean dry run proves nothing about the gates.

## `UpToDateGate` mutates your local clones

It is not read-only. A behind-only branch is fast-forwarded
(`git merge --ff-only`) during the run, including runs that go on to abort.
The `WARN ... fast-forwarded to match` lines are edits that already happened.

## The merge-back PR's head IS the release branch

`b-1.6` is the head branch of `Merge v1.6.x release into main`. If
`delete_branch_on_merge` were `true`, merging it deletes the maintenance
line. Keep it `false` on every release repo; `DeleteBranchOnMergeGate`
enforces this and fails closed. That gate is scoped to repos whose release
branch is not `main` — a `main`-line repo cannot have a merge-back PR at all,
so auto-delete there is harmless feature-branch hygiene and must not block
the release.

See also [[release-tooling-intermediate-node-retag-gap]].
