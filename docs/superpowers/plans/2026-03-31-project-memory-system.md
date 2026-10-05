# Project Memory System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** All agents in this repo automatically receive critical memory (pitfalls, conventions) at session start, get nudged to capture learnings mid-task, and the memory grows intelligently without flooding context.

**Architecture:** A two-tier memory system — `[!]`-annotated entries in `MEMORY.md` trigger full content load at session start via a Python hook script; unmarked entries are index-only and loaded on demand. Mid-task capture is enforced via a PostToolUse hook on test runs and a CLAUDE.md rule.

**Tech Stack:** Python 3 (hook script), JSON (settings.json), Markdown (memory files), Claude Code hooks

---

## File Map

| File | Action |
|---|---|
| `.claude/hooks/load-memory.py` | Create — Python hook script that reads MEMORY.md, finds `[!]` entries, loads them in full |
| `.claude/memory/MEMORY.md` | Modify — add HTML comment header explaining the `[!]` convention + annotate critical files |
| `.claude/settings.json` | Modify — replace SessionStart hook command; add PostToolUse Bash hook for test reminders |
| `CLAUDE.md` | Modify — add mid-task capture rule under Agent Memory section |

---

### Task 1: Create the memory loader hook script

**Files:**
- Create: `.claude/hooks/load-memory.py`

- [ ] **Step 1: Create `.claude/hooks/` directory and write the script**

Create `.claude/hooks/load-memory.py` with this exact content:

```python
#!/usr/bin/env python3
"""
SessionStart hook: loads MEMORY.md index + full content of all [!]-annotated files.
Run: python3 .claude/hooks/load-memory.py
Output: JSON for Claude Code hookSpecificOutput
"""
import json
import os
import re
import subprocess
import sys


def main():
    try:
        root = subprocess.check_output(
            ["git", "rev-parse", "--show-toplevel"],
            stderr=subprocess.DEVNULL,
        ).decode().strip()
    except subprocess.CalledProcessError:
        # Not in a git repo — no-op
        sys.exit(0)

    mem_dir = os.path.join(root, ".claude", "memory")
    index_path = os.path.join(mem_dir, "MEMORY.md")

    if not os.path.isfile(index_path):
        sys.exit(0)

    with open(index_path) as f:
        index = f.read()

    parts = ["## Shared Agent Memory\n\n### Index\n" + index]

    for m in re.finditer(r"^\- \[!\] \[.*?\]\((.*?)\)", index, re.MULTILINE):
        full_path = os.path.join(mem_dir, m.group(1))
        if os.path.isfile(full_path):
            with open(full_path) as f:
                parts.append("### " + os.path.basename(full_path) + "\n" + f.read())

    content = "\n".join(parts)
    print(json.dumps({
        "hookSpecificOutput": {
            "hookEventName": "SessionStart",
            "additionalContext": content,
        }
    }))


if __name__ == "__main__":
    main()
```

- [ ] **Step 2: Verify the script runs and produces valid JSON**

Run from the repo root:

```bash
python3 .claude/hooks/load-memory.py | python3 -m json.tool
```

Expected: pretty-printed JSON with `hookSpecificOutput.additionalContext` containing the index content. No errors.

- [ ] **Step 3: Verify the script handles missing MEMORY.md gracefully**

```bash
cd /tmp && python3 "$(git -C ~/o3/shop-ce rev-parse --show-toplevel)/.claude/hooks/load-memory.py"; echo "exit: $?"
```

Expected: exits 0, no output (graceful no-op — `/tmp` is not a git repo).

- [ ] **Step 4: Commit**

```bash
git add .claude/hooks/load-memory.py
git commit -m "feat: add memory loader hook script"
```

---

### Task 2: Update MEMORY.md with header and [!] annotations

**Files:**
- Modify: `.claude/memory/MEMORY.md`

- [ ] **Step 1: Replace MEMORY.md content**

Replace the entire file with:

```markdown
# Project Memory Index

<!--
HOW TO CLASSIFY NEW ENTRIES:
  [!] CRITICAL = always loaded at session start. Use for:
      - things that will cause bugs if ignored (pitfalls, gotchas)
      - conventions every agent must follow before touching code
  (no prefix) REFERENCE = loaded on demand. Use for:
      - how things work (architecture, patterns)
      - lookup knowledge only needed for specific tasks
When a [!] file grows large: split into sub-files, keep a short summary as [!],
add sub-files as reference entries.
-->

Shared memory for all Claude agents working in this repository. Read this first, then open relevant files for detail.

- [!] [Known Pitfalls](known-pitfalls.md) — bugs and mistakes already encountered in this repo
- [!] [Project Conventions](project-conventions.md) — PSR-12, DBAL patterns, namespace rules, Smarty usage
- [Architecture](architecture.md) — DI wiring, module system, key architectural decisions
- [Testing Patterns](testing-patterns.md) — PHPUnit setup, mocking, test structure conventions
```

