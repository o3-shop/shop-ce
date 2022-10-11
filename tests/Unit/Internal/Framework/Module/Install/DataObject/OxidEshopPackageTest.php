<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Install\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use PHPUnit\Framework\TestCase;

class OxidEshopPackageTest extends TestCase
{
    public function testGetTargetDirectoryIfCustomDirectoryIdSet()
    {
        $package = $this->getPackage();
        $this->assertSame(
            'customTargetDir',
            $package->getTargetDirectory()
        );
    }

    public function testGetTargetDirectoryReturnPackageNameIfCustomDirectoryIsNotSet()
    {
        $package = new OxidEshopPackage(
            'shinyPackage',
            'pathToPackage'
        );

        $this->assertSame(
            'shinyPackage',
            $package->getTargetDirectory()
        );
    }

    public function testGetPackageSourcePath()
    {
        $package = new OxidEshopPackage(
            'shinyPackage',
            'pathToPackage'
        );

        $this->assertSame(
            'pathToPackage',
            $package->getPackageSourcePath()
        );
    }

    public function testGetPackageSourcePathIfCustomDirectoryIdSet()
    {
        $package = $this->getPackage();
        $this->assertSame(
            'pathToPackage/customSourceDir',
            $package->getPackageSourcePath()
        );
    }

    public function testGetBlackListFilters()
    {
        $package = $this->getPackage();
        $this->assertSame(
            ['blackDir'],
            $package->getBlackListFilters()
        );
    }

    private function getPackage(): OxidEshopPackage
    {
        $package = new OxidEshopPackage(
            'shinyPackage',
            'pathToPackage'
        );
        $package->setTargetDirectory('customTargetDir');
        $package->setBlackListFilters(['blackDir']);
        $package->setSourceDirectory('customSourceDir');

        return $package;
    }
}
