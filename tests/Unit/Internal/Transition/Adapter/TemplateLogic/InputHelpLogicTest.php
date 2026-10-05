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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\LanguageException;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\InputHelpLogic;

class InputHelpLogicTest extends \OxidTestCase
{
    public function testGetIdentReturnsIdentParamWhenPresent(): void
    {
        $logic = new InputHelpLogic();
        $this->assertSame('HELP_TEXT', $logic->getIdent(['ident' => 'HELP_TEXT']));
    }

    public function testGetIdentReturnsNullWhenAbsent(): void
    {
        $logic = new InputHelpLogic();
        $this->assertNull($logic->getIdent([]));
    }

    public function testGetTranslationDelegatesToLangAndPassesAdminFlag(): void
    {
        $lang = $this->getMockBuilder(Language::class)
            ->onlyMethods(['translateString', 'getTplLanguage'])
            ->getMock();
        $lang->expects($this->any())->method('getTplLanguage')->willReturn(0);
        $lang->expects($this->once())
            ->method('translateString')
            ->with('HELP_KEY', 0, false)
            ->willReturn('Translated help.');
        Registry::set(Language::class, $lang);

        $config = $this->getMock(Config::class, ['isAdmin']);
        $config->expects($this->any())->method('isAdmin')->willReturn(false);
        Registry::set(Config::class, $config);

        $logic = new InputHelpLogic();
        $this->assertSame('Translated help.', $logic->getTranslation(['ident' => 'HELP_KEY']));
    }

    public function testGetTranslationCatchesLanguageExceptionAndReturnsNull(): void
    {
        $lang = $this->getMockBuilder(Language::class)
            ->onlyMethods(['translateString', 'getTplLanguage'])
            ->getMock();
        $lang->expects($this->any())->method('getTplLanguage')->willReturn(1);
        $lang->expects($this->any())
            ->method('translateString')
            ->willThrowException(new LanguageException('debug-mode missing key'));
        Registry::set(Language::class, $lang);

        $logic = new InputHelpLogic();
        $this->assertNull($logic->getTranslation(['ident' => 'MISSING_KEY']));
    }
}
