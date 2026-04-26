## Why

The current admin update check only compares the core shop version against the latest GitHub release, showing a plain text message — it does not check installed modules for available updates and provides no detail about what needs updating. As shops grow, knowing which modules are outdated is as important as knowing the core version is outdated.

## What Changes

- Replace the ad-hoc `file_get_contents` GitHub API call with a dedicated `UpdateCheckService` class that POSTs a structured payload (shop version, domain, active modules + their versions) to the O3-Shop update check endpoint.
- The service returns structured data: core update availability + a list of outdated modules with their latest available versions and update links.
- `NavigationController::doStartUpChecks()` uses the service and passes the result as view data so the template can render a rich update notice (not just a plain string).
- A new admin template partial renders the update summary: core version status + per-module update rows.
- A "Check for updates" button is added to the admin header area; it calls the check via AJAX and shows the result in a modal popup (for shops that have auto-check disabled).
- The config flag `blCheckForUpdates` continues to control whether the auto-check on login runs; the manual button always works when enabled.

## Capabilities

### New Capabilities

- `admin-update-check`: Rich update check that collects shop version, domain, and active module versions, sends them to the O3-Shop update service, and displays a structured result (core + module updates) in the admin backend. Includes both an automatic check on login and a manually triggered popup variant.

### Modified Capabilities

<!-- None: this is a net-new capability extracted from the current inline logic in NavigationController -->

## Impact

- **Modified**: `source/Application/Controller/Admin/NavigationController.php` — `checkVersion()` / `doStartUpChecks()` delegate to the new service.
- **New**: `source/Internal/Framework/UpdateCheck/UpdateCheckService.php` — encapsulates HTTP call, payload building, and response parsing.
- **New/Modified**: `source/Application/views/admin/tpl/` — partial template for update notices; update button in header.
- **Language files**: New translation keys for update check messages.
- **Config**: `blCheckForUpdates` config param already exists and continues to be respected.
- **Dependencies**: Uses PHP `curl` (already available) or Symfony HTTP client if present; falls back gracefully when unavailable.
- **No DB schema changes.**
- **No breaking changes.**