- [ ] **Step 2: Verify the [!] regex matches correctly**

```bash
python3 -c "
import re
index = open('.claude/memory/MEMORY.md').read()
matches = re.findall(r'^\- \[!\] \[.*?\]\((.*?)\)', index, re.MULTILINE)
print('Critical files found:', matches)
"
```

Expected output:
```
Critical files found: ['known-pitfalls.md', 'project-conventions.md']
```

- [ ] **Step 3: Re-run the hook script and confirm critical content is now included**

```bash
python3 .claude/hooks/load-memory.py | python3 -c "
import sys, json
data = json.load(sys.stdin)
ctx = data['hookSpecificOutput']['additionalContext']
print('Contains known-pitfalls content:', 'Bot/Guest Request Handling' in ctx)
print('Contains project-conventions content:', 'PSR-12' in ctx)
print('Does NOT contain architecture body:', ctx.count('Symfony DI container') == 0)
"
```

Expected:
```
Contains known-pitfalls content: True
Contains project-conventions content: True
Does NOT contain architecture body: True
```

- [ ] **Step 4: Commit**

```bash
git add .claude/memory/MEMORY.md
git commit -m "feat: add [!] tier annotations and header to MEMORY.md"
```

---

### Task 3: Update settings.json — SessionStart hook

**Files:**
- Modify: `.claude/settings.json`

- [ ] **Step 1: Read the current settings.json**

Read `.claude/settings.json` to see the current SessionStart hook command before editing.

- [ ] **Step 2: Replace the SessionStart hook command**

In `.claude/settings.json`, replace the existing `SessionStart` hook `command` value with:

```
python3 \"$(git rev-parse --show-toplevel)/.claude/hooks/load-memory.py\"
```

The full `SessionStart` entry should look like:

```json
"SessionStart": [
  {
    "hooks": [
      {
        "type": "command",
        "command": "python3 \"$(git rev-parse --show-toplevel)/.claude/hooks/load-memory.py\"",
        "statusMessage": "Loading shared agent memory..."
      }
    ]
  }
]
```

- [ ] **Step 3: Verify settings.json is valid JSON**

```bash
python3 -m json.tool .claude/settings.json > /dev/null && echo "Valid JSON"
```

Expected: `Valid JSON`

- [ ] **Step 4: Commit**

```bash
git add .claude/settings.json
git commit -m "feat: update SessionStart hook to use load-memory.py"
```

---

### Task 4: Update settings.json — PostToolUse test reminder hook

**Files:**
- Modify: `.claude/settings.json`

- [ ] **Step 1: Add a PostToolUse hook entry for Bash test runs**

In `.claude/settings.json`, add a second entry to the `PostToolUse` array (after the existing cs-fixer entry):

```json
{
  "matcher": "Bash",
  "hooks": [
    {
      "type": "command",
      "command": "CMD=$(echo \"$CLAUDE_TOOL_INPUT\" | python3 -c \"import sys,json; d=json.load(sys.stdin); print(d.get('command',''))\" 2>/dev/null); if echo \"$CMD\" | grep -q 'docker\\.sh test'; then echo 'MEMORY REMINDER: If this test run revealed anything non-obvious (unexpected failure, surprising behaviour, env quirk) — capture it in .claude/memory/ before continuing.'; fi"
    }
  ]
}
```

The full `PostToolUse` section after the change:

```json
"PostToolUse": [
  {
    "matcher": "Edit|Write",
    "hooks": [
      {
        "type": "command",
        "command": "FILE=$(echo \"$CLAUDE_TOOL_INPUT\" | python3 -c \"import sys,json; d=json.load(sys.stdin); print(d.get('file_path',''))\"); if [[ \"$FILE\" == *.php ]] && docker ps --format '{{.Names}}' | grep -q '^o3shop-app$'; then cd $(git rev-parse --show-toplevel) && ./docker.sh cs-fixer || true; fi"
      }
    ]
  },
  {
    "matcher": "Bash",
    "hooks": [
      {
        "type": "command",
        "command": "CMD=$(echo \"$CLAUDE_TOOL_INPUT\" | python3 -c \"import sys,json; d=json.load(sys.stdin); print(d.get('command',''))\" 2>/dev/null); if echo \"$CMD\" | grep -q 'docker\\.sh test'; then echo 'MEMORY REMINDER: If this test run revealed anything non-obvious (unexpected failure, surprising behaviour, env quirk) — capture it in .claude/memory/ before continuing.'; fi"
      }
    ]
  }
]
```

