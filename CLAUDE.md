# O3-Shop Community Edition

PHP e-commerce platform (OxidEsales fork). All dev work runs inside Docker.

## Claude Code Workflow

All dev workflow skills are **bundled in this repo** at `.claude/skills/` — no plugin installation required. Skills trigger automatically based on context, or you can invoke them explicitly with `/skill-name`.

### How skills work

- **Auto-triggered:** Claude invokes the right skill based on what you ask. Say "help me build X" → brainstorming starts. Say "this test is failing" → systematic-debugging starts.
- **Explicit:** Type `/brainstorming`, `/systematic-debugging`, etc. to invoke directly.
- **Always run `/finish` before calling a task done** — it runs cs-fixer + full tests + coverage.

### Skill reference

| Skill | Trigger | When to use |
|---|---|---|
| `brainstorming` | "help me build/add/create X" | Before building anything new — explores intent and design |
| `writing-plans` | "plan this", "write a plan for" | Turns a spec into a step-by-step implementation plan |
| `test-driven-development` | any feature or bugfix | TDD for every feature or bugfix |
| `systematic-debugging` | "this is broken", "test fails" | Any bug, test failure, or unexpected behaviour |
| `verification-before-completion` | before "it's done" | Runs verification before claiming work is complete |
| `finishing-a-development-branch` | "wrap up this branch" | Guided merge/PR/discard options |
| `subagent-driven-development` | "execute this plan" | Execute plans with parallel subagents + review checkpoints |
| `executing-plans` | "start implementing" | Run a written plan in a separate session with checkpoints |
| `requesting-code-review` | before merging | Multi-agent review of your changes |
| `receiving-code-review` | after getting review feedback | Structured response to review comments |
| `dispatching-parallel-agents` | large independent tasks | Spawn multiple agents working in parallel |
| `using-git-worktrees` | feature isolation needed | Creates an isolated workspace via git worktree |
| `/finish` | task complete | Quality gate: cs-fixer + full tests + coverage + memory update |

### Example workflow: building a feature end-to-end

This is the recommended way to tackle any non-trivial feature. You don't need to orchestrate it — just describe your problem and the skills chain together automatically.

**1. Describe the problem in plain language**
> "Multiple Claude agents are fighting over the same Docker containers. They try to start their own shop but it fails because the port is already taken and the database gets corrupted by the other agent."

Brainstorming kicks in. It explores the codebase and asks targeted questions — each with multiple-choice answers or a free-text option:
- "How should port conflicts be resolved? A) fixed ports per worktree B) random assignment C) deterministic hash"
- "Should each worktree get its own database? A) yes B) shared DB with prefixed tables"

**2. Plan review**
Once brainstorming has enough context it writes a full implementation plan. You read it, push back on anything that looks wrong, and approve it. Nothing gets built until you say yes.

**3. Design doc review**
For larger features it also writes a design document (architecture, data flow, edge cases). Same drill — review, comment, approve.

**4. Choose execution mode**
> "Do you want to implement this in the current session or use subagent-driven development?"

Always choose **subagent-driven development**. It creates an isolated git worktree, splits the plan into independent steps, and runs them with dedicated subagents. Each step gets an automatic code review by a separate reviewer agent before the next step starts. The main thread stays clean and you're not blocked while work happens.

**5. Review and iterate**
When all steps are done, the agent comes back to you with a summary. You test it. If something's broken:
- Simple fix → it resolves it inline
- Complex fix with multiple options → back to the planning phase: updated questions, updated plan, your approval, then back into subagent execution

**6. Finish**
Run `/finish` — cs-fixer, full test suite, coverage check. If anything fails, the task isn't done.

---

### Other skills

#### `writing-plans`

After brainstorming has all the context, this skill writes the full implementation plan — broken into bite-sized tasks, each a 2–5 minute action, with exact files to touch, what to write, and how to verify it. Plans are saved to `docs/superpowers/plans/`. You review and approve before anything gets built.

---

#### `test-driven-development`

