## Constraints

- **PHP 7.4 – 8.2 compatibility required** (`composer.json`: `^7.4 || ^8.0`). The following PHP 8.x features must NOT be used:
  - Constructor property promotion (PHP 8.0+)
  - Union types in signatures — use PHPDoc `@param`/`@return` instead (PHP 8.0+)
  - Named arguments (PHP 8.0+)
  - `match` expressions (PHP 8.0+)
  - Nullsafe operator `?->` (PHP 8.0+)
  - `readonly` properties (PHP 8.1+)
  - Enums (PHP 8.1+)
  - First-class callable syntax (PHP 8.1+)
- Typed properties (PHP 7.4+) are fine and should be used.

## Context

The current update check lives inline in `NavigationController::checkVersion()` (~30 lines). It:
- Calls `https://api.github.com/repos/o3-shop/o3-shop/releases/latest` with `file_get_contents`
- Compares the tag name against the current `ShopVersion::getVersion()`
- Appends a single plain-text `message` string to the `$aMessage` view array

**Limitations:**
- Installed modules are never checked — shops have no visibility into outdated extensions
- The plain message string is lost inside a generic message box, with no structure for module-level detail
- No way for an admin to re-run the check without logging out and back in
- HTTP call is synchronous at login; a slow/unavailable endpoint blocks the admin page load
- No timeout guard — `file_get_contents` uses the PHP default (no timeout)

**Module version data is available** via `ShopConfigurationDaoBridgeInterface` (already used in `ModuleList` admin controller): iterate `$shopConfiguration->getModuleConfigurations()`, each gives `getId()` and `getVersion()`.

## Goals / Non-Goals

**Goals:**
- Extract update check into a dedicated `UpdateCheckService` in `Internal/Framework/UpdateCheck/`
- Extend the payload to include active module ids and versions
- Send payload to the O3-Shop update check endpoint via `curl` (POST JSON, 5 s timeout)
- Return a structured result object: core update info + list of outdated modules with latest version and update URL
- Render structured update output in `home.tpl` (core notice + per-module rows)
- Add a "Check for updates" button in the admin navigation header that re-runs the check manually (no AJAX needed for v1 — a simple link that reloads `home.tpl` with a forced check)

**Non-Goals:**
- Building the server-side update check webservice (out of scope for CE)
- Automatic module updates / one-click upgrades
- Full AJAX popup for the manual check (deferred to v2 — adds JS complexity with minimal gain for now)
- Updating module records in the database (unnecessary here since we have no version_available DB column)

## Decisions

### D1 — New service in `Internal/Framework/UpdateCheck/`

**Decision:** Place `UpdateCheckService` under `source/Internal/Framework/UpdateCheck/` with a matching interface `UpdateCheckServiceInterface`.

**Rationale:** All framework services follow this pattern. It allows DI wiring, testability with a mock, and future module override. Placing it in `Internal` keeps it off-limits for direct module use (per namespace rules) but makes it injectable via the Symfony container.

**Alternative considered:** A static helper in `Core/`. Rejected — untestable and doesn't fit the DI model used throughout the codebase.

---

### D2 — HTTP transport: `curl` with 5 s timeout, not `file_get_contents`

**Decision:** Use `curl_init` / `curl_setopt_array` with `CURLOPT_TIMEOUT => 5` and `CURLOPT_RETURNTRANSFER => true`.

**Rationale:** `file_get_contents` has no timeout guard — a hanging endpoint blocks the page load. The reference implementation from the email uses `curl` with a 5 s timeout for the same reason.

**Alternative considered:** Symfony HTTP Client (already in project as a dev dependency). Rejected for now — introduces a runtime Composer dependency for a single call, and `curl` is a required system dependency of O3-Shop.

---

### D3 — Update check endpoint is a constant baked into the service

**Decision:** Define the endpoint URL as a class constant in `UpdateCheckService`, e.g. `const ENDPOINT = 'https://updates.o3-shop.com/check/v1'`.

**Rationale:** The endpoint is not a shop operator concern — it is part of the O3-Shop platform contract and should not be changeable at runtime. Baking it in as a constant makes the intent clear, prevents misconfiguration, and simplifies the code (no config lookup needed).

---

### D4 — Payload: shop_version + domain + active modules (id → version map)

**Decision:** Build payload as:
```json
{
  "shop_version": "v1.5.4",
  "domain": "https://myshop.example.com",
  "modules": {
    "my-module-id": "1.2.3",
    "other-module": "2.0.0"
  }
}
```
Module list collected via `ShopConfigurationDaoBridgeInterface` (same source as `ModuleList` admin controller). No license key (CE has none).