- [ ] **Step 2: Verify settings.json is still valid JSON**

```bash
python3 -m json.tool .claude/settings.json > /dev/null && echo "Valid JSON"
```

Expected: `Valid JSON`

- [ ] **Step 3: Smoke-test the reminder logic in isolation**

```bash
CMD="./docker.sh test --fast tests/Unit/Foo.php"
if echo "$CMD" | grep -q 'docker\.sh test'; then echo "REMINDER fires: yes"; else echo "REMINDER fires: no"; fi

CMD="git status"
if echo "$CMD" | grep -q 'docker\.sh test'; then echo "REMINDER fires: yes"; else echo "REMINDER fires: no"; fi
```

Expected:
```
REMINDER fires: yes
REMINDER fires: no
```

- [ ] **Step 4: Commit**

```bash
git add .claude/settings.json
git commit -m "feat: add PostToolUse memory reminder on test runs"
```

---

### Task 5: Update CLAUDE.md — mid-task capture rule

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Add the mid-task capture rule**

In `CLAUDE.md`, find the `## Agent Memory` section. After the existing numbered list (steps 1–3), add:

```markdown
**Mid-task capture:** If you encounter something surprising, non-obvious, or that contradicts your assumptions during a task — write it to `.claude/memory/` immediately. Don't wait for `/finish`.
```

The full Agent Memory section should look like:

```markdown
## Agent Memory

This repo has a shared memory system at `.claude/memory/`. All agents working here contribute to it.

**Before finishing any task:**
1. Read `.claude/memory/MEMORY.md` (the index)
2. If you learned something non-obvious during your work, find the relevant memory file and append it
3. If nothing fits, create a new memory file and add it to `MEMORY.md`

**Mid-task capture:** If you encounter something surprising, non-obvious, or that contradicts your assumptions during a task — write it to `.claude/memory/` immediately. Don't wait for `/finish`.

Memory files use frontmatter:
...
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: add mid-task memory capture rule to CLAUDE.md"
```

---

### Task 6: End-to-end verification

- [ ] **Step 1: Run the full hook script and inspect output**

```bash
python3 .claude/hooks/load-memory.py | python3 -c "
import sys, json
data = json.load(sys.stdin)
ctx = data['hookSpecificOutput']['additionalContext']
lines = ctx.split('\n')
print(f'Total lines in context: {len(lines)}')
print('--- First 10 lines ---')
print('\n'.join(lines[:10]))
print('--- Contains critical files ---')
print('known-pitfalls.md:', 'Bot/Guest Request Handling' in ctx)
print('project-conventions.md:', 'QueryBuilder' in ctx)
print('--- Reference files NOT expanded ---')
print('architecture.md body absent:', 'Symfony DI container' not in ctx)
print('testing-patterns.md body absent:', 'PHPUnit' not in ctx)
"
```

Expected: critical file content present, reference file body absent.

- [ ] **Step 2: Verify all JSON in settings.json is valid and hooks are wired**

```bash
python3 -c "
import json
with open('.claude/settings.json') as f:
    s = json.load(f)
ss_hooks = s['hooks']['SessionStart'][0]['hooks']
ptu_hooks = s['hooks']['PostToolUse']
print('SessionStart hooks:', len(ss_hooks))
print('SessionStart command contains load-memory.py:', 'load-memory.py' in ss_hooks[0]['command'])
print('PostToolUse entries:', len(ptu_hooks))
bash_hook = next((h for h in ptu_hooks if h.get('matcher') == 'Bash'), None)
print('Bash PostToolUse hook present:', bash_hook is not None)
"
```

Expected:
```
SessionStart hooks: 1
SessionStart command contains load-memory.py: True
PostToolUse entries: 2
Bash PostToolUse hook present: True
```

- [ ] **Step 3: Confirm CLAUDE.md has the mid-task rule**

```bash
grep -n "Mid-task capture" CLAUDE.md
```

Expected: a line number with the mid-task capture text.

- [ ] **Step 4: Final commit (if any loose files)**

```bash
git status
# If clean, nothing to do. If not, stage and commit any remaining changes.
```
