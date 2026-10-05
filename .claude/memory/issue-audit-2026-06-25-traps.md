---
name: Issue-audit traps — board-says-done ≠ code-says-done
description: Verified gotchas from the 2026-06-25 full open-issue audit; check these before acting on the named issues
type: reference
---

Full audit of all 47 open `o3-shop/o3-shop` issues on 2026-06-25 (against b-1.6 tip 404115e5). Durable, non-obvious findings:

- **shop-ce commits NO `composer.lock`** (it's an `oxideshop` library, declares `replace: oxid-esales/oxideshop-ce`). `composer audit` results in security issues (#164/#176) come from the assembled **`o3-shop` umbrella** install / RC builds, not this repo. Of the flagged Symfony CVE pkgs, only `symfony/yaml` is a *direct* shop-ce dep (still `~3.4 || ~4.0` → cannot reach fixed 5.4.52). cache/process/dom-crawler/polyfill live in umbrella / testing-library / tinymce-editor.
- **#123 is NOT resolved by the color-rename fixes (#180/#182/#183/#184/#186).** Those churned the same `initial_data.sql` but fixed *color* settings. The orphan *feature* rows #123 actually names (`blFooterShowLinks`, `blFooterShowNews`, several `bl_show*`) still sit in `source/Setup/Sql/initial_data.sql` and are absent from o3-theme `theme.php`. Project board "Done" is a trap here. #122 Phase 3 is the structural cure.
- **#189 is NOT resolved by #190.** b-1.6 tip is the #190 merge (`DeleteBranchOnMergeGate` — a pre-flight *guard*). #189 asks to stop using the release branch as PR head (throwaway `merge-back/vX.Y.Z` branch); Update 2026-10-04: o3-shop/o3-shop#241 implements the throwaway head (`merge-back-<tag>`, pinned to the tag); the old worktree `189-merge-back-throwaway-branch` no longer exists anywhere. #189's squash-merge concern is still open.
- **#81 premise is outdated.** Stack already runs `mariadb:10.11` (docker-compose.yml), not MySQL 8; the `docker/data` certs it mentions don't exist. Remaining real scope = bump to MariaDB 11 and/or a cross-engine test matrix.
- **Cross-repo: theme/editor fixes land in sibling repos.** #173 (wave build) is DONE in the wave-theme repo; #24 (TinyMCE link mangling) is DONE in `o3-shop/tinymce-editor` v1.1.0 (`relative_urls:false`+`remove_script_host:true`, same fix as #151). shop-ce only shows the consuming side — don't close from shop-ce evidence alone; re-verify in the satellite repo / RC.
- **Already fixed in shop-ce, close candidates:** #28 (setup doc deep-links, commits d49e1ff0/822863d6). #13 superseded by the ≥90% coverage gate (#133/#137). #134 planning deliverable (PHP-version report) is complete.
- **#33 (Smarty separation) looks undone but isn't greenfield:** `source/Internal/Framework/Templating/` already defines `TemplateEngineInterface` + Smarty bridge; only legacy direct callers remain (`UtilsView`, `Email`, `oxfunctions.php`). #34 (Twig) shares that seam.
- **#113 (Altcha) has a prepared DI seam from #99:** `RevocationAntiSpamServiceInterface` → `NoopAntiSpamService`, services.yaml literally says "issue #113 will rebind this". Themes have empty `captcha_form` blocks. Supersedes external captcha-module (#191).
- **Empty-body placeholder issues** (transferred from Mantis, no spec): #43/#44/#45/#46/#48 and #162/#165. #165 is a content-free dup of the dep-update theme.

## Triage applied 2026-06-25
- **Closed:** #13 (superseded by coverage gate), #24 (fixed in tinymce-editor v1.0.0+). #28 commented + assigned to nlo-tronet for verify (kept open). #134/#173 kept open with status comments.
- **Epic #165** repurposed from empty placeholder → "Dependency & security renovation (epic)"; children #98, #18, #9, #164; #164 → #176 (had to detach #164 from closed release #177 and invert the pre-existing #164→#165 link). **#40** → #39 (admin module-mgmt epic).
- **Sub-issues created:** #46 → #198–#202; #152 → #203–#206; #113 → #207–#210; #76 → #211–#212.
- Status/re-scope comments posted on the rest. Left as product decisions (untouched): #31/#32/#42 packaging, #35/#48 API tech, #192 auto-migration design.

See [[issue-tracker-umbrella-repo]] and [[known-pitfalls]].
