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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use PHPUnit\Framework\MockObject\MockObject;

class TableViewNameGeneratorTest extends \OxidTestCase
{
    public function testLanguageTableViewNameGenerationWhenDefaultLanguageIsUsed()
    {
        /** @var oxConfig|MockObject $config */
        $config = $this->getConfig();

        /** @var oxLang|MockObject $language */
        $language = $this->getMock(\OxidEsales\Eshop\Core\Language::class, array('getMultiLangTables', 'getBaseLanguage', 'getLanguageAbbr'));
        $language->expects($this->any())->method('getMultiLangTables')->will($this->returnValue(array('test_table1', 'test_table2')));
        $language->expects($this->any())->method('getBaseLanguage')->will($this->returnValue('baseLanguage'));
        $language->expects($this->any())->method('getLanguageAbbr')->with('baseLanguage')->will($this->returnValue('te'));

        $viewNameGenerator = oxNew('oxTableViewNameGenerator', $config, $language);
        $this->assertEquals('oxv_test_table1_te', $viewNameGenerator->getViewName('test_table1'));
    }

    public function testLanguageTableViewNameGenerationWhenLanguageIsPassed()
    {
        /** @var oxConfig|MockObject $config */
        $config = $this->getConfig();

        /** @var oxLang|MockObject $language */
        $language = $this->getMock(\OxidEsales\Eshop\Core\Language::class, array('getMultiLangTables', 'getBaseLanguage', 'getLanguageAbbr'));
        $language->expects($this->any())->method('getMultiLangTables')->will($this->returnValue(array('test_table1', 'test_table2')));
        $language->expects($this->any())->method('getBaseLanguage')->will($this->returnValue('baseLanguage'));
        $language->expects($this->any())->method('getLanguageAbbr')->with('passedLanguage')->will($this->returnValue('te'));

        $viewNameGenerator = oxNew('oxTableViewNameGenerator', $config, $language);
        $this->assertEquals('oxv_test_table1_te', $viewNameGenerator->getViewName('test_table1', 'passedLanguage'));
    }

    public function testViewNameGenerationWithNonMultiLangAndNonMultiShopTable()
    {
        /** @var oxConfig|MockObject $config */
        $config = $this->getConfig();

        /** @var oxLang|MockObject $language */
        $language = $this->getMock(\OxidEsales\Eshop\Core\Language::class, array('getMultiLangTables', 'getBaseLanguage', 'getLanguageAbbr'));
        $language->expects($this->any())->method('getMultiLangTables')->will($this->returnValue(array()));

        $viewNameGenerator = oxNew('oxTableViewNameGenerator', $config, $language);
        $this->assertEquals('non_multi_lang_table', $viewNameGenerator->getViewName('non_multi_lang_table'));
    }

    public function testTableViewNameGenerationWithNegativeLanguage()
    {
        /** @var oxConfig|MockObject $config */
        $config = $this->getConfig();

        /** @var oxLang|MockObject $language */
        $language = $this->getMock(\OxidEsales\Eshop\Core\Language::class, array('getMultiLangTables', 'getBaseLanguage', 'getLanguageAbbr'));
        $language->expects($this->any())->method('getMultiLangTables')->will($this->returnValue(array('table1')));

        $viewNameGenerator = oxNew('oxTableViewNameGenerator', $config, $language);
        $this->assertEquals('oxv_table1', $viewNameGenerator->getViewName('table1', -1));
    }
}
