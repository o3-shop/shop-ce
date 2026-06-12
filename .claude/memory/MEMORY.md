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
- [!] [Graceful degradation over fail-fast](feedback_graceful-degradation.md) — user-facing flows must not break on missing templates / lang keys / assets — fall back, log, don't block
- [!] [Form input must survive errors](feedback_form-input-preservation.md) — when a form submission is rejected, re-render with submitted values — never make the user re-type
- [!] [Git / PR / merge workflow](feedback_git-pr-workflow.md) — wait-for-instruction cadence; never auto-push; squash-merge default; cs-fixer before every commit
- [Architecture](architecture.md) — DI wiring, module system, key architectural decisions
- [Testing Patterns](testing-patterns.md) — PHPUnit setup, mocking, test structure conventions
- [!] [Theme repos are external](architecture_theme-repos.md) — wave + o3-theme live in separate GitHub repos; their dirs in shop-ce are gitignored snapshots
- [!] [o3-theme migration](project_o3-theme-migration.md) — wave → o3-theme cutover before 2026-05-01; keep new storefront templates portable
- [o3-theme npm audit](project_o3-theme-dep-audit.md) — pre-existing brace-expansion vulnerability blocks test-all-coverage; use test --fast for PHP-only changes
- [bin/release intermediate-node re-tag gap](release-tooling-intermediate-node-retag-gap.md) — resolver "reuses" an intermediate fat node (the metapackage) while bumping its child pin → orphaned edit; force a re-tag during the fold-out cut (#169)
- [!] [cs-fixer dirties nested clones](cs-fixer-pollutes-nested-clones.md) — ./docker.sh cs-fixer reformats testing-library/themes/demodata clones → aborts bin/release pre-flight; clean them before a cut
- [!] [ExitHandler interface-guard bug](shop-ce-exithandler-interface-guard-bug.md) — bootstrap.php uses class_exists() on the ExitHandlerInterface (always false) → fresh-install Setup redirect dies in a DB-error loop; fix: interface_exists()
- [tinymce-editor module](tinymce-editor-module.md) — admin WYSIWYG editor is a sibling repo; vendors TinyMCE in out/, no test suite; TinyMCE 7 upgrade notes (#194)