Two distinct uses of the module list:
- **Payload** (sent to the update endpoint): all configured modules — gives the server complete analytics coverage.
- **Update notices shown to the admin**: only modules that are currently active — inactive modules are excluded from the outdated list displayed in the UI.

**Rationale:** Sending all modules to the server maximises analytics value. Restricting update notices to active modules avoids noise — an admin cannot act on an update for a module they have disabled.

---

### D5 — Structured result DTO, not a raw array

**Decision:** `UpdateCheckService::check()` returns an `UpdateCheckResult` value object with:
- `bool $coreUpdateAvailable`
- `string $latestCoreVersion`
- `string $updateLink`
- `array $outdatedModules` — each item: `['id' => ..., 'latest_version' => ..., 'url' => ...]`

**Rationale:** A typed DTO is easier to test and clearer in the template than a raw associative array. It also survives a null/error state gracefully (all fields have safe defaults).

---

### D6 — Fallback to GitHub API when O3-Shop endpoint unavailable

**Decision:** If the POST to the update endpoint returns non-200 or times out, the service falls back to the existing GitHub Releases API for core-only version comparison. Module check is skipped silently.

**Rationale:** Existing behaviour is preserved. An unavailable update server must not break the admin login. The GitHub API fallback ensures the core version notice keeps working during an endpoint rollout.

---

### D7 — Manual "Check for updates" button in admin header: link, not AJAX

**Decision:** Add a "Check for updates" link in `header.tpl` that navigates the `basefrm` frame to `?cl=navigation&item=home.tpl&forceUpdateCheck=1`. When `forceUpdateCheck=1` is present, `NavigationController` bypasses the `blCheckForUpdates` config flag and always runs the check, passing results to `home.tpl`.

**Rationale:** Keeps implementation simple — no new JS, no extra endpoint. A full AJAX modal adds ~100 lines of JS for a rarely-used action. A link that reloads the home panel is sufficient for v1 and aligns with the existing admin frame pattern.

---

### D8 — Session caching of last check result (24 h TTL)

**Decision:** After a successful check, store the `UpdateCheckResult` serialised in the session under key `updateCheckResult` with a timestamp. On subsequent page loads, serve the cached result until 24 hours have elapsed, then re-check. `forceUpdateCheck=1` bypasses the cache.

**Rationale:** Avoids an HTTP call on every admin page load. Checking once per day is appropriate and matches the expected server-side cadence. Session storage requires no DB schema change.

## Risks / Trade-offs

**[Risk] O3-Shop update endpoint does not exist yet** → The service falls back to GitHub API for core, so the feature degrades gracefully. Module updates simply won't be reported until the server endpoint is live.

**[Risk] Serialising a DTO in the session** → `serialize()` / `unserialize()` on a simple value object is safe here (no resource handles, no circular refs). If the class changes between deploys the unserialise will fail; the catch resets the cache and re-fetches, which is acceptable.

**[Risk] `curl` not compiled into PHP** → Covered by O3-Shop's system requirements check (`SystemRequirements::checkCurl()`), so the service can rely on it being present. No additional guard needed.

**[Risk] Admin page load latency** → 5 s timeout + 24 h session cache means the worst case is a 5 s delay on first login per day. This is unchanged from a cold `file_get_contents` call; the timeout actually improves worst-case over the current code.

## Migration Plan

1. Deploy new `UpdateCheckService` + `UpdateCheckResult` classes — no side effects on deploy.
2. Update `NavigationController` to call the service and pass `$updateCheckResult` view data.
3. Update `home.tpl` to render structured output alongside the existing `$aMessage` block.
4. Add "Check for updates" button to `header.tpl`.
5. Add new translation keys to `admin/en/lang.php` and `admin/de/lang.php`.
6. No DB migrations, no config.inc.php changes required for existing installs.

**Rollback:** Reverting the `NavigationController` change and templates restores the old behaviour immediately. The new service classes can be left in place without harm.

## Open Questions

- **What is the actual O3-Shop update check endpoint URL?** (`https://updates.o3-shop.com/check/v1` is a placeholder in `UpdateCheckService::ENDPOINT` — needs confirmation from the team before release.)
- ~~**Should the module list include only active modules, or all configured modules?**~~ Resolved: payload includes all configured modules; update notices are shown for active modules only.
- ~~**Should the manual button appear even when `blCheckForUpdates` is `false`?**~~ Resolved: yes, the button always appears and always triggers a check regardless of the auto-check flag.
