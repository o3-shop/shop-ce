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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\TranslateSalutationLogic;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Class TranslateSalutationLogic
 */
class TranslateSalutationLogicTest extends UnitTestCase
{

    /** @var TranslateSalutationLogic */
    private $translateSalutationLogic;

    protected function setUp(): void
    {
        $this->translateSalutationLogic = new TranslateSalutationLogic();
    }

    /**
     * Provides data for testTranslateSalutation
     *
     * @return array
     */
    public function translateSalutationProvider(): array
    {
        return [
            ['MR', 0, 'Herr'],
            ['MRS', 0, 'Frau'],
            ['MR', 1, 'Mr'],
            ['MRS', 1, 'Mrs']
        ];
    }

    /**
     * @param string $ident
     * @param int    $languageId
     * @param string $expected
     *
     * @dataProvider translateSalutationProvider
     */
    public function testTranslateSalutation(string $ident, int $languageId, string $expected): void
    {
        $this->setLanguage($languageId);
        $this->assertEquals($expected, $this->translateSalutationLogic->translateSalutation($ident));
    }
}