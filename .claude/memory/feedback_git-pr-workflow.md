---
name: Git / PR / merge workflow preferences
description: When to commit, push, open PRs, coordinate across repos; merge strategy; pre-commit verification
type: feedback
---

User preferences for the git workflow on this project. Captured 2026-04-27 during the §356a revocation feature work.

## Commit cadence

- After each work batch, **stop and wait for instructions** before committing. Do not auto-commit at the end of every task or every phase.
- A "batch" is whatever logical unit the conversation is currently working on (one task, one phase, one set of related edits — context-dependent).
- The user may say "commit", "yes", or "go ahead" — that's the green light. Until then, leave the working tree dirty.
- Always create new commits, never amend (unless explicitly asked).
- Stage specific files by name; do not use `git add -A` / `git add .`.

## Pushing branches

- **Never push to a remote without an explicit instruction.** No "I'll push this so you can review" — wait to be asked.
- This applies to feature branches, the main branch, and any other ref.

## Opening PRs

- **Never open a PR without an explicit instruction.** Even a draft PR.
- When asked, follow the harness defaults: title ≤ 70 chars, body via heredoc, cross-link related PRs, include the Co-Authored-By trailer.

## Multi-repo coordination

- When work spans multiple repos (e.g. shop-ce + wave-theme + o3-Theme), the **main repo (shop-ce)** is the anchor.
- When the main repo is committed/pushed (under explicit instruction), the satellite repos can be committed/pushed in the same step under the same instruction.
- Do not commit or push satellite repos ahead of, or independent of, the main repo's progress unless explicitly asked.

## Merge strategy

- **Squash on default.** When opening a PR, frame it as a squash-merge candidate (single resulting commit on the target branch).
- The squashed commit message takes the PR title and the body of the squash dialog — write the PR title and description with that in mind.

## Pre-commit verification

- **Run `./docker.sh cs-fixer` before every commit.** It auto-fixes most PSR-12 issues; if there are remaining warnings, fix them before committing.
- Tests do not need to run on every commit during apply (that would be too slow); they run as part of the project-wide quality gates phase (phase 14 in the §356a tasks list, or equivalent in other features).
- Never bypass `cs-fixer` with `--no-verify` or similar.

## Why these defaults

The user is the maintainer who reviews and lands his own PRs (see `user_role.md` in user-level memory) and prefers to control timing for commits, pushes, and PRs himself. Auto-committing or auto-pushing reduces his ability to shape the history. The cs-fixer step is mandatory because the project enforces PSR-12 strictly via `.php-cs-fixer.dist.php`.
