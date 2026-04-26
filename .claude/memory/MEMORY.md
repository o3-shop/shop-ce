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
- [!] [Theme repos are external](architecture_theme-repos.md) — wave + o3-theme live in separate GitHub repos; their dirs in shop-ce are gitignored snapshots
- [!] [o3-theme migration](project_o3-theme-migration.md) — wave → o3-theme cutover before 2026-05-01; keep new storefront templates portable
