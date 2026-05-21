# Superpowers — Developer Guide

All workflow skills are bundled in `.claude/skills/` — no plugin installation required. Just clone the repo and open it in Claude Code. Skills trigger automatically based on what you say, or you can invoke them explicitly with `/skill-name`.

## The Full Workflow (How to Use This in Practice)

This is the recommended way to tackle any non-trivial feature. You don't need to orchestrate anything — just describe your problem and the skills chain together automatically.

**1. Describe the problem in plain language**

> "Multiple Claude agents are fighting over the same Docker containers. They try to start their own shop but it fails because the port is already taken and the database gets corrupted by the other agent."

The `brainstorming` skill kicks in. It explores the codebase and asks targeted questions — each with multiple-choice answers or a free-text option:
- "How should port conflicts be resolved? A) fixed ports per worktree  B) random assignment  C) deterministic hash"
- "Should each worktree get its own database? A) yes  B) shared DB with prefixed tables"

**2. Plan review**

Once brainstorming has enough context it writes a full implementation plan. You read it, push back on anything that looks wrong, and approve it. Nothing gets built until you say yes.

**3. Design doc review**

For larger features it also writes a design document (architecture, data flow, edge cases). Same drill — review, comment, approve.

**4. Choose execution mode**

> "Do you want to implement this in the current session or use subagent-driven development?"

Always choose **subagent-driven development**. It creates an isolated git worktree, splits the plan into independent steps, and runs each step with a dedicated subagent. Each step gets an automatic code review by a separate reviewer agent before the next step starts. The main thread stays clean and you're not blocked while work happens.

**5. Review and iterate**

When all steps are done, the agent comes back with a summary. You test it. If something's broken:
- Simple fix → resolved inline
- Complex fix with multiple options → back to planning: updated questions, updated plan, your approval, then subagent execution again

**6. Finish**

Run `/finish` — cs-fixer, full test suite, coverage check. If anything fails, the task isn't done.

---

## Skill Reference

### `brainstorming`

**Trigger:** "help me build X", "I want to add X", "how should we approach X"

Before any feature gets planned or built, brainstorming explores what you actually want. It reads the codebase for context, then asks you focused questions with multiple-choice options (or free text). When it has enough context it hands off to `writing-plans`. Nothing moves forward until you've shaped the solution.

---

### `writing-plans`

**Trigger:** after brainstorming, or "write a plan for X"

Takes the output of brainstorming and produces a full implementation plan — broken into bite-sized tasks, each a 2–5 minute action, with exact files to touch, what to write, and how to verify it. Plans are saved to `docs/superpowers/plans/`. You review and approve before anything gets built.

---

### `subagent-driven-development`

**Trigger:** "execute this plan", or chosen at the end of brainstorming

The recommended way to execute any plan. Creates an isolated git worktree, assigns one subagent per task, and runs them with automatic code review between steps. Each subagent gets precisely crafted context — never your session history — so they stay focused. You stay in the main thread and review when everything is done.

---

### `test-driven-development`

**Trigger:** any feature or bugfix

Enforces the red-green-refactor loop. The iron law: no production code without a failing test first. If you write the code before the test, delete it and start over. Claude won't skip this even when it "seems obvious" — that's exactly when the test matters most.

---

### `systematic-debugging`

**Trigger:** "this is broken", "test fails", any unexpected behaviour

Stops you from guessing. Forces root cause investigation before any fix is proposed. It traces the failure path, reads logs and stack traces, and identifies the actual cause. Symptom fixes are treated as failure — if the root cause hasn't been found, the bug hasn't been found.

---

### `verification-before-completion`

**Trigger:** before claiming anything is done

Runs the actual verification command and reads the full output before making any success claim. No assertions without evidence. Will never say "tests pass" without having run them in that message — if it can't prove it, it says so.

---

### `finishing-a-development-branch`

**Trigger:** "wrap up this branch", "let's merge"

When implementation is done and tests pass, walks you through wrapping up: create a PR, merge, or discard. Verifies the test suite first and won't proceed if anything is failing. Presents structured options so you stay in control of what happens to the branch.

---

### `executing-plans`

**Trigger:** "start implementing", when working without subagents

Takes a written plan file and executes it task by task with progress tracking. Raises concerns before starting if anything in the plan looks wrong. Hands off to `finishing-a-development-branch` when all tasks are done. Use `subagent-driven-development` instead when subagents are available — it gets significantly better results.

---

### `dispatching-parallel-agents`

**Trigger:** multiple independent problems at the same time

When you have multiple unrelated failures (e.g. 3 failing test files with different root causes), this dispatches one focused subagent per problem domain instead of investigating them sequentially. Each agent gets precisely crafted context — never your session history. Cuts investigation time dramatically on large breakages.

---

### `using-git-worktrees`

**Trigger:** before feature work that needs isolation

Sets up an isolated workspace before implementation starts. Detects if you're already in a worktree (common in this repo) and skips creation if so. Falls back to manual git worktree if no native tool is available. Ensures work never happens directly on the main checkout.

---

### `requesting-code-review`

**Trigger:** after completing a step, before merging, when stuck

Dispatches a dedicated reviewer subagent that looks at your changes with fresh eyes — it never sees your session history, only the code diff. This keeps it focused and unbiased.

Returns structured feedback in three tiers:
- **Critical** — fix immediately before continuing
- **Important** — fix before merging
- **Minor** — noted for later

**Example:**
> "I just finished implementing the port assignment logic. Let me request a code review before moving to the next step."

Claude gets the SHAs, dispatches the reviewer, brings back the findings. You fix Critical and Important issues, then continue.

---

### `receiving-code-review`

**Trigger:** when you get review feedback — from a subagent, a teammate, or a GitHub PR comment

Enforces technical rigor instead of blind agreement. Before implementing anything it verifies the feedback against the actual codebase. If the reviewer is wrong or missing context, it pushes back with technical reasoning. If any feedback is unclear it asks for clarification before touching a single line — partial understanding leads to wrong implementation.

**What it never does:** says "great point!" or "you're absolutely right!" — it just fixes things and shows it in the code.

**Example — inline PR comment:**
> A reviewer says "remove this legacy code." Claude checks whether anything still depends on it, finds it's used by a build target, and responds: "This is needed for backward compat on 10.15+. Remove if we're dropping pre-13 support — your call."

**Example — unclear batch feedback:**
> You say "fix items 1–6." Claude understands 1, 2, 3, 6 but not 4 and 5. Instead of guessing, it stops and asks: "Understand items 1, 2, 3, 6. Need clarification on 4 and 5 before implementing."

---

## Quick Reference

| Skill | Say this | What happens |
|---|---|---|
| `brainstorming` | "help me build X" | Asks questions, shapes the solution |
| `writing-plans` | "write a plan" | Step-by-step implementation plan |
| `subagent-driven-development` | "execute the plan" | Parallel agents, isolated worktree, auto review |
| `test-driven-development` | any feature/fix | Test first, always |
| `systematic-debugging` | "this is broken" | Root cause before any fix |
| `verification-before-completion` | before "it's done" | Evidence before claims |
| `finishing-a-development-branch` | "wrap up this branch" | PR / merge / discard options |
| `executing-plans` | "start implementing" | Sequential plan execution |
| `requesting-code-review` | "review my changes" | Reviewer subagent, structured feedback |
| `receiving-code-review` | after getting feedback | Verify before implementing, push back if wrong |
| `dispatching-parallel-agents` | multiple broken things | One agent per problem, run in parallel |
| `using-git-worktrees` | before feature work | Isolated workspace |
| `/finish` | "I'm done" | cs-fixer + tests + coverage gate |
