# `oe:theme:*` CLI + Install-Path Wiring — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `oe:theme:activate`, `oe:theme:deactivate`, `oe:theme:list` console commands (symmetric with the module CLI) and call activation from the install paths so `theme.php` — not hand-written SQL — populates the live theme config.

**Architecture:** A thin, mockable `ThemeBridge` wraps the legacy `\OxidEsales\Eshop\Core\Theme` model (mirroring `ModuleActivationBridge`). Three Symfony commands depend only on `ThemeBridgeInterface`, so they unit-test against a mock; the bridge's `oxNew`-based behaviour is covered by integration tests. Install paths (`bin/o3-setup`, `Setup/Controller.php`) invoke `oe:theme:activate` with no argument, which resolves the configured default (`sCustomTheme || sTheme`).

**Tech Stack:** PHP 8.2, Symfony Console **v3.4.47** (⚠ no `Command::SUCCESS` constant, no `: int` return on `execute()` — return plain `0`/`1`, no return type), PHPUnit 9, Symfony DI (YAML), Docker via `./docker.sh`.

**Scope:** Phases 1 & 2 of issue #122. Phase 3 (removing duplicated SQL) is OUT of scope — all changes are additive and idempotent.

---

## Conventions for every task

- License header: copy the 20-line GPLv3 header block verbatim from any existing file in the same directory (e.g. `source/Internal/Framework/Theme/Bridge/AdminThemeBridge.php`). It is omitted from the code blocks below for brevity — **include it** at the top of every new PHP file, followed by `declare(strict_types=1);`.
- Run a single unit test: `./docker.sh test --fast tests/Unit/<path>`
- Run a single integration test: `./docker.sh test --fast tests/Integration/<path>`
- Start the environment first: `./docker.sh start`
- Commit message footer (both lines):
  ```
  Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_01LHXXemrQsPWf7KeN9DY9Zm
  ```

---

## File structure

**New**
- `source/Internal/Framework/Theme/Exception/ThemeNotFoundException.php`
- `source/Internal/Framework/Theme/Exception/CannotDeactivateThemeException.php`
- `source/Internal/Framework/Theme/DataObject/ThemeDataObject.php`
- `source/Internal/Framework/Theme/Bridge/ThemeBridgeInterface.php`
- `source/Internal/Framework/Theme/Bridge/ThemeBridge.php`
- `source/Internal/Framework/Theme/Command/ThemeActivateCommand.php`
- `source/Internal/Framework/Theme/Command/ThemeDeactivateCommand.php`
- `source/Internal/Framework/Theme/Command/ThemeListCommand.php`
- `tests/Unit/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php`
- `tests/Unit/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php`
- `tests/Unit/Internal/Framework/Theme/Command/ThemeListCommandTest.php`
- `tests/Integration/Internal/Framework/Theme/Command/ThemeCommandsTestCase.php`
- `tests/Integration/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php`
- `tests/Integration/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php`
- `tests/Integration/Internal/Framework/Theme/Command/ThemeListCommandTest.php`

**Modified**
- `source/Internal/Framework/Theme/services.yaml` — register bridge + 3 commands
- `bin/o3-setup` — activate default theme after data import
- `source/Setup/Controller.php` + `source/Setup/Utilities.php` — activate default theme after `installShopData()`

---

## Task 1: Exceptions + DataObject + bridge interface

**Files:**
- Create: `source/Internal/Framework/Theme/Exception/ThemeNotFoundException.php`
- Create: `source/Internal/Framework/Theme/Exception/CannotDeactivateThemeException.php`
- Create: `source/Internal/Framework/Theme/DataObject/ThemeDataObject.php`
- Create: `source/Internal/Framework/Theme/Bridge/ThemeBridgeInterface.php`

No tests for this task (pure type declarations); they are exercised by Tasks 2–6.

- [ ] **Step 1: Create `ThemeNotFoundException`**

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception;

