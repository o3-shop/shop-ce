# Claude Code Setup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make O3-Shop CE immediately productive for any new Claude Code user — shared agent memory, quality-gate finish skill, auto-formatting hook, and complete project context.

**Architecture:** Extend `docker.sh` with two new commands (`cs-fixer`, `test-all-coverage`), create a static `CLAUDE.md` for project context, wire a PostToolUse hook in `.claude/settings.json`, seed a committed `.claude/memory/` system for shared agent knowledge, and add a `/finish` skill that enforces the full quality gate before any task is declared done.

**Tech Stack:** Bash (docker.sh), Markdown (CLAUDE.md, memory files, skill), JSON (settings.json)

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `docker.sh` | Modify | Add `cs-fixer` case + `run_full_test_with_coverage()` + `test-all-coverage` case + help text |
| `CLAUDE.md` | Create | Static project context for every Claude session |
| `.claude/settings.json` | Create | PostToolUse hook — auto-runs cs-fixer after PHP edits |
| `.claude/memory/MEMORY.md` | Create | Index of all memory files (loaded by CLAUDE.md instruction) |
| `.claude/memory/project-conventions.md` | Create | PSR-12, DBAL, namespace, Smarty conventions |
| `.claude/memory/known-pitfalls.md` | Create | Bugs and mistakes already found in this repo |
| `.claude/memory/architecture.md` | Create | DI wiring, module system, key architectural decisions |
| `.claude/memory/testing-patterns.md` | Create | PHPUnit setup, mocking, test structure conventions |
| `.claude/skills/finish/SKILL.md` | Create | /finish skill — quality gate before marking work done |

---

## Task 1: Add `cs-fixer` command to `docker.sh`

**Files:**
- Modify: `docker.sh`

- [ ] **Step 1: Verify current help output (baseline)**

```bash
./docker.sh | grep -E "cs-fixer|test-all-coverage"
```

Expected: no output (commands don't exist yet).

- [ ] **Step 2: Add `cs-fixer` to the case block**

In `docker.sh`, find the `case "$1" in` block (line ~199). Add before the `*)` catch-all:

```bash
    cs-fixer)
        run_php_cs_fixer || exit 127
        ;;
```

- [ ] **Step 3: Add `cs-fixer` to the help text**

In the `*)` catch-all block, add to the Commands section (after the `test-all` line):

```bash
        echo "  cs-fixer     Run php-cs-fixer on the entire codebase"
```

- [ ] **Step 4: Verify syntax is valid**

```bash
bash -n docker.sh
```

Expected: no output (no syntax errors).

- [ ] **Step 5: Verify command appears in help**

```bash
./docker.sh | grep cs-fixer
```

Expected: `  cs-fixer     Run php-cs-fixer on the entire codebase`

- [ ] **Step 6: Commit**

```bash
git add docker.sh
git commit -m "feat: add cs-fixer standalone command to docker.sh"
```

---

## Task 2: Add `test-all-coverage` command to `docker.sh`

**Files:**
- Modify: `docker.sh`

- [ ] **Step 1: Add `run_full_test_with_coverage()` function**

In `docker.sh`, after the existing `run_full_test_with_cs_fixer()` function (around line ~181), add:

```bash
run_full_test_with_coverage() {
  run_php_cs_fixer
  echo ""
  echo "---------------------------"
  echo "Now running tests with coverage:"
  echo "---------------------------"
  run_tests --coverage
}
```

- [ ] **Step 2: Add `test-all-coverage` to the case block**

In the `case "$1" in` block, add after the `test-all)` entry:

```bash
    test-all-coverage)
        run_full_test_with_coverage || exit 127
        ;;
```

- [ ] **Step 3: Add `test-all-coverage` to the help text**

In the `*)` catch-all block, add after the `test-all` help line:

```bash
        echo "  test-all-coverage  Run php-cs-fixer, then full test suite with coverage report"
```

- [ ] **Step 4: Verify syntax**

```bash
bash -n docker.sh
```

Expected: no output.

- [ ] **Step 5: Verify both new commands appear in help**

```bash
./docker.sh | grep -E "cs-fixer|test-all-coverage"
```

Expected:
```
  cs-fixer     Run php-cs-fixer on the entire codebase
  test-all-coverage  Run php-cs-fixer, then full test suite with coverage report
```

- [ ] **Step 6: Commit**

```bash
git add docker.sh
git commit -m "feat: add test-all-coverage command to docker.sh"
```

---

## Task 3: Create `.claude/settings.json` with PostToolUse hook

**Files:**
- Create: `.claude/settings.json`

- [ ] **Step 1: Create `.claude/` directory and `settings.json`**

Create `.claude/settings.json` with this exact content:

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Edit|Write",
        "hooks": [
          {
            "type": "command",
            "command": "FILE=$(echo \"$CLAUDE_TOOL_INPUT\" | python3 -c \"import sys,json; d=json.load(sys.stdin); print(d.get('file_path',''))\"); if [[ \"$FILE\" == *.php ]] && docker ps --format '{{.Names}}' | grep -q '^o3shop-app$'; then cd $(git rev-parse --show-toplevel) && ./docker.sh cs-fixer 2>/dev/null || true; fi"
          }
        ]
      }
    ]
  }
}
```

- [ ] **Step 2: Verify JSON is valid**

```bash
python3 -m json.tool .claude/settings.json
```

Expected: prints the formatted JSON without errors.

- [ ] **Step 3: Commit**

```bash
git add .claude/settings.json
git commit -m "feat: add PostToolUse hook to auto-run cs-fixer on PHP file edits"
```

---

## Task 4: Create `CLAUDE.md`

**Files:**
- Create: `CLAUDE.md`

- [ ] **Step 1: Create `CLAUDE.md` in the repo root**

```markdown
# O3-Shop Community Edition

