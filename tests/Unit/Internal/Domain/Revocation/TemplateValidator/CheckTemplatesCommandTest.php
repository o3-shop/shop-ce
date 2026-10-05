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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Revocation\TemplateValidator;

use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\CheckTemplatesCommand;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAsset;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator;
use Symfony\Component\Console\Tester\CommandTester;

class CheckTemplatesCommandTest extends \OxidTestCase
{
    public function testCommandNameIsFeatureNeutral(): void
    {
        $validator = $this->createMock(RevocationTemplateValidator::class);
        $command = new CheckTemplatesCommand($validator);
        $this->assertSame('o3:check-templates', $command->getName());
    }

    public function testReportsOkWhenAllAssetsPresent(): void
    {
        $validator = $this->createMock(RevocationTemplateValidator::class);
        $validator->expects($this->once())->method('validate')->willReturn([]);

        $command = new CheckTemplatesCommand($validator);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('OK', $tester->getDisplay());
    }

    public function testReportsMissingAssetsAndExitsNonZero(): void
    {
        $validator = $this->createMock(RevocationTemplateValidator::class);
        $validator->expects($this->once())
            ->method('validate')
            ->willReturn([
                new MissingAsset(
                    MissingAsset::TYPE_PAGE_TEMPLATE,
                    '/path/page/revocation.tpl',
                    null,
                    'Install the missing page template.'
                ),
                new MissingAsset(
                    MissingAsset::TYPE_TRANSLATION_KEY,
                    'O3_REVOCATION_FOOTER_LINK',
                    1,
                    'Add the missing key in language 1.'
                ),
            ]);

        $command = new CheckTemplatesCommand($validator);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(1, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Missing assets', $output);
        $this->assertStringContainsString('/path/page/revocation.tpl', $output);
        $this->assertStringContainsString('O3_REVOCATION_FOOTER_LINK', $output);
        // Translation-key entries surface their language ID.
        $this->assertStringContainsString('lang 1', $output);
    }

    public function testActiveLangIdsFallBackToLanguageZeroWhenConfigUnparseable(): void
    {
        // Empty config → resolveActiveLangIds returns [0].
        $this->getConfig()->setConfigParam('aLanguageParams', null);

        $validator = $this->createMock(RevocationTemplateValidator::class);
        $captured = null;
        $validator->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function ($shopId, $themeId, $langs) use (&$captured) {
                $captured = $langs;
                return [];
            });

        $tester = new CommandTester(new CheckTemplatesCommand($validator));
        $tester->execute([]);

        $this->assertSame([0], $captured);
    }

    public function testActiveLangIdsExtractedFromConfigWhenPresent(): void
    {
        $this->getConfig()->setConfigParam('aLanguageParams', [
            'de' => ['active' => 1, 'baseId' => 0],
            'en' => ['active' => 1, 'baseId' => 1],
            'fr' => ['active' => 0, 'baseId' => 2], // skipped — inactive
        ]);

        $validator = $this->createMock(RevocationTemplateValidator::class);
        $captured = null;
        $validator->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function ($shopId, $themeId, $langs) use (&$captured) {
                $captured = $langs;
                return [];
            });

        $tester = new CommandTester(new CheckTemplatesCommand($validator));
        $tester->execute([]);

        $this->assertSame([0, 1], $captured, 'Inactive languages must be filtered out.');
    }

    public function testThemeIdComesFromCustomThemeConfigWhenPresent(): void
    {
        $this->getConfig()->setConfigParam('sCustomTheme', 'my-custom-theme');
        $this->getConfig()->setConfigParam('sTheme', 'wave');

        $validator = $this->createMock(RevocationTemplateValidator::class);
        $captured = null;
        $validator->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function ($shopId, $themeId, $langs) use (&$captured) {
                $captured = $themeId;
                return [];
            });

        (new CommandTester(new CheckTemplatesCommand($validator)))->execute([]);

        $this->assertSame('my-custom-theme', $captured);
    }

    public function testThemeIdFallsBackToWaveWhenAllConfigSlotsEmpty(): void
    {
        $this->getConfig()->setConfigParam('sCustomTheme', '');
        $this->getConfig()->setConfigParam('sTheme', '');

        $validator = $this->createMock(RevocationTemplateValidator::class);
        $captured = null;
        $validator->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function ($shopId, $themeId, $langs) use (&$captured) {
                $captured = $themeId;
                return [];
            });

        (new CommandTester(new CheckTemplatesCommand($validator)))->execute([]);

        $this->assertSame('wave', $captured);
    }
}
