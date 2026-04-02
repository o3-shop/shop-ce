## 1. UpdateCheckResult DTO

- [ ] 1.1 Create `source/Internal/Framework/UpdateCheck/UpdateCheckResult.php` with typed properties: `bool $coreUpdateAvailable`, `string $latestCoreVersion`, `string $updateLink`, `array $outdatedModules`; all initialised to safe empty defaults
- [ ] 1.2 Add a named constructor `UpdateCheckResult::empty()` returning a zeroed-out instance for error/fallback cases
- [ ] 1.3 Write unit test for `UpdateCheckResult`: verify defaults and that all fields are correctly set via constructor

## 2. UpdateCheckServiceInterface

- [ ] 2.1 Create `source/Internal/Framework/UpdateCheck/UpdateCheckServiceInterface.php` declaring `public function check(): UpdateCheckResult`

## 3. UpdateCheckService — Payload Building

- [ ] 3.1 Create `source/Internal/Framework/UpdateCheck/UpdateCheckService.php` implementing `UpdateCheckServiceInterface`; define `const ENDPOINT` (placeholder `https://updates.o3-shop.com/check/v1` until confirmed)
- [ ] 3.2 Implement `buildPayload()`: collect `shop_version` from `ShopVersion::getVersion()`, `domain` from `Registry::getConfig()->getShopUrl()`, and `modules` map (all configured module id → version) via `ShopConfigurationDaoBridgeInterface`
- [ ] 3.3 Write unit test for `buildPayload()`: assert correct shape of the payload array

## 4. UpdateCheckService — HTTP and Response Parsing

- [ ] 4.1 Implement HTTP POST via `curl` with `CURLOPT_TIMEOUT => 5`, `CURLOPT_RETURNTRANSFER => true`, `Content-Type: application/json`
- [ ] 4.2 Implement response parsing: map server response fields to `UpdateCheckResult`; filter `outdatedModules` to active modules only using `ModuleActivationBridgeInterface`
- [ ] 4.3 Implement GitHub API fallback (`https://api.github.com/repos/o3-shop/o3-shop/releases/latest`) for core-only check when the primary endpoint returns non-200 or times out
- [ ] 4.4 Wrap the entire `check()` method in a try/catch so any exception returns `UpdateCheckResult::empty()` without propagating
- [ ] 4.5 Write unit test for `check()` with a mocked successful response: assert `UpdateCheckResult` fields are populated and inactive modules are excluded from `outdatedModules`
- [ ] 4.6 Write unit test for the fallback path: non-200 response falls back to GitHub API and returns a core-only result

## 5. Session Caching

- [ ] 5.1 Implement cache read in `check()`: if a valid cached `UpdateCheckResult` exists in the session and is less than 24 hours old, return it immediately
- [ ] 5.2 Implement cache write: after a successful HTTP check, serialise the result and timestamp into the session under a dedicated key
- [ ] 5.3 Ensure `forceUpdateCheck=1` request parameter causes the cache to be bypassed before the check runs
- [ ] 5.4 Write unit test for session cache: second call within 24 h returns cached result without a new HTTP call; `forceUpdateCheck=1` bypasses it

## 6. DI Registration

- [ ] 6.1 Register `UpdateCheckServiceInterface` → `UpdateCheckService` in the Symfony DI container (add entry to the appropriate `services.yaml` under `source/Internal/`)

## 7. NavigationController Integration

- [ ] 7.1 Inject or resolve `UpdateCheckServiceInterface` in `NavigationController`
- [ ] 7.2 Replace the existing inline `checkVersion()` logic with a call to `UpdateCheckService::check()`
- [ ] 7.3 In `doStartUpChecks()`: when `blCheckForUpdates` is `true`, call the service and assign the result to `$this->_aViewData['updateCheckResult']`
- [ ] 7.4 Handle `forceUpdateCheck=1` parameter: always call the service regardless of `blCheckForUpdates`, passing the flag through to the service (cache bypass)
- [ ] 7.5 Keep the existing `checkVersion()` method signature for backwards compatibility but delegate to the service internally
- [ ] 7.6 Write unit test for `doStartUpChecks()`: assert `updateCheckResult` view data is set when `blCheckForUpdates` is true, and absent when false

## 8. Templates

- [ ] 8.1 Update `source/Application/views/admin/tpl/home.tpl`: add a block that renders the core update notice when `$updateCheckResult->coreUpdateAvailable` is true, showing current and latest version with the update link
- [ ] 8.2 Add a module update list block in `home.tpl`: iterate `$updateCheckResult->outdatedModules` and render one row per module (id, installed version, latest version, URL)
- [ ] 8.3 Add the "Check for updates" button/link to `source/Application/views/admin/tpl/header.tpl`, targeting `basefrm` frame with `?cl=navigation&item=home.tpl&forceUpdateCheck=1`

## 9. Language Keys

- [ ] 9.1 Add new translation keys to `source/Application/views/admin/en/lang.php`: core update notice, module update table headers, "Check for updates" button label, "up to date" message
- [ ] 9.2 Add the same keys to `source/Application/views/admin/de/lang.php`
