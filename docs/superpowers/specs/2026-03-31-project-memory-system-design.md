# Project Memory System Design

**Date:** 2026-03-31
**Status:** Approved

## Goal

All agents working in this repo — on any machine — automatically have the same working knowledge: what works, what breaks, what conventions to follow, and what's been learned. This knowledge grows over time and is shared via git.

## Current State

- `.claude/memory/` is git-tracked (cross-machine sharing already works)
- `SessionStart` hook injects only the `MEMORY.md` index (titles + one-liners)
- Individual memory file content is NOT auto-loaded — agents must manually read files
- `/finish` skill prompts memory updates at task end

## Design

### 1. Two-Tier Memory

Memory files are classified as **critical** or **reference** using a `[!]` annotation in `MEMORY.md`:

```markdown
- [!] [Known Pitfalls](known-pitfalls.md) — always loaded (pitfalls, gotchas)
- [Architecture](architecture.md) — loaded on demand
```

**Critical `[!]`** — loaded in full at every session start. Use for:
- Things that will cause bugs if ignored (pitfalls, gotchas)
- Conventions every agent must follow before touching any code

**Reference (no prefix)** — index description only at session start. Agents `Read` them when the task demands it.

**Rule of thumb for new entries:** if ignoring it would cause a bug or style violation → `[!]`. If it's lookup knowledge → no prefix.

### 2. MEMORY.md Header

The classification rules are embedded as an HTML comment at the top of `MEMORY.md`, visible to any agent reading the raw file:

```markdown
<!--
CRITICAL [!] = always loaded at session start. Use for:
  - things that will cause bugs if ignored (pitfalls, gotchas)
  - conventions every agent must follow before touching code
REFERENCE (no prefix) = loaded on demand. Use for:
  - how things work (architecture, patterns)
  - lookup knowledge only needed for specific tasks
When a [!] file grows large, split it and only mark the summary [!].
-->
```

### 3. SessionStart Hook

Replaces the current hook (which only cats `MEMORY.md`) with:

1. Read `MEMORY.md`
2. Extract filenames from all `[!]` lines
3. Load full content of those files
4. Inject: index + critical file contents into session context

Shell logic (runs inside the existing `SessionStart` hook command):

```bash
python3 -c "
import os, re, json, subprocess

root = subprocess.check_output(['git', 'rev-parse', '--show-toplevel']).decode().strip()
mem_dir = os.path.join(root, '.claude', 'memory')
index_path = os.path.join(mem_dir, 'MEMORY.md')

with open(index_path) as f:
    index = f.read()

parts = ['## Shared Agent Memory\n\n### Index\n' + index]

for m in re.finditer(r'^\- \[!\] \[.*?\]\((.*?)\)', index, re.MULTILINE):
    full_path = os.path.join(mem_dir, m.group(1))
    if os.path.isfile(full_path):
        with open(full_path) as f:
            parts.append('### ' + os.path.basename(full_path) + '\n' + f.read())

content = '\n'.join(parts)
print(json.dumps({'hookSpecificOutput': {'hookEventName': 'SessionStart', 'additionalContext': content}}))
"
```

Using Python avoids the bash subshell variable scope issue and handles JSON escaping correctly.

### 4. Mid-Task Capture

Three capture moments, layered:

| When | Mechanism |
|---|---|
| Something surprising happens mid-task | CLAUDE.md instruction — write immediately, don't wait |
| After a test run | `PostToolUse` hook on `docker.sh test` commands — injects reminder |
| End of task | `/finish` skill — full memory review gate |

**CLAUDE.md addition** (under Agent Memory section):

> When you encounter something surprising, non-obvious, or that contradicts your assumptions mid-task — write it to `.claude/memory/` immediately. Don't wait for `/finish`.

**PostToolUse hook** — fires when bash command matches `docker.sh test`:

```
⚠ If this test run revealed anything non-obvious (unexpected failure, surprising behaviour, env quirk) — capture it in .claude/memory/ before continuing.
```

### 5. Scaling

When a `[!]` file grows large:
1. Split into focused sub-files (e.g. `pitfalls-db.md`, `pitfalls-session.md`)
2. Keep a short summary file marked `[!]`
3. Sub-files are reference tier (no `[!]`) — agents load them when the topic is relevant

## What Doesn't Change

- Memory files stay in `.claude/memory/` (git-tracked, cross-machine via git pull)
- File frontmatter format unchanged
- `/finish` skill unchanged
- Agents still write memory files using the existing two-step process (write file + update index)

## Files Changed

1. `.claude/memory/MEMORY.md` — add HTML comment header + `[!]` annotations
2. `.claude/settings.json` — replace `SessionStart` hook + add `PostToolUse` test hook
3. `CLAUDE.md` — add mid-task capture instruction to Agent Memory section
