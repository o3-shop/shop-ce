---
name: Storefront themes live in separate repos (gitignored locally)
description: wave + o3-theme are external GitHub repos; their dirs in shop-ce are gitignored snapshots
type: reference
---

## Topology

Storefront themes are NOT part of `shop-ce`. They are independent GitHub repositories:

- **wave** — https://github.com/o3-shop/wave-theme (current default theme)
- **o3-theme** — https://github.com/o3-shop/o3-Theme (incoming default; migration before 2026-05-01, see `project_o3-theme-migration.md`)

In shop-ce both target directories are explicitly gitignored:

```
# .gitignore lines 52-53
/source/Application/views/wave/*
/source/Application/views/o3-theme/*
```

**Status: target state, delivery in progress.**

The intended state — `docker/entrypoint.sh` clones the upstream theme repo
directly into `source/Application/views/wave/`, gated by an empty-directory
check so re-runs never overwrite an existing checkout — is being delivered
by **issue #114** (`dev: replace wave-theme zip bootstrap with git clone`).
Until #114 lands, the bootstrap still uses `wget` + `unzip` of the GitHub
`main` zip and produces a detached snapshot with no upstream linkage.

How to tell what state your local checkout is in: `ls -la
source/Application/views/wave/.git` — if a `.git` directory exists, you are
on the new bootstrap (working tree). If not, you are on the old bootstrap
(detached snapshot, edits are lost on rebuild). To migrate after #114
merges: `./docker.sh stop && rm -rf source/Application/views/wave &&
./docker.sh start`.

## Standard dev procedure for theme work

1. **Fresh machine**: `./docker.sh start` — entrypoint clones the theme repo
   into `source/Application/views/wave/`. Done.

2. **Existing machine still on the old zip snapshot**: stop docker, delete the
   directory, restart docker:
   ```bash
   ./docker.sh stop
   rm -rf source/Application/views/wave
   ./docker.sh start
   ```

3. To push changes back, switch the remote to SSH inside the theme directory:
   ```bash
   cd source/Application/views/wave
   git remote set-url origin git@github.com:o3-shop/wave-theme.git
   ```

4. Develop and commit theme changes there directly on a feature branch
   matching the shop-ce feature branch (e.g. `99-add-electronic-revocation-function`).
   Saves are visible to the running shop instantly.

5. Open one PR per repo. Cross-link them in both descriptions so reviewers can
   trace the full change.

## Multi-repo PR fan-out

A storefront feature can span up to three coordinated PRs:
- `o3-shop/shop-ce` — controllers, models, migrations, admin templates, admin/email lang keys
- `o3-shop/wave-theme` — wave storefront templates + storefront lang keys
- `o3-shop/o3-Theme` — same templates ported, during/after the migration phase

## Anti-patterns to avoid

- **Editing a `views/wave/` directory that has no `.git` inside it.** That is a
  leftover detached zip snapshot from the old bootstrap. Changes there are not
  version-controlled by anything and will be lost on rebuild. Delete the
  directory and let entrypoint re-clone a real checkout.
- **Trying to commit theme files to shop-ce.** Shop-ce ignores `views/wave/*`
  and `views/o3-theme/*`; commits there go nowhere. Theme commits belong in
  the theme repos.
- **Branching control flow on `getActiveTheme()` inside controllers.** Theme work
  belongs in templates; controllers must stay theme-agnostic so wave and o3-theme
  share one core path.

## Why this matters

The old wget+unzip bootstrap was silent — local edits to `views/wave/` looked
like they worked but were lost on container rebuild. The clone-into-place
bootstrap eliminates that foot-gun by making the directory a real working tree.
Captured during scoping of issue #99 / change `add-electronic-revocation` on
2026-04-26.
