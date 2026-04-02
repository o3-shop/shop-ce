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
        if not os.path.abspath(full_path).startswith(os.path.abspath(mem_dir) + os.sep):
            continue
        if os.path.isfile(full_path):
            try:
                with open(full_path) as f:
                    parts.append("### " + os.path.basename(full_path) + "\n" + f.read())
            except OSError as e:
                print(f"load-memory: could not read {full_path}: {e}", file=sys.stderr)

    content = "\n\n".join(parts)
    print(json.dumps({
        "hookSpecificOutput": {
            "hookEventName": "SessionStart",
            "additionalContext": content,
        }
    }))


if __name__ == "__main__":
    main()
