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

use OxidEsales\EshopCommunity\Core\Edition\EditionSelector;
use OxidEsales\TestingLibrary\UnitTestCase;
use oxRegistry;
use OxidEsales\Eshop\Core\ConfigFile;

// TODO: class should be refactored to testable state.
class EditionSelectorTest extends UnitTestCase
{
    public function testReturnsEdition()
    {
        $editionSelector = new EditionSelector();

        $this->assertSame($this->getConfig()->getEdition(), $editionSelector->getEdition());
    }

    public function testCheckActiveEdition()
    {
        $editionSelector = new EditionSelector();

        $this->assertSame('CE', $editionSelector->getEdition());
        $this->assertTrue($editionSelector->isCommunity());
    }

    public function providerReturnsForcedEdition()
    {
        return array(
            array(EditionSelector::COMMUNITY, 'CE'),
        );
    }

    /**
     * @dataProvider providerReturnsForcedEdition
     */
    public function testReturnsForcedEdition($editionToForce, $expectedEdition)
    {
        $editionSelector = new EditionSelector($editionToForce);

        $this->assertSame($expectedEdition, $editionSelector->getEdition());
    }

    public function testIsCommunityReturnTrueIfForced()
    {
        $editionSelector = new EditionSelector(EditionSelector::COMMUNITY);
        $this->assertTrue($editionSelector->isCommunity());
    }

    public function testForcingEditionByConfig()
    {
        $configFile = oxRegistry::get('oxConfigFile');
        $configFile->setVar('edition', 'CE');

        $editionSelector = new EditionSelector();
        $this->assertTrue($editionSelector->isCommunity());
    }

    public function testForcingEditionByConfigWorksWithLowerCase()
    {
        $configFile = oxRegistry::get('oxConfigFile');
        $configFile->setVar('edition', 'ee');

        $editionSelector = new EditionSelector();
        $this->assertFalse($editionSelector->isCommunity());
    }

    /**
     * When oxConfigFile is not registered in registry (happens during setup), it should be created on the fly.
     */
    public function testForcingEditionByConfigWhenNotRegistered()
    {
        $path = $this->createFile('config.inc.php', '<?php $this->edition = "EE";');
        $fakeConfigFile = new ConfigFile($path);

        $configFile = oxRegistry::get(\OxidEsales\Eshop\Core\ConfigFile::class);
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\ConfigFile::class, $fakeConfigFile);

        $editionSelector = new EditionSelector();
        $this->assertFalse($editionSelector->isCommunity());

        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\ConfigFile::class, $configFile);
    }
}
