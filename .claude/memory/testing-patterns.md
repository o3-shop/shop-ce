---
name: Testing Patterns
description: PHPUnit setup, mocking with Prophecy, test structure and naming conventions
type: reference
---

## Running Tests
- Single file (fast): `./docker.sh test --fast tests/Unit/Path/To/ClassTest.php`
- Full suite: `./docker.sh test`
- With coverage: `./docker.sh test --coverage tests/Unit`
- Quarantine (slow): `./docker.sh quarantine`

## Test Structure
- Unit tests: `tests/Unit/` — mirror `source/` directory structure
- Integration tests: `tests/Integration/`
- Acceptance tests: `tests/Acceptance/` — Selenium-based, requires full stack
- Test class naming: `{ClassName}Test` in same sub-namespace as class under test

## Mocking
- PHPSpec Prophecy (`phpspec/prophecy-phpunit`)
- Use `$this->prophesize(SomeClass::class)` — not PHPUnit's built-in mocks
- Reveal the mock: `$mock->reveal()`
- Exception: legacy `\OxidTestCase`-based tests (e.g. `tests/Unit/Core/*`) use PHPUnit's built-in
  mocks (`getMockBuilder(...)->onlyMethods([...])`). Match the style already in the file.

## Calling protected/`_`-prefixed methods from tests
- `Core\Base::__call` (active when `OXID_PHP_UNIT` is defined) maps a `UNIT`-prefixed call to the
  underscore method: `$obj->UNITcheckModRewrite($x)` invokes the protected `_checkModRewrite($x)`.
- To unit-test the logic of a protected method that does I/O, extract the I/O into its own protected
  `_`-method, then partial-mock that seam: `getMockBuilder(Cls)->onlyMethods(['_doIo'])` and stub it,
  while calling the method under test via its `UNIT...` alias so the real logic runs.

## Bootstrap
- Fast mode uses `vendor/o3-shop/testing-library/bootstrap.php`
- DB is switched to `o3shop-test` automatically by `run-tests.sh` — never touch production DB in tests

## Groups
- Tag slow/special tests with `@group quarantine`
- All other tests run by default (quarantine group excluded)

## Coverage
- Output: `coverage/coverage.xml` (Clover), `coverage/html/` (HTML), `coverage/junit.xml`
- View HTML report: open `coverage/html/index.html` in a browser
