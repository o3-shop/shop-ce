---
name: Issue tracker lives in o3-shop/o3-shop, not shop-ce
description: GitHub issues are filed in the umbrella repo o3-shop/o3-shop; code lives in o3-shop/shop-ce and the per-module repos
type: reference
---

Issues are tracked centrally in the umbrella repo **`o3-shop/o3-shop`**, even when the fix lands in another repo. The code itself lives in `o3-shop/shop-ce` (the shop) and in per-module sibling repos (`o3-shop/tinymce-editor`, themes, etc.). PR numbers and commits referenced from an issue are usually in the *code* repo (e.g. shop-ce PR #167 fixed o3-shop/o3-shop#185).

Practical consequences:
- To view/comment/close an issue: `gh issue view|comment|close <N> --repo o3-shop/o3-shop`. `--repo o3-shop/shop-ce` returns "Could not resolve to an issue".
- Cross-references in commit messages / issues use `o3-shop/o3-shop#NNN` for issues and `o3-shop/shop-ce#NNN` (or bare `#NNN` within shop-ce) for PRs.
- When an issue says "the fix goes there, targeting branch b-1.6", the branch is in the *code* repo (shop-ce), not the umbrella repo.

Recurring gotcha: several issues assigned to @nlo-tronet turned out **already fixed** on `b-1.6` by the reporter's own merged PR (e.g. #106 image paths via #151; #185 install SQL via #167). Always check `git log -- <file>` and diff against `origin/b-1.6` before implementing — the maintainer asking "is this still open?" often means it isn't. See [[known-pitfalls]].
