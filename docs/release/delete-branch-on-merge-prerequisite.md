# Release prerequisite: `delete_branch_on_merge` must be OFF

> Paste this into the "Create a Release" wiki page prerequisites
> (projects.wiki.tro.net).

## Policy

The release-line repos MUST keep GitHub's **"Automatically delete head
branches"** setting **off** (`delete_branch_on_merge = false`):

- `o3-shop/shop-ce`
- `o3-shop/shop-metapackage-ce`
- `o3-shop/o3-shop`

## Why

A merge-back PR whose head is the **release branch itself**
(`b-1.x → main`) deletes the release line branch when merged with
auto-delete on. The next release then walks the (now missing) `b-1.x`
and fails. This bit v1.6.1: merging merge-back PR #168 deleted
`shop-ce@b-1.6`.

Since o3-shop/o3-shop#241, `bin/release` opens merge-backs from a
tag-pinned `merge-back-vX.Y.Z` branch instead, which is safe to
auto-delete. Older and hand-opened merge-backs still use the release
branch as head, so keep auto-delete off.

## Verify (per repo)

```bash
gh api repos/o3-shop/shop-ce --jq .delete_branch_on_merge   # expect: false
```

## Fix (if it returns `true`)

```bash
gh api -X PATCH repos/o3-shop/shop-ce -F delete_branch_on_merge=false
```

## Automated enforcement

`bin/release` verifies this automatically on **final** releases (it runs
in `--dry-run` too, so you can preview before cutting). It aborts with
the fix command if any target repo has the setting on. Still verify
manually when **adding a new release-line repo**, since the gate checks
exactly the repos a given release touches.
