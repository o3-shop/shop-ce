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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Smarty;

use \oxRegistry;

$filePath = oxRegistry::getConfig()->getConfigParam('sCoreDir') . 'Smarty/Plugin/modifier.oxfilesize.php';
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    require_once dirname(__FILE__) . '/../../../../source/Core/Smarty/Plugin/modifier.oxfilesize.php';
}


/**
 * Smarty modifier test case
 */
class FilesizeTest extends \OxidTestCase
{

    /**
     * Byte result test
     *
     * @return null
     */
    public function testOxFileSizeBytes()
    {
        $iSize = 1023;
        $sRes = smarty_modifier_oxfilesize($iSize);
        $this->assertEquals("1023 B", $sRes);
    }

    /**
     * KiloByte result test
     *
     * @return null
     */
    public function testOxFileSizeKiloBytes()
    {
        $iSize = 1025;
        $sRes = smarty_modifier_oxfilesize($iSize);
        $this->assertEquals("1.0 KB", $sRes);
    }

    /**
     * MegaByte result test
     *
     * @return null
     */
    public function testOxFileSizeMegaBytes()
    {
        $iSize = 1024 * 1024 * 1.1;
        $sRes = smarty_modifier_oxfilesize($iSize);

        $this->assertEquals("1.1 MB", $sRes);
    }

    /**
     * GigaByte result test
     *
     * @return null
     */
    public function testOxFileSizeGigaBytes()
    {
        $iSize = 1024 * 1024 * 1024 * 1.3;
        $sRes = smarty_modifier_oxfilesize($iSize);

        $this->assertEquals("1.3 GB", $sRes);
    }
}
