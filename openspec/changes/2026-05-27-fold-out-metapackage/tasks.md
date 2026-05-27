# Tasks

## 1. Adjust bin/release (shop-ce) — DONE on branch 169-fold-out-metapackage

- [x] 1.1 Remove the metapackage special-casing in `FromSnapshotBuilder`
      (drop the `unset`, the pre-fold-in pre-harvest, the "never
      re-introduce" guards, the `METAPACKAGE_PACKAGE` const; rewrite the
      docblock).
- [x] 1.2 Reduce `FromSnapshot` to a single-arg constructor; drop the
      `usedPreFoldInIndirection` / `preFoldInMetapackageVersion` metadata.
- [x] 1.3 Remove the pre-fold-in log line from `DryRunPrinter`; reword the
      `ReleaseNotesAggregator` comment.
- [x] 1.4 Invert the "metapackage dropped" unit-test assertions to
      "metapackage is a first-class pin"; fix all `new FromSnapshot()`
      call sites.
- [x] 1.5 Add a first-write-wins regression test (replacing the deleted
      pre-harvest) and a folded-out cascade test
      (o3-shop → metapackage → shop-ce constraint bumps).
- [x] 1.6 `./docker.sh test --fast tests/Unit/Internal/ReleaseTooling/`
      green (287 tests); cs-fixer clean on touched files.

## 2. OpenSpec

- [x] 2.1 Modify `release-graph-derivation` and `release-orchestration`;
      remove `metapackage-fold-in`; add `metapackage-compilation`.

## 3. composer.json (sibling clones, branch b-1.6)

- [ ] 3.1 o3-shop: slim to `require: { o3-shop/shop-metapackage-ce }`;
      remove the `replace` block and the inlined deps.
- [ ] 3.2 shop-metapackage-ce: confirm fat compilation + `replace`;
      refresh component pins (shop-ce, themes, bundled modules).

## 4. Re-cut a clean coordinated RC

- [ ] 4.1 `bin/release --from <pre-fold-in v1.6.0-era tag> --to <clean RC>
      --dry-run`; review (metapackage is a first-class candidate, no
      indirection line).
- [ ] 4.2 Live cut: o3-shop@vX requires metapackage@vX.
- [ ] 4.3 Delete the broken/abandoned o3-shop `v1.6.1-RC*` tags + draft
      releases and the stray metapackage `v1.6.1-RC5` tag.
- [ ] 4.4 Verify `composer create-project o3-shop/o3-shop:<new RC>`
      resolves, and `composer update` from a metapackage-rooted install
      resolves forward.

## 5. Issue #149

- [ ] 5.1 Reroute from "archive shop-metapackage-ce" to the phased
      long-term-removal procedure; correct the "zero consumers" premise.
