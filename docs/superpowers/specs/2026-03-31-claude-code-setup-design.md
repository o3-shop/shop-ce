# Claude Code Setup Design

**Date:** 2026-03-31
**Status:** Approved
**Branch:** b-1.5-setup-claude

## Overview

Make the O3-Shop CE repository immediately productive for any new Claude Code user by providing:
- Complete project context via `CLAUDE.md`
- A shared, growing agent memory system at `.claude/memory/`
- Standalone `cs-fixer` and `test-all-coverage` commands in `docker.sh`
- A `/finish` skill that enforces quality gates before marking work done
- A PostToolUse hook that auto-runs php-cs-fixer after PHP file edits

---

## 1. `docker.sh` Extensions

### New command: `cs-fixer`
Exposes the existing `run_php_cs_fixer()` function as a standalone command.

```bash
./docker.sh cs-fixer
```

Requires `o3shop-app` container to be running. Runs `php-cs-fixer fix` inside the container.

### New command: `test-all-coverage`
New function `run_full_test_with_coverage()` that chains:
1. `run_php_cs_fixer`
2. `run_tests --coverage`

```bash
./docker.sh test-all-coverage
```

Coverage output: `/var/www/html/coverage/` inside container (clover XML, HTML report, JUnit XML).

### Updated `case` block
```
cs-fixer          → run_php_cs_fixer
test-all-coverage → run_full_test_with_coverage
```

---

## 2. `CLAUDE.md` (repo root)

Static, human-maintained. Read by every Claude session via Claude Code's automatic loading.

### Sections

- **Project overview** — O3-Shop CE, PHP 7.4/8.0+, main branch `b-1.5`
- **Environment setup** — Docker-based, requires `./docker.sh start` before any work
- **Command reference** — full table of all `docker.sh` commands
- **Project structure** — key directories and PSR-4 namespaces
- **Conventions** — PSR-12, Doctrine DBAL ≤2.12, Symfony DI, Smarty ~2.6, branch naming
- **Agent memory** — pointer to `.claude/memory/MEMORY.md`; instruction to read before finishing and update with non-obvious lessons
- **Finish protocol** — run `/finish` before marking any task complete

### Key rule embedded in CLAUDE.md
> Before finishing any task, run `/finish`. This runs cs-fixer, full tests, and coverage, then prompts you to update the shared agent memory.

---

## 3. `.claude/memory/` (Project Agent Memory)

Committed to the repo. Shared across all agents working in this repository.

### Structure
```
.claude/
  memory/
    MEMORY.md              ← index (always read first)
    project-conventions.md ← PSR-12, DBAL, namespaces, Smarty
    known-pitfalls.md      ← bugs and mistakes already encountered
    architecture.md        ← key architectural decisions, DI wiring
    testing-patterns.md    ← how to write tests, mocking conventions
```

### `MEMORY.md` format
Index file. One line per memory file, under 150 chars:
```markdown
- [Project Conventions](project-conventions.md) — PSR-12, DBAL, namespace rules, Smarty usage
- [Known Pitfalls](known-pitfalls.md) — bugs and mistakes already encountered in this repo
- [Architecture](architecture.md) — DI wiring, module system, key architectural decisions
- [Testing Patterns](testing-patterns.md) — PHPUnit setup, mocking, test structure conventions
```

### Memory file frontmatter
```markdown
---
name: <memory name>
description: <one-line description>
type: reference | feedback | project
---
```

### Update protocol
When an agent learns something non-obvious:
1. Find the relevant memory file and append the lesson
2. If no file fits, create a new one with frontmatter and add it to `MEMORY.md`
3. Never duplicate existing entries — check first

---

## 4. `.claude/settings.json` (PostToolUse Hook)

Auto-runs php-cs-fixer inside Docker after any PHP file is edited by Claude.

```json
{
  "hooks": {
    "PostToolUse": [{
      "matcher": "Edit|Write",
      "hooks": [{
        "type": "command",
        "command": "FILE=$(echo \"$CLAUDE_TOOL_INPUT\" | python3 -c \"import sys,json; d=json.load(sys.stdin); print(d.get('file_path',''))\"); if [[ \"$FILE\" == *.php ]] && docker ps --format '{{.Names}}' | grep -q '^o3shop-app$'; then ./docker.sh cs-fixer 2>/dev/null || true; fi"
      }]
    }]
  }
}
```

Delegates entirely to `./docker.sh cs-fixer` — consistent with the standalone command, no duplicated logic. Runs on the whole codebase (not just the edited file), but `php-cs-fixer`'s cache makes subsequent runs fast. Only fires when the `o3shop-app` container is running — silently skips otherwise.

---

## 5. `/finish` Skill (`.claude/skills/finish/SKILL.md`)

User-invocable. Claude can also call it internally when deciding work is complete.

### Flow

1. **Run `./docker.sh test-all-coverage`**
   - If it fails: report what failed, stop. Do not update memory. Work is not done.
2. **Review memory** — read `.claude/memory/MEMORY.md` and relevant files
3. **Update memory** — append non-obvious lessons learned during this task
4. **Report** — tests passed, coverage location, what was written to memory

### Failure behaviour
If `test-all-coverage` fails, the skill halts. Claude reports the failure and waits for instruction. No memory update occurs.

---

## Files to Create/Modify

| File | Action |
|---|---|
| `docker.sh` | Add `cs-fixer`, `test-all-coverage` commands |
| `CLAUDE.md` | Create (repo root) |
| `.claude/settings.json` | Create |
| `.claude/memory/MEMORY.md` | Create |
| `.claude/memory/project-conventions.md` | Create |
| `.claude/memory/known-pitfalls.md` | Create |
| `.claude/memory/architecture.md` | Create |
| `.claude/memory/testing-patterns.md` | Create |
| `.claude/skills/finish/SKILL.md` | Create |
