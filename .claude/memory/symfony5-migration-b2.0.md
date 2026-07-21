---
name: symfony5-migration-b2.0
description: b-2.0 branch — the Symfony 3.4->5.4 lib bump left code on 3.4 APIs; unit suite is now green after fixing 5 categories; remaining cleanup is PHPUnit-10/Integration-only
type: project
---

# Symfony 3.4 → 5.4 code migration (branch b-2.0-update-symmfony)

Commit `79a7d96` bumped the Symfony libs 3.4→5.4 but left application code and
tests using 3.4-era APIs. The full `tests/Unit` suite (9794 tests) is now GREEN on
the 5.4 stack and CI passes across PHP 7.4/8.0/8.1/8.2. Each fix unblocked hundreds
of tests and revealed the next category (the errors HALT the suite by poisoning the
compiled container cache, so only one surfaces per run — fix, clear
`source/tmp/container_cache.php`, rerun).

## Categories fixed (all pushed to b-2.0)
1. **Private services fetched by id** → add `public: true`. Symfony 5.4 services are
   private by default; `ContainerFactory::getInstance()->getContainer()->get(X)`
   throws ServiceNotFoundException. Fixed: `ContextInterface`, DBAL `Connection`,
   `oxid_esales.symfony.file_system`. See [[symfony5-private-services-container-get]].
2. **ServicesYamlValidator** iterated ALL compiled definitions incl. private `.lazy`
   console-command proxies → skip `!$definition->isPublic()`.
3. **Command::execute() must return int** (5.4 `Command::run()` TypeErrors on null) —
   5 module commands got `return 0;` (Theme cmds already correct).
4. **EventDispatcher::dispatch(event, name)** arg order (was `(name, event)`) — swapped
   at all prod call sites + flipped test `->with()` expectations. Prophecy doubles need
   `->dispatch(...)->willReturnArgument(0)` because `dispatch(): object` (prophecy
   returns null); PHPUnit createMock auto-generates a stdClass so it needs no stub.

## Remaining cleanup — NON-BLOCKING (not needed for CI green)
CI runs only `tests/Unit` on PHPUnit 9.6. These work today via shims/deprecation and
are future work (PHPUnit 10 readiness / removing BC shims):
- PHPUnit deprecated-not-removed: ~66 `setMethods()` (→ `onlyMethods`/`addMethods`),
  ~56 `withConsecutive()`, ~24 `$this->at()`, ~89 `assertContains` on strings, etc.
- Event base class: 23 events still `extends Symfony\Component\EventDispatcher\Event`
  (removed), kept alive by `source/Core/Compatibility/symfony_bc_aliases.php` class_alias
  to `Contracts\EventDispatcher\Event`. Also `ShopAwareEventDispatcher::dispatch()` swaps
  legacy arg order — that's why the 4 Integration tests using `(name, event)` still pass.
- Integration-only (not in CI): `TestCommand` fixture execute() returns null; 4 private
  services (`services_commands_provider`, `smarty.smarty_engine_factory`,
  `metadatamapper`, `flock_store_lock_factory`) fetched by Integration tests.

## Local test env gotchas (hit this session)
- The testing-library shop-install STRIPS files (bootstrap.php, base.php) from
  `vendor/o3-shop/testing-library` during a run — back up the dir and restore before
  each local run, or reinstall.
- The `db` host alias on the `o3shop-shared` network can be missing if `./docker.sh
  start` aborted (e.g. a mailpit :8025 port clash with another project) — reconnect with
  `docker network connect --alias db o3shop-shared o3shop-shop-ce-db-1`.
- Local MariaDB client needs the testing-library's `--skip-ssl` (do NOT apply CI's
  sed patch that strips it locally); CI strips it because its MySQL client rejects the flag.