PHP e-commerce platform (OxidEsales fork). All dev work runs inside Docker.

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
- **Templates:** Smarty ~2.6. Template files live in `source/Application/views/`.
- **Dependency injection:** Symfony container. Services registered via YAML configs in `source/Internal/`.
- **Branches:** Feature branches off `b-1.5`. Naming: `NNN-short-description` (issue number prefix).
- **Main branch:** `b-1.5`

## Agent Memory

This repo has a shared memory system at `.claude/memory/`. All agents working here contribute to it.

**Before finishing any task:**
1. Read `.claude/memory/MEMORY.md` (the index)
2. If you learned something non-obvious during your work, find the relevant memory file and append it
3. If nothing fits, create a new memory file and add it to `MEMORY.md`

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
```

- [ ] **Step 2: Verify file exists and is readable**

```bash
head -5 CLAUDE.md
```

Expected: first 5 lines of the file.

- [ ] **Step 3: Commit**

```bash
git add CLAUDE.md
git commit -m "feat: add CLAUDE.md with project context for Claude Code users"
```

---

## Task 5: Create `.claude/memory/` structure

**Files:**
- Create: `.claude/memory/MEMORY.md`
- Create: `.claude/memory/project-conventions.md`
- Create: `.claude/memory/known-pitfalls.md`
- Create: `.claude/memory/architecture.md`
- Create: `.claude/memory/testing-patterns.md`

- [ ] **Step 1: Create `MEMORY.md` index**

Create `.claude/memory/MEMORY.md`:

```markdown
# Project Memory Index

Shared memory for all Claude agents working in this repository. Read this first, then open relevant files for detail.

- [Project Conventions](project-conventions.md) — PSR-12, DBAL patterns, namespace rules, Smarty usage
- [Known Pitfalls](known-pitfalls.md) — bugs and mistakes already encountered in this repo
- [Architecture](architecture.md) — DI wiring, module system, key architectural decisions
- [Testing Patterns](testing-patterns.md) — PHPUnit setup, mocking, test structure conventions
```

- [ ] **Step 2: Create `project-conventions.md`**

Create `.claude/memory/project-conventions.md`:

```markdown
---
name: Project Conventions
description: PSR-12, Doctrine DBAL patterns, namespace rules, Smarty, branch naming
type: reference
---

