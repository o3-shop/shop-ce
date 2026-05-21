# O3-Shop Community Edition

PHP e-commerce platform (OxidEsales fork). All dev work runs inside Docker.

## Claude Code Workflow

Workflow skills are bundled in `.claude/skills/` and trigger automatically. See `superpowers.md` in the repo root for the full developer guide.

| Skill | When to use |
|---|---|
| `brainstorming` | Before building anything new |
| `writing-plans` | Turns a spec into an implementation plan |
| `test-driven-development` | Every feature or bugfix |
| `systematic-debugging` | Any bug, test failure, or unexpected behaviour |
| `verification-before-completion` | Before claiming work is done |
| `finishing-a-development-branch` | Wrapping up a branch |
| `subagent-driven-development` | Execute plans with parallel subagents |
| `requesting-code-review` | Before merging |
| `receiving-code-review` | After getting review feedback |
| `/finish` | Quality gate: cs-fixer + full tests + coverage |
| `/push-pr` | Only if the user prompt to run it |

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

Always pull up your own docker environment with `./docker.sh start` before running any commands. The `test` commands assume the environment is up and will fail if it's not.

If you want to run any `docker exec ...` command manually, pull up your own container and run it inside it. The naming is following: o3shop-{worktree-name}-1. For example, if your worktree is `feature/123-new-feature`, the container will be `o3shop-feature-123-new-feature-1`.

Should your own docker environment not work, say it to me, I fix it for you.

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
- **Templates:** Smarty ~2.6. Template files live in `source/Application/views/{admin,o3-theme,wave}/`.
- **Dependency injection:** Symfony container. Services registered via YAML configs in `source/Internal/`.
- **Branches:** Feature branches off `b-1.6.0`. Naming: `NNN-short-description` (issue number prefix).
- **Main branch:** `b-1.6.0`

## Logging Standards

Reference: https://projects.wiki.tro.net/books/00002-development/page/how-to-write-good-log-files

### The 3 W's: When, Where, What

- **When:** Handled by Monolog. Timestamps must include microseconds and timezone (ISO 8601: `Y-m-d\TH:i:s.uP`).
- **Where:** Always prefix with `__METHOD__ . ' - '` (dash separates where from what).
- **What:** Describe what the software is doing. Full sentences, in English, ending with `.`

### Rules

1. **`__METHOD__` prefix** with dash: `__METHOD__ . ' - '`
2. **Full sentences** ending with a period `.`
3. **Quote variables** with single quotes: `'$variable'` — makes empty values visible as `''`
4. **Label IDs** by meaning: `order-ID '$orderId'` not just `ID '$id'`
5. **Use active language**, not "trying to": `Fetching articles.` not `Trying to fetch articles.`
6. **Tell what it means**: `Service returned '200'. Token is still valid.` not just `Service returned '200'.`
7. **English only** — always, even for "just this moment"
8. **No line breaks** in log messages — DevOps tools are line-based
9. **No binary data** — replace with `(deleted data for logfile)`
10. **Use the data parameter** (second argument, array) for structured context

### Log levels (RFC 5424)

`DEBUG` < `INFO` < `NOTICE` < `WARNING` < `ERROR` < `CRITICAL` < `ALERT` < `EMERGENCY`

### Examples

```php
// Correct
Registry::getLogger()->info(__METHOD__ . " - Fetching user '$userName' from IDP.");
Registry::getLogger()->error(__METHOD__ . " - Connection to API failed: '$error'.");
Registry::getLogger()->warning(__METHOD__ . " - Invalid token received from: '$source'.", ['token' => $token]);
Registry::getLogger()->debug(__METHOD__ . " - Found '3' orders for customer-ID '$customerId'.");

// Wrong — missing dash, no quotes, no period, "trying to", unlabeled ID
Registry::getLogger()->info(__METHOD__ . 'Trying to fetch user ' . $userName);
Registry::getLogger()->error('Connection failed', ['error' => $e->getMessage()]);
Registry::getLogger()->debug(__METHOD__ . " - Found order with ID '$id'.");
```

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
