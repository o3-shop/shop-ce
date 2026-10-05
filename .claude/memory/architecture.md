---
name: Architecture
description: Key architectural decisions, DI wiring, module system, Symfony integration
type: reference
---

## Dependency Injection
- Symfony DI container
- Service definitions: YAML files inside `source/Internal/`
- Container is compiled — after adding a new service, clear `source/tmp/`
- Entry point for container: `source/bootstrap.php`

## Module System
- Modules live in `source/modules/`
- Modules extend shop classes via OxidEsales chain inheritance
- Modules must NOT depend on `source/Internal/` (considered private API)
- Module configuration: `metadata.php` in each module root

## CLI
- Symfony Console via `bin/oe-console`
- Add commands by registering them in the DI container with the `console.command` tag

## Email
- PHPMailer via `\OxidEsales\EshopCommunity\Core\Mailer` (may live in vendor — verify with `find vendor -name "Mailer.php"` before editing)
- Test emails caught by Mailpit at http://localhost:8025

## Logging
- Monolog v2, PSR-3 compatible
- Logger available via DI: `\Psr\Log\LoggerInterface`

## Configuration
- Runtime config: `.env` (copied from `.env.example` on first run)
- Docker-specific env: `docker/.env` (auto-generated from `.env.example`)
- Shop config in DB — editable via Admin panel or `source/config.inc.php`