## Code Style
- PSR-12, enforced by PHP-CS-Fixer with `.php-cs-fixer.dist.php`
- Single quotes for strings (unless interpolation needed)
- Array short syntax `[]`, trailing commas in multi-line arrays
- Imports ordered alphabetically, no unused imports

## Database
- Doctrine DBAL ≤2.12 — use `QueryBuilder`, never raw PDO or string-concatenated SQL
- Access DB via `\OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory`
- Test DB name: `o3shop-test` (switched automatically by `run-tests.sh`)

## Namespaces
- Application code: `OxidEsales\EshopCommunity\` → `source/`
- Tests: `OxidEsales\EshopCommunity\Tests\` → `tests/`
- Modules must NOT use classes from `source/Internal/` (blacklisted)

## Templates
- Smarty ~2.6
- Template files: `source/Application/views/{frontend,admin}/`
- Cache: `source/tmp/smarty/` — clear when templates misbehave

## Branches
- Main branch: `b-1.5`
- Feature branches: `NNN-short-description` (NNN = GitHub issue number)
- Commit prefix: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`
```

- [ ] **Step 3: Create `known-pitfalls.md`**

Create `.claude/memory/known-pitfalls.md`:

```markdown
---
name: Known Pitfalls
description: Bugs and non-obvious mistakes already encountered in this codebase
type: feedback
---

## Bot/Guest Request Handling
- `UtilsComponent::toCompareList()` (and similar utils methods) can crash on bot requests where session/user context is not fully initialised. Always guard with a user/session existence check before accessing user-dependent data.

## Article List Checks
- `Article::isInList()` must check both wish list and notice list independently. A missing check on one list caused a bug (fixed in eb4c3c8).

## Directory Creation
- Do not use ad-hoc `mkdir()` calls scattered through setup code. Use the centralised safe helper introduced in eb4c3c8. It handles race conditions and permission errors gracefully.

## php-cs-fixer Cache
- `.php-cs-fixer.cache` is gitignored but speeds up repeated runs significantly. If fixer seems to miss files, delete the cache and re-run.
```

- [ ] **Step 4: Create `architecture.md`**

Create `.claude/memory/architecture.md`:

```markdown
---
name: Architecture
description: Key architectural decisions, DI wiring, module system, Symfony integration
type: reference
---

## Dependency Injection
- Symfony DI container
- Service definitions: YAML files inside `source/Internal/`
- Container is compiled — after adding a new service, clear `source/tmp/`
- Entry point for container: `source/bootstrap.php`

## Module System
- Modules live in `source/modules/`
- Modules extend shop classes via OxidEsales chain inheritance
- Modules must NOT depend on `source/Internal/` (considered private API)
- Module configuration: `metadata.php` in each module root

## CLI
- Symfony Console via `bin/oe-console`
- Add commands by registering them in the DI container with the `console.command` tag

## Email
- PHPMailer via `\OxidEsales\EshopCommunity\Core\Mailer`
- Test emails caught by Mailpit at http://localhost:8025

## Logging
- Monolog v2, PSR-3 compatible
- Logger available via DI: `\Psr\Log\LoggerInterface`

## Configuration
- Runtime config: `.env` (copied from `.env.example` on first run)
- Docker-specific env: `docker/.env` (auto-generated from `.env.example`)
- Shop config in DB — editable via Admin panel or `source/config.inc.php`
```

- [ ] **Step 5: Create `testing-patterns.md`**

Create `.claude/memory/testing-patterns.md`:

```markdown
---
name: Testing Patterns
description: PHPUnit setup, mocking with Prophecy, test structure and naming conventions
type: reference
---

## Running Tests
- Single file (fast): `./docker.sh test --fast tests/Unit/Path/To/ClassTest.php`
- Full suite: `./docker.sh test`
- With coverage: `./docker.sh test --coverage tests/Unit`
- Quarantine (slow): `./docker.sh quarantine`

## Test Structure
- Unit tests: `tests/Unit/` — mirror `source/` directory structure
- Integration tests: `tests/Integration/`
- Acceptance tests: `tests/Acceptance/` — Selenium-based, requires full stack
- Test class naming: `{ClassName}Test` in same sub-namespace as class under test

