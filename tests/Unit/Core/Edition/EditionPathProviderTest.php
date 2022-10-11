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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Edition;

use OxidEsales\EshopCommunity\Core\Edition\EditionPathProvider;
use OxidEsales\EshopCommunity\Core\Edition\EditionRootPathProvider;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EditionPathProviderTest extends UnitTestCase
{
    public function testReturnsSetupPath()
    {
        $editionPathSelector = $this->getEditionPathSelectorMock();
        $editionPathSelector = new EditionPathProvider($editionPathSelector);

        $this->assertSame('/Setup/', $editionPathSelector->getSetupDirectory());
    }

    public function testReturnsSqlDirectory()
    {
        $editionSelector = $this->getEditionPathSelectorMock();
        $editionPathSelector = new EditionPathProvider($editionSelector);

        $this->assertSame('/Setup/Sql/', $editionPathSelector->getDatabaseSqlDirectory());
    }

    public function testReturnsSmartyPluginsDirectory()
    {
        $editionSelector = $this->getEditionPathSelectorMock();
        $editionPathSelector = new EditionPathProvider($editionSelector);

        $this->assertSame('/Core/Smarty/Plugin/', $editionPathSelector->getSmartyPluginsDirectory());
    }

    /**
     * @return PHPUnit\Framework\MockObject\MockObject|EditionRootPathProvider
     */
    protected function getEditionPathSelectorMock()
    {
        $editionSelector = $this->getMockBuilder('EditionRootPathProvider')->setMethods(array('getDirectoryPath'))->getMock();
        $editionSelector->method('getDirectoryPath')->willReturn('/');
        return $editionSelector;
    }
}
