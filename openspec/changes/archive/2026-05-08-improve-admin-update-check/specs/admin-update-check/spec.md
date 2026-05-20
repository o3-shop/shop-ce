## ADDED Requirements

### Requirement: Payload collection
The `UpdateCheckService` SHALL build a JSON payload containing the current shop version (from `ShopVersion::getVersion()`), the shop domain (from `Registry::getConfig()->getShopUrl()`), and a map of all configured module IDs to their versions (obtained via `ShopConfigurationDaoBridgeInterface`).

#### Scenario: Payload includes all configured modules
- **WHEN** the service builds the payload
- **THEN** the `modules` field contains every configured module (active and inactive) as a map of `id => version`

#### Scenario: Payload includes shop version and domain
- **WHEN** the service builds the payload
- **THEN** the `shop_version` field contains the value from `ShopVersion::getVersion()`
- **THEN** the `domain` field contains the shop's base URL

---

### Requirement: HTTP update check request
The `UpdateCheckService` SHALL POST the payload as JSON to the hardcoded endpoint constant `UpdateCheckService::ENDPOINT` using `curl` with a 5-second timeout and `Content-Type: application/json`.

#### Scenario: Successful request
- **WHEN** the endpoint returns HTTP 200 with a valid JSON body
- **THEN** the service parses and returns the structured result

#### Scenario: Endpoint returns non-200
- **WHEN** the endpoint returns a non-200 HTTP status
- **THEN** the service falls back to the GitHub Releases API for a core-only version comparison
- **THEN** no module update notices are produced

#### Scenario: Request times out
- **WHEN** the endpoint does not respond within 5 seconds
- **THEN** the service falls back to the GitHub Releases API for a core-only version comparison
- **THEN** no module update notices are produced

---

### Requirement: Structured result DTO
The `UpdateCheckService::check()` method SHALL return an `UpdateCheckResult` value object containing: `bool $coreUpdateAvailable`, `string $latestCoreVersion`, `string $updateLink`, and `array $outdatedModules` (each item: `['id', 'latest_version', 'url']`). All fields SHALL have safe empty defaults so callers never receive null.

#### Scenario: Core update available
- **WHEN** the latest core version is higher than the installed version
- **THEN** `$result->coreUpdateAvailable` is `true` and `$result->latestCoreVersion` is set

#### Scenario: No core update available
- **WHEN** the installed version is equal to or higher than the latest
- **THEN** `$result->coreUpdateAvailable` is `false`

#### Scenario: Error or unavailable endpoint
- **WHEN** the check fails entirely
- **THEN** `UpdateCheckResult` with all empty/false defaults is returned without throwing an exception

---

### Requirement: Active-module-only update notices
The service or its caller SHALL filter `$outdatedModules` so that only modules that are currently active are included in the result shown to the admin. Inactive modules MAY be present in the payload sent to the server but SHALL NOT appear in the UI update list.

#### Scenario: Inactive module has an available update
- **WHEN** the server reports a newer version for a configured but inactive module
- **THEN** that module does NOT appear in the outdated modules list shown in the admin UI

#### Scenario: Active module has an available update
- **WHEN** the server reports a newer version for an active module
- **THEN** that module appears in the outdated modules list shown in the admin UI

---

### Requirement: Session result caching
After a successful check the result SHALL be stored in the admin session with a timestamp. On subsequent page loads within 24 hours the cached result SHALL be returned without making a new HTTP request. A request with `forceUpdateCheck=1` SHALL bypass the cache and perform a fresh check.

#### Scenario: Result served from cache
- **WHEN** a cached result exists and is less than 24 hours old
- **THEN** the service returns the cached result without making an HTTP call

#### Scenario: Cache expired
- **WHEN** the cached result is 24 hours old or older
- **THEN** the service performs a fresh HTTP check and updates the cache

#### Scenario: Force refresh
- **WHEN** the request contains `forceUpdateCheck=1`
- **THEN** the service ignores the cache and performs a fresh HTTP check

---

### Requirement: Automatic update check on admin login
When the config param `blCheckForUpdates` is `true`, `NavigationController::doStartUpChecks()` SHALL invoke `UpdateCheckService::check()` and pass the result as view data to `home.tpl`.

#### Scenario: Auto-check enabled
- **WHEN** the admin loads `home.tpl` and `blCheckForUpdates` is `true`
- **THEN** the update check runs and results are rendered in the message area

#### Scenario: Auto-check disabled
- **WHEN** `blCheckForUpdates` is `false`
- **THEN** the automatic check does NOT run on page load
- **THEN** no update results are rendered automatically

---

### Requirement: Manual "Check for updates" button
A "Check for updates" link SHALL always be present in the admin navigation header regardless of the `blCheckForUpdates` config value. Clicking it SHALL navigate the `basefrm` frame to `home.tpl` with `forceUpdateCheck=1`, triggering a fresh check and displaying the result.

#### Scenario: Button visible when auto-check is disabled
- **WHEN** `blCheckForUpdates` is `false`
- **THEN** the "Check for updates" button is still rendered in the admin header

#### Scenario: Manual check triggers fresh result
- **WHEN** the admin clicks the button
- **THEN** a fresh update check is performed (cache bypassed) and results are shown in `home.tpl`

---

### Requirement: Structured update display in home.tpl
The `home.tpl` template SHALL render update results in a structured format: a core version notice when a core update is available, and a list of outdated active modules each showing the module ID, currently installed version, latest available version, and an update URL.

#### Scenario: Core update available
- **WHEN** `$updateCheckResult->coreUpdateAvailable` is `true`
- **THEN** the template renders a notice with the current and latest core version

#### Scenario: Outdated active modules present
- **WHEN** `$updateCheckResult->outdatedModules` contains entries
- **THEN** the template renders one row per outdated module with its ID, versions, and link

#### Scenario: Everything up to date
- **WHEN** no core update and no outdated modules are present
- **THEN** no update notice is rendered (silent success)
