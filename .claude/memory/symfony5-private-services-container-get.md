---
name: symfony5-private-services-container-get
description: Symfony 3.4->5.4 made DI services private-by-default; any service fetched via container->get() must be explicitly public:true or it throws ServiceNotFoundException
type: reference
---

# Symfony 5.4 migration: services are private by default

Symfony 3.4 registered services **public** by default; Symfony 4.0+ (so the
`b-2.0` 5.4 stack) makes them **private** by default. A private service can be
injected but **cannot** be fetched with `$container->get(SomeInterface::class)` —
that throws:
`ServiceNotFoundException: The "..." service or alias has been removed or inlined
when the container was compiled. You should either make it public, or stop using
the container directly and use dependency injection instead.`

## The bug it caused (2026-07-21, `b-2.0-update-symmfony`)

`UserTest::testGetUserNotAdmin` errored because `tests/Unit/Internal/ContextStub.php`
(lines 48 and 202) seeds itself via
`ContainerFactory::getInstance()->getContainer()->get(ContextInterface::class)`.
`source/Internal/Transition/Utility/services.yaml` had `_defaults: public: false`
and never overrode it for `ContextInterface`, while the sibling
`BasicContextInterface` in `bootstrap-services.yaml` was correctly `public: true`.
The migration made services private and missed `ContextInterface`.

Fetched via `->get()`: **tests** do it (26 files, incl. ContextStub); **production
source does it 0 times**. So the fix is test-enabling but harmless in prod.

## Fix / rule

Add `public: true` to the definition, mirroring `BasicContextInterface`:

```yaml
  OxidEsales\...\ContextInterface:
    class: OxidEsales\...\Context
    public: true
```

When a `ServiceNotFoundException` says "removed or inlined when the container was
compiled", it's almost always this: a service pulled from the container by id that
the migration left private. Prefer DI where you can; where legacy/test code must
pull by id (the OXID `ContainerFactory::getInstance()->getContainer()->get(...)`
bridge pattern), mark that service `public: true`. Remember the compiled
`source/tmp/container_cache.php` must be cleared for a services.yaml change to take
effect. See [[console-commands-and-php-floor]] and [[architecture]].

## Variant: compiler-added private services (`.lazy` command proxies)

`ServicesYamlValidator` (module activation, `Internal/Framework/Module/Setup/Validator`)
makes every definition `public` then `compile()`s, then `->get()`s **every** definition
to check a module's services.yaml is loadable. But Symfony's `AddConsoleCommandPass`
adds private lazy command proxies (ids like `.oxid_esales.command.*.lazy`, leading dot)
*during* compile — after the setPublic loop — so `->get()` on them throws. Fix: skip
non-public definitions in the check loop (`if (!$definition->isPublic()) continue;`).
This affects real module activation on 5.4, not just tests (surfaced via
`ModuleInstallerCoverageTest::testActivateSucceedsForRegisteredModule`).
