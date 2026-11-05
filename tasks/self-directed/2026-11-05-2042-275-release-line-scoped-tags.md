# 275-release-line-scoped-tags — run log

Issue: o3-shop/o3-shop#275 (part of #274). Started 2026-11-05 20:42.

## Setup resolved 2026-11-05

- Gate: `./docker.sh test-all-coverage` (cs-fixer + full suite + coverage); containers `./docker.sh start`
- Destructive: `./docker.sh rebuild`; cs-fixer dirties the nested clones (clean them after)
- Forge: gh; code in o3-shop/shop-ce, issues in o3-shop/o3-shop, docs in o3-shop/o3-documentation
- Queue: maintainer-given (#274 plan: tooling, docs, memory note)
- Roster: maintainer `ralftrapp` (assign PRs; pushes run as this account)
- Channels: none configured → run log + handoff carry the session
- Files: tasks/self-directed/ (untracked, not gitignored); earlier tasks/ content
  (self-directed-run.md, todo.md) parked in the session scratchpad for the v1.7.3-RC2 cut
- Language: English; branches `NNN-short-description` off b-1.7

## Context

The v1.7.3-RC2 cut (paused) hit: paypal-checkout not in the branch map (keyed by repo
name), paypal-client falling back to a stale `main`, and repo-wide "latest tag".
Maintainer asked for #274's convention: one line per shop line, `vX.Y.n`, `main` follows newest.

## Decisions

- Line-versioned packages are an explicit opt-in list (not derived from "has vX.Y.* tags"):
  derivation would make the first release on a new line fall back to all tags and cut
  from the old line's highest tag.
- First tag on a new line: existing exact `--bump <slug>=vX.Y.0`; TagCutter already throws
  for a relative bump without a latest tag. No new first-tag logic.
- paypal-checkout maps to `b-1.0`, not `main`: fixes land on b-1.0 (paypal-module PR #2);
  `main` is the "latest" pointer, moved by merge-backs of final releases.
- Memory note (maintainer's 3rd task) goes into shop-ce `.claude/memory/` — o3-shop/o3-shop
  has no `.claude/`; shop-ce's is the shared agent memory.

## Progress
- Filed #274 (umbrella: convention + checklist) and #275 (this tooling fix). No duplicates found.
- Implemented: `Version/ReleaseLine` (opt-in list, `tagPrefix()`), resolver filters tags to the
  line + names the exact first-tag bump, TagCutter `onLine()` guard (flag, .next-bump, default),
  map: paypal-checkout → b-1.0, paypal-client → b-7.0.x, dead paypal-module key dropped.
- Remote-only dry-run `--from v1.7.2 --to v1.7.3-RC2` with the change: exit 0;
  paypal-checkout@b-1.0 reuse v1.0.1, paypal-client@b-7.0.x reuse v1.0.0 (no stale cut).
- Env: `./docker.sh start` aborted on the Adminer port (8081 held by faruti-app); shop + db
  came up anyway. `oe-console` then exited 255 silently: stale `source/tmp/container_cache.php`
  from before the b-1.7 fast-forward (InstallModuleConfigurationCommand ctor 2 → 6 args).
  Deleted the (gitignored) cache file; cache:clear + tests fine afterwards.
- ReleaseTooling unit tests: OK (329 tests, 819 assertions).
- Mutation check (Edit tool): resolver filter off, TagCutter guard off, map key back to
  paypal-module → 6 failures, exactly the new tests. Restored.
- Memory note (3rd task): `.claude/memory/module-versioning-convention.md` + index line;
  pitfall entry in release-tooling-branch-selection.md links it.
- Full gate: green (9973 tests, coverage 90.65%, cs-fixer 0 changes); nested clones cleaned.
- Cold review 1 (5 findings): fixed the unseen first-tag hint (moved into TagCutter) and the
  docs overstating enforcement; paypal-client merge-back into stale main documented.
- Cold review 2 (fresh, 9 findings): added branch-shape guard (LogicException), exact bump
  must be above the line's latest tag, map-key invariant test vs PackageRepoSlug::RENAMES,
  docblock + memory corrections. Deferred to PR follow-ups: dry-run blocked on a new line until
  --bump given; unknown --bump slugs silently ignored (pre-existing); RC-only line skips .0.
- Cold review 3 (fresh, 7 findings): "map to the newest line" was wrong (would cut v2.0.n
  into a 1.7.x release) → now "the line of the shop being released"; main no longer exempt
  (it is the missing-key fallback) + test that every line-versioned package maps to b-X.Y.
  Judgment: that diff applied the reviewer's own fix verbatim (one condition removed,
  mutation-tested) → not material, no 4th review.
- Mutation checks for every guard: each goes red without it. Final gate: 9980 tests OK,
  coverage 90.65%, cs-fixer 0; dry-run unchanged (paypal-checkout v1.0.1, paypal-client v1.0.0).
- Commits: ec221832 (code), memory docs commit.
