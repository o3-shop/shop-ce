<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Command;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Command\ReleaseCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Section 3 scaffold tests: only flag parsing + validation. The
 * command's body that runs the algorithm (Sections 4–9) and the
 * per-repo flow (Section 10) is not yet implemented; these tests
 * cover the surface contract that those Sections build on.
 */
class ReleaseCommandTest extends TestCase
{
    private function tester(): CommandTester
    {
        return new CommandTester(new ReleaseCommand());
    }

    public function testRunWithBothMandatoryFlagsExitsZero(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
        ]);
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
        $this->assertStringContainsString('--from=v1.6.0', $tester->getDisplay());
        $this->assertStringContainsString('--to=v1.6.1-RC1', $tester->getDisplay());
    }

    public function testRunWithoutFromExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute(['--to' => 'v1.6.1-RC1']);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString('--from is required', $tester->getDisplay());
    }

    public function testRunWithoutToExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute(['--from' => 'v1.6.0']);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString('--to is required', $tester->getDisplay());
    }

    public function testRunWithBothFlagsEmptyStringExitsUsageError(): void
    {
        // Symfony lets you pass --from='' as an empty string; the command
        // must treat that as "missing", not as a valid empty tag.
        $tester = $this->tester();
        $status = $tester->execute(['--from' => '', '--to' => 'v1.6.1-RC1']);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
    }

    public function testRunWithRepeatedBumpFlagsCollectsAllValues(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--bump' => ['testing-library=minor', 'shop-facts=v2.0.0'],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('testing-library=minor', $display);
        $this->assertStringContainsString('shop-facts=v2.0.0', $display);
    }

    public function testRunWithDryRunFlagPropagates(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--dry-run' => true,
        ]);
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
        $this->assertStringContainsString('--dry-run=true', $tester->getDisplay());
    }

    /**
     * @dataProvider validBumpValueProvider
     */
    public function testValidBumpValueReturnsNullFromValidator(string $value): void
    {
        $command = new ReleaseCommand();
        $this->assertNull($command->validateBumpValue($value));
    }

    public function validBumpValueProvider(): array
    {
        return [
            ['testing-library=patch'],
            ['testing-library=minor'],
            ['shop-facts=major'],
            ['shop-facts=v2.0.0'],
            ['gdpr-optin-module=v1.1.0'],
            ['o3-theme=v1.6.1-RC1'],
            ['shop-demodata-ce=v0.0.0-alpha.1'],
        ];
    }

    /**
     * @dataProvider invalidBumpValueProvider
     */
    public function testInvalidBumpValueReturnsErrorFromValidator(
        string $value,
        string $expectFragment
    ): void {
        $command = new ReleaseCommand();
        $error = $command->validateBumpValue($value);
        $this->assertNotNull($error);
        $this->assertStringContainsString($expectFragment, $error);
    }

    public function invalidBumpValueProvider(): array
    {
        return [
            'no equals sign' => ['testing-library', 'Expected <repo>=<level>'],
            'leading equals' => ['=patch', 'Expected <repo>=<level>'],
            'trailing equals' => ['testing-library=', 'Expected <repo>=<level>'],
            'unknown level' => ['testing-library=bogus', 'Malformed --bump level'],
            'missing v prefix' => ['testing-library=1.0.0', 'Malformed --bump level'],
            'uppercase repo' => ['Testing-Library=patch', 'Malformed --bump repo slug'],
            'slash in repo' => ['o3-shop/testing-library=patch', 'Malformed --bump repo slug'],
        ];
    }

    public function testRunWithMalformedBumpExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--bump' => ['testing-library=bogus'],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString(
            'Malformed --bump level',
            $tester->getDisplay()
        );
    }
}
