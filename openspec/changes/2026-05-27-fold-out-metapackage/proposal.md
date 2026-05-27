## Why

`composer create-project o3-shop/o3-shop` is broken: the project package
and `o3-shop/shop-metapackage-ce` both `replace
oxid-esales/oxideshop-metapackage-ce`, so Composer refuses to install
("cannot coexist"). This is the result of a half-finished metapackage
fold-in (archived change `automate-release-procedure`, issue
o3-shop/o3-shop#140): the fold-in moved the metapackage's dependency list
and `replace` clause into `o3-shop/o3-shop` (tags v1.6.1-RC1..RC3, which
installed), but v1.6.1-RC5 then re-added `require:
o3-shop/shop-metapackage-ce` on top of o3-shop's own `replace`, creating
the double-replace.

The fold-in's premise — "the metapackage has zero consumers" — measured
Packagist + code-search dependents but missed **deployed installs**: every
shop created from `o3-shop/o3-shop` ≤ v1.6.0 has a root `composer.json`
that `require`s `o3-shop/shop-metapackage-ce` by name. For those installs
to `composer update` forward, the metapackage must keep existing and
advancing. Archiving it (as the fold-in intended, issue #149) breaks
in-place upgrades.

## What Changes

Reverse the fold-in — restore the architecture that shipped in every
release ≤ v1.6.0:

- `o3-shop/shop-metapackage-ce` is the single **compilation**: it owns the
  full pinned dependency list and the `replace:
  oxid-esales/oxideshop-metapackage-ce` clause. It is NOT archived.
- `o3-shop/o3-shop` is a **thin project**: `require:
  { o3-shop/shop-metapackage-ce: <ver> }` only — no `replace`, no inlined
  deps.
- `bin/release` is **adjusted** (not deleted — it shipped with the
  fold-in) so `o3-shop/shop-metapackage-ce` is a permanent, first-class
  release node again. The fold-in special-casing in `FromSnapshotBuilder`
  (dropping the metapackage from `from_pin[]`; the "pre-fold-in
  indirection" one-time path) is removed; the metapackage is recursed into
  like any other node.
- The broken/abandoned `v1.6.1-RC*` tags are deleted and one clean
  coordinated RC is re-cut.

## Capabilities

### Modified Capabilities

- `release-graph-derivation`: the metapackage is a normal recursed node
  and a first-class release candidate; the pre-fold-in indirection
  requirement is removed; tier ordering reflects
  o3-shop → metapackage → shop-ce.
- `release-orchestration`: the tier-by-tier walk is now four tiers
  (leaves → shop-ce → metapackage → o3-shop); the `.next-bump`
  dist-exclude requirement is re-homed here.

### Removed Capabilities

- `metapackage-fold-in`: the whole capability is reversed.

### New Capabilities

- `metapackage-compilation`: documents the folded-out steady state — the
  metapackage owns the compilation + `replace`; o3-shop is thin; the
  metapackage stays published for upgrade compatibility.

## Impact

- **shop-ce**: `bin/release` tooling simplified (done on branch
  `169-fold-out-metapackage`); unit tests inverted/added; OpenSpec specs
  updated.
- **o3-shop repo** (`b-1.6`): `composer.json` slimmed to require only the
  metapackage; `replace` and inlined deps removed.
- **shop-metapackage-ce repo** (`b-1.6`): stays the fat compilation +
  `replace`; component pins refreshed; NOT archived.
- **Tags/releases**: broken `o3-shop` v1.6.1-RC* deleted; clean
  coordinated RC re-cut via the adjusted `bin/release`.
- **Issue #149**: rerouted from "archive shop-metapackage-ce" to a phased
  long-term-removal procedure.

## Long-term

The metapackage's only remaining load-bearing role is in-place upgrade
compatibility. Removing it for good (the eventual goal) requires migrating
that path off it — at a major-version boundary or via a documented
root-`require` swap, with a thin-bridge support window — not archival.
Tracked in #149.