Enforces the red-green-refactor loop on every feature and bugfix. The iron law: no production code without a failing test first. If you write the code before the test, delete it and start over. Claude will refuse to skip this even when it "seems obvious" — that's exactly when the test matters most.

---

#### `systematic-debugging`

Stops you from guessing. When a test fails or something breaks, this skill forces root cause investigation before any fix is proposed. It traces the failure path, reads logs and stack traces, and identifies the actual cause. Symptom fixes are treated as failure — if you haven't found the root cause you haven't found the bug.

---

#### `verification-before-completion`

Before claiming anything is done, this skill runs the actual verification command and reads the full output. No assertions without evidence. It will never say "tests pass" without having run them in that message — if it can't prove it, it says so.

---

#### `finishing-a-development-branch`

When implementation is done and tests pass, this skill walks you through wrapping up: create a PR, merge, or discard. It verifies the test suite first and won't proceed if anything is failing. Presents structured options so you stay in control of what happens to the branch.

---

#### `executing-plans`

Takes a written plan file and executes it task by task with progress tracking. Raises concerns before starting if anything in the plan looks wrong. After all tasks are done it hands off to `finishing-a-development-branch` automatically. Use `subagent-driven-development` instead when subagents are available — it gets significantly better results.

---

#### `dispatching-parallel-agents`

When you have multiple independent problems (e.g. 3 failing test files with unrelated root causes), this skill dispatches one focused subagent per problem instead of investigating them one by one. Each agent gets precisely crafted context — never your session history — so they stay focused. Cuts investigation time dramatically on large breakages.

---

#### `using-git-worktrees`

Sets up an isolated workspace before implementation starts. Detects if you're already in a worktree (common in this repo) and skips creation if so. Falls back to manual git worktree if no native tool is available. Ensures work never happens directly on the main checkout.

---

### Code review skills

Two skills handle the full review loop — one for requesting a review, one for receiving one.

#### `requesting-code-review`

Dispatches a dedicated reviewer subagent that looks at your changes with fresh eyes — it never sees your session history, only the code diff. This keeps it focused and unbiased.

**When to use it:**
- After each step in subagent-driven development (catches issues before they compound)
- Before merging any feature branch
- When you're stuck and want a second opinion

**How it works:**

The skill gets the base and head commit SHAs, spins up a reviewer subagent with the diff as context, and returns structured feedback:
- **Critical** — fix immediately before continuing
- **Important** — fix before merging
- **Minor** — noted for later

**Example:**
> "I just finished implementing the port assignment logic. Let me request a code review before moving to the next step."

Claude gets the SHAs, dispatches the reviewer, and brings back the findings. You fix Critical and Important issues, then continue.

---

#### `receiving-code-review`

Use this when you get review feedback — whether from the reviewer subagent, a teammate on GitHub, or a PR comment. It enforces technical rigor instead of blind agreement.

**Core behavior:**
- Verifies feedback against the actual codebase before implementing anything
- Pushes back with technical reasoning if the reviewer is wrong or missing context
- Asks for clarification on unclear items before touching a single line (partial understanding = wrong implementation)
- Never says "great point!" or "you're absolutely right!" — just fixes things and shows it in the code

**Example — inline PR comment:**
> A reviewer says "remove this legacy code." Claude checks whether anything still depends on it, finds it's used by a build target, and responds: "This is needed for backward compat on 10.15+. Remove if we're dropping pre-13 support — your call."

**Example — unclear batch feedback:**
> You say "fix items 1–6." Claude understands 1, 2, 3, 6 but not 4 and 5. Instead of guessing, it stops and asks: "Understand items 1, 2, 3, 6. Need clarification on 4 and 5 before implementing."

---

### Plugins (auto-installed)

The repo registers `claude-plugins-official` automatically via `.claude/settings.json`. On first launch Claude Code will install:

| Plugin | What it adds |
|---|---|
| `superpowers` | Extended skill set (already bundled, plugin kept for updates) |
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
