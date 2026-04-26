---
name: Graceful degradation over fail-fast on missing assets
description: User-facing flows must not break when an updater forgot a template/file/key — fall back, log, don't block
type: feedback
---

When an asset that an end-user-facing flow depends on is missing at runtime — a template file, a translation key, a CMS snippet, a config row, a static asset — the production code MUST gracefully degrade and MUST NOT block the user. Operator updates routinely lag behind core merges; a consumer trying to exercise a legitimate function (place an order, submit a revocation, reset a password, etc.) MUST NOT be turned away because someone forgot to copy a `.tpl` or update a lang file.

**Concrete rule for new code:**

1. **Try the requested resource first** — render in the requested language, with the requested template, with the configured value.
2. **Fall back** when it's missing — to the shop's default language, to a sibling template, to a sane built-in default. Cascade until something works.
3. **Skip cleanly** if every fallback also fails — render a minimally-helpful page or email, never a 500/crash.
4. **Emit one `ERROR`-level log line** at each fall-through with: which resource was missing, which fallback was used (or "skipped"), and a one-line remediation hint. Per CLAUDE.md logging standards: `__METHOD__ . ' - '` prefix, full sentence, English, no line breaks; structured context in the data array.
5. **Do NOT add fail-fast asserts in setup / migration / install scripts** for missing front-of-house assets. The production-incident cost of a wrong assertion is much higher than the cost of an operator seeing an ERROR log line and copying a missing file.

**Why:** updaters usually forget to update templates and lang files; that's the steady-state behaviour, not an exceptional case. Captured during scoping of issue #99 (electronic revocation) on 2026-04-26 in response to a fail-fast template-presence assertion that would have blocked legitimate revocations.

**How to apply:** when you write code that loads a template, lang key, content snippet, config value, or static asset on a user-facing path, the first question is "what does this do if the asset is missing?" — and the answer must be "log and continue", never "abort and 500". Apply equally in storefront, admin, mailer, and migration paths *unless* the missing asset would corrupt data (in which case fail-fast at write time, but still let the user-facing read path degrade).

**Counter-cases (where fail-fast IS correct):**
- Missing column on an INSERT (would corrupt data — fail).
- Missing required env var at boot (the process can't function — fail).
- Schema mismatch detected at migration time (would silently break later — fail).
- Anything that, if proceeded with, would write inconsistent data.

The rule is about *read-time* asset resolution on user-facing flows, not about data integrity guards.