class ThemeNotFoundException extends \Exception
{
    public function __construct(string $themeId)
    {
        parent::__construct(sprintf('Theme "%s" was not found.', $themeId));
    }
}
```

- [ ] **Step 2: Create `CannotDeactivateThemeException`**

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception;

class CannotDeactivateThemeException extends \Exception
{
    public function __construct(string $themeId)
    {
        parent::__construct(sprintf(
            'Cannot deactivate base theme "%s": the storefront would be left without a theme. '
            . 'Activate another theme instead.',
            $themeId
        ));
    }
}
```

- [ ] **Step 3: Create `ThemeDataObject`** (immutable read-model used by `list`)

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\DataObject;

class ThemeDataObject
{
    private string $id;
    private string $title;
    private string $version;
    private string $parentTheme;
    private bool $active;

    public function __construct(
        string $id,
        string $title,
        string $version,
        string $parentTheme,
        bool $active
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->version = $version;
        $this->parentTheme = $parentTheme;
        $this->active = $active;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getParentTheme(): string
    {
        return $this->parentTheme;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
```

- [ ] **Step 4: Create `ThemeBridgeInterface`**

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\DataObject\ThemeDataObject;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface ThemeBridgeInterface
{
    /**
     * Activates a theme, writing its theme.php defaults to the shop configuration.
     *
     * @throws ThemeNotFoundException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException on activation errors (e.g. parent version mismatch)
     */
    public function activate(string $themeId): void;

    /**
     * Deactivates a theme.
     * - If $themeId is the active child theme (sCustomTheme), clears it (reverts to the parent base theme).
     * - If $themeId is the active base theme (sTheme), refuses.
     * - If $themeId is not active, does nothing.
     *
     * @return bool true if the theme was deactivated, false if it was not active (no-op)
     * @throws ThemeNotFoundException
     * @throws CannotDeactivateThemeException
     */
    public function deactivate(string $themeId): bool;

    /**
     * Returns the configured active theme id (sCustomTheme if set, else sTheme), or '' if none.
     */
    public function getActiveThemeId(): string;

    /**
     * @return ThemeDataObject[]
     */
    public function list(): array;
}
```

- [ ] **Step 5: Commit**

```bash
git add source/Internal/Framework/Theme/Exception source/Internal/Framework/Theme/DataObject source/Internal/Framework/Theme/Bridge/ThemeBridgeInterface.php
git commit -m "feat(#122): add theme bridge interface, exceptions, data object"
```

---

## Task 2: `ThemeBridge` implementation

The bridge uses `oxNew()` and `Registry::getConfig()`, so it is covered by integration tests (Task 6), not unit tests. Implement it here so the commands in Tasks 3–5 have a concrete service to wire later.

**Files:**
- Create: `source/Internal/Framework/Theme/Bridge/ThemeBridge.php`

- [ ] **Step 1: Implement `ThemeBridge`**

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Theme;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\DataObject\ThemeDataObject;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;

class ThemeBridge implements ThemeBridgeInterface
{
    public function activate(string $themeId): void
    {
        $this->loadTheme($themeId)->activate();
    }

    public function deactivate(string $themeId): bool
    {
        $this->loadTheme($themeId);

        $config = Registry::getConfig();
        $customTheme = (string) $config->getConfigParam('sCustomTheme');
        $baseTheme = (string) $config->getConfigParam('sTheme');

        if ($customTheme !== '' && $customTheme === $themeId) {
            $config->saveShopConfVar('str', 'sCustomTheme', '');
            return true;
        }

        if ($baseTheme === $themeId) {
            throw new CannotDeactivateThemeException($themeId);
        }

        return false;
    }

    public function getActiveThemeId(): string
    {
        $theme = oxNew(Theme::class);
        return (string) $theme->getActiveThemeId();
    }

    public function list(): array
    {
        $activeThemeIds = $this->getActiveThemeIds();

        $themes = [];
        foreach (oxNew(Theme::class)->getList() as $theme) {
            $id = (string) $theme->getId();
            $themes[] = new ThemeDataObject(
                $id,
                (string) $theme->getInfo('title'),
                (string) $theme->getInfo('version'),
                (string) $theme->getInfo('parentTheme'),
                in_array($id, $activeThemeIds, true)
            );
        }

        return $themes;
    }

    /**
     * @throws ThemeNotFoundException
     */
    private function loadTheme(string $themeId): Theme
    {
        $theme = oxNew(Theme::class);
        if (!$theme->load($themeId)) {
            throw new ThemeNotFoundException($themeId);
        }
        return $theme;
    }

    /**
     * @return string[]
     */
    private function getActiveThemeIds(): array
    {
        $config = Registry::getConfig();
        return array_values(array_filter([
            (string) $config->getConfigParam('sTheme'),
            (string) $config->getConfigParam('sCustomTheme'),
        ]));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add source/Internal/Framework/Theme/Bridge/ThemeBridge.php
git commit -m "feat(#122): implement ThemeBridge over legacy Theme model"
```

---

## Task 3: `ThemeActivateCommand` (TDD)

**Files:**
- Create: `source/Internal/Framework/Theme/Command/ThemeActivateCommand.php`
- Test: `tests/Unit/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php`

- [ ] **Step 1: Write the failing unit test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeActivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ThemeActivateCommandTest extends TestCase
{
    public function testActivatesGivenTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->expects($this->once())->method('activate')->with('wave');

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute(['theme-id' => 'wave']);

        $this->assertStringContainsString('was activated', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testResolvesConfiguredThemeWhenNoArgumentGiven(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('getActiveThemeId')->willReturn('o3-theme');
        $bridge->expects($this->once())->method('activate')->with('o3-theme');

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute([]);

        $this->assertStringContainsString('o3-theme', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testFailsWhenNoArgumentAndNoConfiguredTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('getActiveThemeId')->willReturn('');
        $bridge->expects($this->never())->method('activate');

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testReportsThemeNotFound(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('activate')->willThrowException(new ThemeNotFoundException('ghost'));

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute(['theme-id' => 'ghost']);

        $this->assertStringContainsString('not found', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testReportsActivationError(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('activate')->willThrowException(new StandardException('EXCEPTION_PARENT_VERSION_MISMATCH'));

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute(['theme-id' => 'child']);

        $this->assertStringContainsString('EXCEPTION_PARENT_VERSION_MISMATCH', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php`
Expected: FAIL — `Class "...ThemeActivateCommand" not found`.

- [ ] **Step 3: Implement `ThemeActivateCommand`**

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command activates a theme by theme id.
 */
class ThemeActivateCommand extends Command
{
    public const MESSAGE_THEME_ACTIVATED = 'Theme - "%s" was activated.';

    public const MESSAGE_THEME_NOT_FOUND = 'Theme - "%s" not found.';

    public const MESSAGE_NO_THEME_CONFIGURED =
        'No theme id was given and no active theme is configured.';

    public const ARGUMENT_THEME_ID = 'theme-id';

    private ThemeBridgeInterface $themeBridge;

    public function __construct(ThemeBridgeInterface $themeBridge)
    {
        parent::__construct(null);
        $this->themeBridge = $themeBridge;
    }

    protected function configure()
    {
        $this->setDescription('Activates a theme.')
            ->addArgument(
                static::ARGUMENT_THEME_ID,
                InputArgument::OPTIONAL,
                'Theme ID (defaults to the configured active theme)'
            )
            ->setHelp(
                'Activates a theme by ID, writing its theme.php defaults to the shop configuration. '
                . 'If no ID is given, the currently configured theme (sCustomTheme or sTheme) is re-activated.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $themeId = (string) $input->getArgument(static::ARGUMENT_THEME_ID);

        if ($themeId === '') {
            $themeId = $this->themeBridge->getActiveThemeId();
            if ($themeId === '') {
                $output->writeln('<error>' . static::MESSAGE_NO_THEME_CONFIGURED . '</error>');
                return 1;
            }
        }

        try {
            $this->themeBridge->activate($themeId);
            $output->writeln('<info>' . sprintf(static::MESSAGE_THEME_ACTIVATED, $themeId) . '</info>');
            return 0;
        } catch (ThemeNotFoundException $exception) {
            $output->writeln('<error>' . sprintf(static::MESSAGE_THEME_NOT_FOUND, $themeId) . '</error>');
            return 1;
        } catch (StandardException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return 1;
        }
    }
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php`
Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add source/Internal/Framework/Theme/Command/ThemeActivateCommand.php tests/Unit/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php
git commit -m "feat(#122): add oe:theme:activate command"
```

---

## Task 4: `ThemeDeactivateCommand` (TDD)

**Files:**
- Create: `source/Internal/Framework/Theme/Command/ThemeDeactivateCommand.php`
- Test: `tests/Unit/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php`

- [ ] **Step 1: Write the failing unit test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Theme\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeDeactivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ThemeDeactivateCommandTest extends TestCase
{
    public function testDeactivatesActiveChildTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->with('child')->willReturn(true);

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'child']);

        $this->assertStringContainsString('was deactivated', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testReportsNotActiveWhenNoOp(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->willReturn(false);

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'wave']);

        $this->assertStringContainsString('not active', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testRefusesToDeactivateBaseTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->willThrowException(new CannotDeactivateThemeException('o3-theme'));

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'o3-theme']);

        $this->assertStringContainsString('Cannot deactivate base theme', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testReportsThemeNotFound(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->willThrowException(new ThemeNotFoundException('ghost'));

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'ghost']);

        $this->assertStringContainsString('not found', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `ThemeDeactivateCommand`**

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command deactivates a theme by theme id.
 */
class ThemeDeactivateCommand extends Command
{
    public const MESSAGE_THEME_DEACTIVATED = 'Theme - "%s" was deactivated.';

    public const MESSAGE_THEME_NOT_ACTIVE = 'Theme - "%s" is not active.';

    public const MESSAGE_THEME_NOT_FOUND = 'Theme - "%s" not found.';

    public const ARGUMENT_THEME_ID = 'theme-id';

    private ThemeBridgeInterface $themeBridge;

    public function __construct(ThemeBridgeInterface $themeBridge)
    {
        parent::__construct(null);
        $this->themeBridge = $themeBridge;
    }

    protected function configure()
    {
        $this->setDescription('Deactivates a theme.')
            ->addArgument(static::ARGUMENT_THEME_ID, InputArgument::REQUIRED, 'Theme ID')
            ->setHelp(
                'Deactivates a custom (child) theme, reverting the storefront to its parent base theme. '
                . 'Base themes cannot be deactivated; activate another theme instead.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $themeId = (string) $input->getArgument(static::ARGUMENT_THEME_ID);

        try {
            $deactivated = $this->themeBridge->deactivate($themeId);
            if ($deactivated) {
                $output->writeln('<info>' . sprintf(static::MESSAGE_THEME_DEACTIVATED, $themeId) . '</info>');
            } else {
                $output->writeln('<info>' . sprintf(static::MESSAGE_THEME_NOT_ACTIVE, $themeId) . '</info>');
            }
            return 0;
        } catch (ThemeNotFoundException $exception) {
            $output->writeln('<error>' . sprintf(static::MESSAGE_THEME_NOT_FOUND, $themeId) . '</error>');
            return 1;
        } catch (CannotDeactivateThemeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return 1;
        }
    }
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add source/Internal/Framework/Theme/Command/ThemeDeactivateCommand.php tests/Unit/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php
git commit -m "feat(#122): add oe:theme:deactivate command"
```

---

## Task 5: `ThemeListCommand` (TDD)

**Files:**
- Create: `source/Internal/Framework/Theme/Command/ThemeListCommand.php`
- Test: `tests/Unit/Internal/Framework/Theme/Command/ThemeListCommandTest.php`

- [ ] **Step 1: Write the failing unit test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Theme\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeListCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\DataObject\ThemeDataObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ThemeListCommandTest extends TestCase
{
    public function testListsThemesWithActiveFlag(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('list')->willReturn([
            new ThemeDataObject('o3-theme', 'O3-Theme', '1.5.0', '', true),
            new ThemeDataObject('wave', 'Wave', '1.2.3', '', false),
        ]);

        $tester = new CommandTester(new ThemeListCommand($bridge));
        $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertStringContainsString('o3-theme', $display);
        $this->assertStringContainsString('O3-Theme', $display);
        $this->assertStringContainsString('1.5.0', $display);
        $this->assertStringContainsString('wave', $display);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testHandlesEmptyThemeList(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('list')->willReturn([]);

        $tester = new CommandTester(new ThemeListCommand($bridge));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Internal/Framework/Theme/Command/ThemeListCommandTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `ThemeListCommand`**

```php
namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command lists all installed themes.
 */
class ThemeListCommand extends Command
{
    private ThemeBridgeInterface $themeBridge;

    public function __construct(ThemeBridgeInterface $themeBridge)
    {
        parent::__construct(null);
        $this->themeBridge = $themeBridge;
    }

    protected function configure()
    {
        $this->setDescription('Lists installed themes.')
            ->setHelp('Lists all installed themes with their id, title, version, parent and active state.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rows = [];
        foreach ($this->themeBridge->list() as $theme) {
            $rows[] = [
                $theme->getId(),
                $theme->getTitle(),
                $theme->getVersion(),
                $theme->getParentTheme(),
                $theme->isActive() ? 'yes' : 'no',
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Title', 'Version', 'Parent', 'Active'])
            ->setRows($rows)
            ->render();

        return 0;
    }
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Internal/Framework/Theme/Command/ThemeListCommandTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add source/Internal/Framework/Theme/Command/ThemeListCommand.php tests/Unit/Internal/Framework/Theme/Command/ThemeListCommandTest.php
git commit -m "feat(#122): add oe:theme:list command"
```

---

## Task 6: Register services + integration tests

**Files:**
- Modify: `source/Internal/Framework/Theme/services.yaml`
- Create: `tests/Integration/Internal/Framework/Theme/Command/ThemeCommandsTestCase.php`
- Create: `tests/Integration/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php`
- Create: `tests/Integration/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php`
- Create: `tests/Integration/Internal/Framework/Theme/Command/ThemeListCommandTest.php`

- [ ] **Step 1: Register the bridge and commands in `services.yaml`**

The existing file (do not delete its `AdminThemeBridge` block) must gain the bridge + three command registrations. Append under `services:`:

```yaml
  OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface:
    class: OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridge

  oxid_esales.command.theme_activate_command:
    class: OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeActivateCommand
    tags:
      - { name: 'console.command', command: 'oe:theme:activate' }

  oxid_esales.command.theme_deactivate_command:
    class: OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeDeactivateCommand
    tags:
      - { name: 'console.command', command: 'oe:theme:deactivate' }

  oxid_esales.command.theme_list_command:
    class: OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeListCommand
    tags:
      - { name: 'console.command', command: 'oe:theme:list' }
```

The file's `_defaults` already sets `autowire: true`, so the commands' `ThemeBridgeInterface` argument is injected automatically.

- [ ] **Step 2: Verify the commands register (smoke check)**

Run: `./docker.sh start` then
```bash
docker exec o3shop-122-oe-theme-activate-cli-1 php bin/oe-console list | grep oe:theme
```
Expected: three lines — `oe:theme:activate`, `oe:theme:deactivate`, `oe:theme:list`.
(If the container name differs, derive it from the worktree per CLAUDE.md: `o3shop-<worktree>-1`.)

- [ ] **Step 3: Create the integration test base case**

This base saves and restores `sTheme` / `sCustomTheme` so theme switching cannot leak into other tests, and exposes the console helpers.

```php
namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Console\ConsoleTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;

class ThemeCommandsTestCase extends TestCase
{
    use ContainerTrait;
    use ConsoleTrait;

    private string $originalTheme = '';
    private string $originalCustomTheme = '';

    protected function setUp(): void
    {
        parent::setUp();
        $config = Registry::getConfig();
        $this->originalTheme = (string) $config->getConfigParam('sTheme');
        $this->originalCustomTheme = (string) $config->getConfigParam('sCustomTheme');
    }

    protected function tearDown(): void
    {
        $config = Registry::getConfig();
        $config->saveShopConfVar('str', 'sTheme', $this->originalTheme);
        $config->saveShopConfVar('str', 'sCustomTheme', $this->originalCustomTheme);
        parent::tearDown();
    }

    protected function getApplication(): Application
    {
        $application = $this->get('oxid_esales.console.symfony.component.console.application');
        $application->setAutoExit(false);
        return $application;
    }

    protected function runCommand(array $input): string
    {
        return $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new \Symfony\Component\Console\Input\ArrayInput($input)
        );
    }

    protected function setActiveTheme(string $baseTheme, string $customTheme): void
    {
        $config = Registry::getConfig();
        $config->saveShopConfVar('str', 'sTheme', $baseTheme);
        $config->saveShopConfVar('str', 'sCustomTheme', $customTheme);
    }
}
```

- [ ] **Step 4: Create the activate integration test**

```php
namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Registry;

final class ThemeActivateCommandTest extends ThemeCommandsTestCase
{
    public function testActivatesRealThemeAndWritesConfig(): void
    {
        $output = $this->runCommand(['command' => 'oe:theme:activate', 'theme-id' => 'wave']);

        $this->assertStringContainsString('was activated', $output);
        $this->assertSame('wave', (string) Registry::getConfig()->getConfigParam('sTheme'));
    }

    public function testActivatesConfiguredThemeWhenNoArgument(): void
    {
        $this->setActiveTheme('wave', '');

        $output = $this->runCommand(['command' => 'oe:theme:activate']);

        $this->assertStringContainsString('wave', $output);
        $this->assertSame('wave', (string) Registry::getConfig()->getConfigParam('sTheme'));
    }

    public function testReportsNotFoundForUnknownTheme(): void
    {
        $output = $this->runCommand(['command' => 'oe:theme:activate', 'theme-id' => 'does-not-exist']);

        $this->assertStringContainsString('not found', $output);
    }
}
```

- [ ] **Step 5: Create the deactivate integration test**

Note: real CE themes (`wave`, `o3-theme`) have no parent, so the child-theme branch is simulated by writing `sCustomTheme` directly — the bridge only checks `sCustomTheme === $themeId`.

```php
namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Registry;

final class ThemeDeactivateCommandTest extends ThemeCommandsTestCase
{
    public function testDeactivatesActiveCustomThemeAndClearsIt(): void
    {
        // Simulate 'wave' as the active custom theme over base 'o3-theme'.
        $this->setActiveTheme('o3-theme', 'wave');

        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'wave']);

        $this->assertStringContainsString('was deactivated', $output);
        $this->assertSame('', (string) Registry::getConfig()->getConfigParam('sCustomTheme'));
    }

    public function testRefusesToDeactivateBaseTheme(): void
    {
        $this->setActiveTheme('wave', '');

        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'wave']);

        $this->assertStringContainsString('Cannot deactivate base theme', $output);
        $this->assertSame('wave', (string) Registry::getConfig()->getConfigParam('sTheme'));
    }

    public function testReportsNotActiveForInactiveTheme(): void
    {
        $this->setActiveTheme('wave', '');

        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'o3-theme']);

        $this->assertStringContainsString('not active', $output);
    }

    public function testReportsNotFoundForUnknownTheme(): void
    {
        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'does-not-exist']);

        $this->assertStringContainsString('not found', $output);
    }
}
```

- [ ] **Step 6: Create the list integration test**

```php
namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Theme\Command;

final class ThemeListCommandTest extends ThemeCommandsTestCase
{
    public function testListsInstalledThemes(): void
    {
        $this->setActiveTheme('wave', '');

        $output = $this->runCommand(['command' => 'oe:theme:list']);

        $this->assertStringContainsString('wave', $output);
        $this->assertStringContainsString('o3-theme', $output);
        $this->assertStringContainsString('Active', $output);
    }
}
```

- [ ] **Step 7: Run the integration tests**

Run:
```bash
./docker.sh test --fast tests/Integration/Internal/Framework/Theme/Command/ThemeActivateCommandTest.php
./docker.sh test --fast tests/Integration/Internal/Framework/Theme/Command/ThemeDeactivateCommandTest.php
./docker.sh test --fast tests/Integration/Internal/Framework/Theme/Command/ThemeListCommandTest.php
```
Expected: all PASS. If a real theme id other than `wave`/`o3-theme` is needed, confirm available ids via the Step 2 smoke check / `ls source/Application/views`.

- [ ] **Step 8: Commit**

```bash
git add source/Internal/Framework/Theme/services.yaml tests/Integration/Internal/Framework/Theme/Command/
git commit -m "feat(#122): register theme commands + integration tests"
```

---

## Task 7: Wire `bin/o3-setup` (Phase 2a)

Activate the configured default theme after the `initial_data.sql` backfill, inside the fresh-install block (`if ($result->num_rows == 0)`), after the admin user is created (so shop + config rows exist) and before that block closes.

**Files:**
- Modify: `bin/o3-setup`

- [ ] **Step 1: Insert the activation call**

In `bin/o3-setup`, locate the admin-user `INSERT INTO oxuser` block (currently ending around line 214 with `$stmt->execute();`) which is immediately followed by `} else {` (the "Database already initialized" branch). Insert the following BETWEEN that `$stmt->execute();` and the `} else {`:

```php

    // Activate the declared default theme (issue #122, phase 2). With no
    // theme-id argument the command resolves sCustomTheme||sTheme from the
    // config just imported by initial_data.sql and re-applies the theme.php
    // defaults via the canonical Theme::activate() path. Idempotent alongside
    // the existing theme:<id> rows.
    echo "Activating default theme...\n";
    $themeActivateCommand = __DIR__ . '/oe-console';
    exec("cd " . __DIR__ . "/.. && php $themeActivateCommand oe:theme:activate 2>&1", $themeOutput, $themeReturnCode);
    if ($themeReturnCode === 0) {
        echo "Default theme activated.\n";
    } else {
        echo "WARNING: Default theme activation failed:\n" . implode("\n", $themeOutput) . "\n";
    }
```

- [ ] **Step 2: Verify on a clean install**

Run:
```bash
./docker.sh rebuild
```
Expected: setup output includes `Activating default theme...` then `Default theme activated.`

- [ ] **Step 3: Confirm config was written by activation**

Run:
```bash
docker exec o3shop-122-oe-theme-activate-cli-1 php bin/oe-console oe:theme:list
```
Expected: the default theme row shows `Active = yes`.

- [ ] **Step 4: Commit**

```bash
git add bin/o3-setup
git commit -m "feat(#122): activate default theme in bin/o3-setup"
```

---

## Task 8: Wire `source/Setup/Controller.php` (Phase 2b)

The web Setup wizard runs in a minimal bootstrap, so we invoke activation as an isolated subprocess (full framework bootstrap) via a new `Utilities` method, and degrade gracefully on failure (log + continue) per the repo's "graceful degradation over fail-fast" rule for user-facing flows. This is the web installer path (not exercised by `./docker.sh`), so it is smoke-verified manually.

**Files:**
- Modify: `source/Setup/Utilities.php`
- Modify: `source/Setup/Controller.php`

- [ ] **Step 1: Add `executeExternalThemeActivateCommand()` to `Utilities`**

Add this public method to `source/Setup/Utilities.php` (near `executeExternalDatabaseMigrationCommand`, ~line 497). It resolves `bin/oe-console` from the shop root via `Facts` (already imported) and runs the no-argument activate command:

```php
    /**
     * Calls the external oe:theme:activate console command to activate the
     * theme declared by the just-imported configuration (sCustomTheme||sTheme).
     * Failure is logged but does not abort setup.
     *
     * @return int Exit code of the activation command.
     */
    public function executeExternalThemeActivateCommand(Facts $facts = null)
    {
        $facts = $facts ?: new Facts();
        $console = $facts->getSourcePath() . '/../bin/oe-console';

        $output = [];
        $returnCode = 0;
        exec('php ' . escapeshellarg($console) . ' oe:theme:activate 2>&1', $output, $returnCode);

        return $returnCode;
    }
```

- [ ] **Step 2: Call it from `installShopData()`**

In `source/Setup/Controller.php`, inside `installShopData()` (lines 625–651), after the `if/else` block that installs demo or initial data and runs migrations — i.e. immediately before the closing brace of the `try` (after line 642) — add:

```php
            $this->getUtilitiesInstance()->executeExternalThemeActivateCommand();
```

So the `try` body ends:
```php
            } else {
                $database->queryFile("$baseSqlDir/initial_data.sql");

                $this->getUtilitiesInstance()->executeExternalDatabaseMigrationCommand();
            }

            $this->getUtilitiesInstance()->executeExternalThemeActivateCommand();
        } catch (Exception $exception) {
```

- [ ] **Step 3: Static check**

Run: `./docker.sh cs-fixer`
Expected: no errors; files reformatted to PSR-12 if needed.

- [ ] **Step 4: Smoke-verify the wizard path (manual, best-effort)**

The `./docker.sh` flow uses `bin/o3-setup`, not the web wizard, so automated verification isn't available. Confirm at minimum that `Utilities` loads without error and the method is syntactically reachable:
```bash
docker exec o3shop-122-oe-theme-activate-cli-1 php -r 'require "vendor/autoload.php"; echo method_exists(\OxidEsales\EshopCommunity\Setup\Utilities::class, "executeExternalThemeActivateCommand") ? "ok\n" : "missing\n";'
```
Expected: `ok`. Note in the PR description that the web-wizard branch is smoke-verified only.

- [ ] **Step 5: Commit**

```bash
git add source/Setup/Utilities.php source/Setup/Controller.php
git commit -m "feat(#122): activate default theme after install in Setup wizard"
```

---

## Task 9: Full quality gate

- [ ] **Step 1: Run the finish gate**

Run: `./docker.sh test-all-coverage` (cs-fixer + full unit suite + coverage), then run the three integration tests from Task 6 Step 7.
Expected: all green. Fix any failures before claiming completion (systematic-debugging skill if needed).

- [ ] **Step 2: Update shared memory**

Per `CLAUDE.md`, append to `.claude/memory/` anything non-obvious learned (e.g. "Symfony Console is v3.4 — no `Command::SUCCESS`; `Theme/services.yaml` is auto-imported via `Framework/services.yaml`; theme activation is idempotent and headless-safe via `saveShopConfVar`"). Add an index line to `.claude/memory/MEMORY.md`.

---

## Self-review notes (author)

- **Spec coverage:** activate (Task 3), deactivate w/ parent-revert + base-refusal (Task 4), list (Task 5), bridge (Task 2), registration (Task 6), bin/o3-setup wiring (Task 7), Setup wizard wiring (Task 8), read-default-from-config (activate optional arg → `getActiveThemeId`, Tasks 3/7/8). ✔
- **Symfony 3.4 constraint** applied throughout: no `Command::SUCCESS`, no `: int` on `execute()`, plain `return 0/1`. ✔
- **Type consistency:** `ThemeBridgeInterface` signatures (`activate:void`, `deactivate:bool`, `getActiveThemeId:string`, `list:ThemeDataObject[]`) match every caller and mock. ✔
- **No SQL removed** — Phase 3 explicitly excluded; activation is additive/idempotent. ✔
- **Known limitation:** the web Setup wizard branch (Task 8) is smoke-verified only — `./docker.sh` exercises `bin/o3-setup`, not the web installer.
