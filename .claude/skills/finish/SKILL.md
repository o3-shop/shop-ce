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

> Note: this command can take 5–15 minutes on first run or after container rebuild.

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
