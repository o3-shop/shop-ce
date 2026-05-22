---
name: push-pr
description: Stop docker, remove worktree, rename branch (strip worktree- prefix), push and open a GitHub PR with the issue title and a standard body.
---

# Push & PR

> **Only run this skill when the user explicitly types `/push-pr`.** Never invoke it automatically.

Use this skill when implementation is done and you are ready to push your worktree branch and open a pull request.

## Steps

### 1. Stop your Docker environment

Run from inside the worktree:

```bash
./docker.sh stop
```

### 2. Collect branch and issue info

```bash
WORKTREE_PATH=$(git rev-parse --show-toplevel)
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
```

Strip the `worktree-` prefix to get the target branch name:

```bash
# e.g. worktree-157-short-description → 157-short-description
TARGET_BRANCH="${CURRENT_BRANCH#worktree-}"
```

Extract the issue number from the branch name (leading digits):

```bash
ISSUE_NUMBER=$(echo "$TARGET_BRANCH" | grep -oE '^[0-9]+')
```

### 3. Fetch the issue title from GitHub

```bash
ISSUE_TITLE=$(gh issue view "$ISSUE_NUMBER" --repo o3-shop/o3-shop --json title --jq '.title')
```

If the issue can't be fetched (e.g. no network), ask the user for the PR title instead.

### 4. Rename the branch

```bash
git branch -m "$CURRENT_BRANCH" "$TARGET_BRANCH"
```

### 5. Push

```bash
git push -u origin "$TARGET_BRANCH"
```

### 6. Create the PR

Fill in the Summary bullets from `git log --oneline origin/b-1.6..HEAD`. Fill in the test plan from what you actually ran.

```bash
gh pr create \
  --title "$ISSUE_TITLE" \
  --base b-1.6 \
  --body "$(cat <<EOF
Closes #${ISSUE_NUMBER}

## Summary

- <bullet summarising what changed>
- <bullet summarising why>

## Test plan

- [ ] \`./docker.sh test --fast <relevant test file>\` — all tests pass
- [ ] \`./docker.sh cs-fixer\` — no changes
- [ ] Manual verification: <describe the scenario>

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

### 7. Remove the worktree

Move to the main repo root, then remove:

```bash
MAIN_ROOT=$(git -C "$(git rev-parse --git-common-dir)/.." rev-parse --show-toplevel)
cd "$MAIN_ROOT"
git worktree remove "$WORKTREE_PATH"
git worktree prune
```

### 8. Report

Print the PR URL and confirm the worktree is gone.