## Mocking
- PHPSpec Prophecy (`phpspec/prophecy-phpunit`)
- Use `$this->prophesize(SomeClass::class)` — not PHPUnit's built-in mocks
- Reveal the mock: `$mock->reveal()`

## Bootstrap
- Fast mode uses `vendor/o3-shop/testing-library/bootstrap.php`
- DB is switched to `o3shop-test` automatically by `run-tests.sh` — never touch production DB in tests

## Groups
- Tag slow/special tests with `@group quarantine`
- All other tests run by default (quarantine group excluded)

## Coverage
- Output: `coverage/coverage.xml` (Clover), `coverage/html/` (HTML), `coverage/junit.xml`
- View HTML report: open `coverage/html/index.html` in a browser
```

- [ ] **Step 6: Verify all files created**

```bash
ls .claude/memory/
```

Expected:
```
MEMORY.md
architecture.md
known-pitfalls.md
project-conventions.md
testing-patterns.md
```

- [ ] **Step 7: Commit**

```bash
git add .claude/memory/
git commit -m "feat: add shared agent memory system at .claude/memory/"
```

---

## Task 6: Create `/finish` skill

**Files:**
- Create: `.claude/skills/finish/SKILL.md`

- [ ] **Step 1: Create the skills directory and skill file**

Create `.claude/skills/finish/SKILL.md`:

```markdown
---
name: finish
description: Quality gate before marking any task complete. Runs cs-fixer + full tests + coverage, then updates shared agent memory with lessons learned.
---

# Finish Protocol

Run this skill before declaring any task complete.

## Steps

### 1. Run the quality gate

```bash
./docker.sh test-all-coverage
```

This runs (in order):
1. `./docker.sh cs-fixer` — fix all code style violations
2. Full unit test suite with coverage report

**If this fails:** Stop. Report what failed. Do NOT update memory. The task is not done — fix the failure first.

**If this passes:** Continue to step 2.

### 2. Review shared memory

Read `.claude/memory/MEMORY.md` to see what memory files exist, then read any that are relevant to the work you just completed.

### 3. Update memory

Ask yourself: **"Did I learn anything non-obvious during this task?"**

Examples of what belongs in memory:
- A class/method that behaves unexpectedly
- A pattern that works well (or poorly) in this codebase
- A pitfall that caused a bug or wasted time
- An architectural constraint that wasn't obvious from reading the code

**If yes:** Find the relevant memory file and append the lesson. If no file fits, create a new one with frontmatter and add it to `MEMORY.md`.

**If no:** Skip this step.

Memory file frontmatter format:
```markdown
---
name: <name>
description: <one-line summary used to decide relevance>
type: reference | feedback | project
---
```

### 4. Report to the user

Summarise:
- Tests passed (or what failed)
- Coverage report location: `coverage/html/index.html`
- What (if anything) was written to memory
```

- [ ] **Step 2: Verify file exists**

```bash
cat .claude/skills/finish/SKILL.md | head -5
```

Expected: first 5 lines of the skill file.

- [ ] **Step 3: Commit**

```bash
git add .claude/skills/
git commit -m "feat: add /finish skill for quality gate and memory update"
```

---

## Task 7: Final verification

- [ ] **Step 1: Verify all files are in place**

```bash
ls CLAUDE.md .claude/settings.json .claude/memory/ .claude/skills/finish/SKILL.md docker.sh
```

Expected: all files listed without errors.

- [ ] **Step 2: Verify docker.sh syntax and new commands**

```bash
bash -n docker.sh && ./docker.sh | grep -E "cs-fixer|test-all-coverage"
```

Expected:
```
  cs-fixer     Run php-cs-fixer on the entire codebase
  test-all-coverage  Run php-cs-fixer, then full test suite with coverage report
```

- [ ] **Step 3: Verify settings.json is valid JSON**

```bash
python3 -m json.tool .claude/settings.json > /dev/null && echo "valid"
```

Expected: `valid`

- [ ] **Step 4: Final commit (if any loose files)**

```bash
git status
```

If anything is unstaged, add and commit. Otherwise skip.
