# O3-Shop Community Edition

PHP e-commerce platform (OxidEsales fork). All dev work runs inside Docker.

## Claude Code Workflow

The following dev workflow skills are **bundled in this repo** at `.claude/skills/` — no plugin installation required:

| Skill | When to use |
|---|---|
| `brainstorming` | Before building anything new — explores intent and design |
| `writing-plans` | Turns a spec into a step-by-step implementation plan |
| `test-driven-development` | TDD for every feature or bugfix |
| `systematic-debugging` | Any bug, test failure, or unexpected behaviour |
| `verification-before-completion` | Before claiming work is done |
| `finishing-a-development-branch` | Wrapping up a branch (merge/PR/discard) |
| `subagent-driven-development` | Execute plans with parallel subagents + review checkpoints |
| `/finish` | Quality gate: cs-fixer + full tests + coverage + memory update |

**Recommended additional plugins** (install once, auto-activate for this repo via `.claude/settings.json`):

```bash
claude plugins add marketplace claude-plugins-official
```

| Plugin | What it adds |
|---|---|
| `superpowers` | Extended skill set (receiving code review, git worktrees, parallel agents) |
| `feature-dev` | Guided feature development with codebase understanding |
| `php-lsp` | PHP language server (inline errors, go-to-definition) |

## Quick Start

```bash
./docker.sh start   # start all containers (required before any work)
./docker.sh stop    # stop containers
./docker.sh rebuild # full rebuild from scratch (slow — only when needed)
```

Shop: http://localhost:8080 | Admin: http://localhost:8080/admin/ (admin@example.com / admin123)
Adminer: http://localhost:8081 | Mailpit: http://localhost:8025

## Command Reference

| Command | What it does |
|---|---|
| `./docker.sh start` | Start Docker containers |
| `./docker.sh stop` | Stop Docker containers |
| `./docker.sh rebuild` | Rebuild containers from scratch |
| `./docker.sh cs-fixer` | Run php-cs-fixer on the codebase |
| `./docker.sh test --fast tests/Unit/Path/Test.php` | Run a single test file (fast, no reinstall) |
| `./docker.sh test` | Run full unit test suite |
| `./docker.sh test-all` | cs-fixer + full test suite |
| `./docker.sh test-all-coverage` | cs-fixer + full tests + coverage report |
| `./docker.sh quarantine` | Run slow/special quarantine tests only |

Coverage reports land in `coverage/` (clover XML, HTML, JUnit XML).

## Project Structure

```
source/                          # Application code
  Application/                   # Controllers, Models, Components, Views
  Core/                          # Core framework classes
  Internal/                      # Internal utilities (not available to modules)
  admin/                         # Admin panel
  migration/                     # Database migrations
tests/
  Unit/                          # Unit tests (PHPUnit 9)
  Integration/                   # Integration tests
  Acceptance/                    # Selenium acceptance tests
docker/                          # Docker Compose setup (MySQL, Mailpit)
bin/oe-console                   # Symfony Console CLI entry point
```

**Namespace:** `OxidEsales\EshopCommunity\` → maps to `source/` (PSR-4)
**Test namespace:** `OxidEsales\EshopCommunity\Tests\` → maps to `tests/`

## Conventions

- **Style:** PSR-12, enforced by PHP-CS-Fixer (`.php-cs-fixer.dist.php`). Run `./docker.sh cs-fixer` before committing.
- **Database:** Doctrine DBAL ≤2.12. Use QueryBuilder — never raw PDO or string-concatenated SQL.
- **Templates:** Smarty ~2.6. Template files live in `source/Application/views/{admin,wave}/`.
- **Dependency injection:** Symfony container. Services registered via YAML configs in `source/Internal/`.
- **Branches:** Feature branches off `b-1.5`. Naming: `NNN-short-description` (issue number prefix).
- **Main branch:** `b-1.5`

## Agent Memory

This repo has a shared memory system at `.claude/memory/`. All agents working here contribute to it.

**Before finishing any task:**
1. Read `.claude/memory/MEMORY.md` (the index)
2. If you learned something non-obvious during your work, find the relevant memory file and append it
3. If nothing fits, create a new memory file and add it to `MEMORY.md`

**Mid-task capture:** If you encounter something surprising, non-obvious, or that contradicts your assumptions during a task — write it to `.claude/memory/` immediately. Don't wait for `/finish`.

Memory files use frontmatter:
```markdown
---
name: <name>
description: <one-line summary>
type: reference | feedback | project
---
```

## Finish Protocol

**Before marking any task complete, run `/finish`.**

The `/finish` skill runs:
1. `./docker.sh test-all-coverage` (cs-fixer + full tests + coverage)
2. Prompts you to update `.claude/memory/` with any lessons learned

If the tests fail, the task is not done.
