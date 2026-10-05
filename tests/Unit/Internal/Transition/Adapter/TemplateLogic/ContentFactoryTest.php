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

use OxidEsales\EshopCommunity\Application\Model\Content;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\ContentFactory;

class ContentFactoryTest_StubContent extends Content
{
    public static bool $loadByIdentReturns = true;
    public static bool $loadReturns = true;

    public ?string $loadedByIdent = null;
    public ?string $loadedByOxid = null;

    public function __construct()
    {
    }

    public function loadByIdent($loadId, $onlyActive = false)
    {
        $this->loadedByIdent = (string) $loadId;
        return self::$loadByIdentReturns;
    }

    public function load($oxid)
    {
        $this->loadedByOxid = (string) $oxid;
        return self::$loadReturns;
    }
}

class ContentFactoryTest extends \OxidTestCase
{
    public function testGetContentByIdentLoadsViaLoadByIdent(): void
    {
        $stub = new ContentFactoryTest_StubContent();
        ContentFactoryTest_StubContent::$loadByIdentReturns = true;
        \oxTestModules::addModuleObject('oxcontent', $stub);

        $result = (new ContentFactory())->getContent('ident', 'about-us');
        $this->assertSame($stub, $result);
        $this->assertSame('about-us', $stub->loadedByIdent);
    }

    public function testGetContentByOxidLoadsViaLoad(): void
    {
        $stub = new ContentFactoryTest_StubContent();
        ContentFactoryTest_StubContent::$loadReturns = true;
        \oxTestModules::addModuleObject('oxcontent', $stub);

        $result = (new ContentFactory())->getContent('oxid', 'content-7');
        $this->assertSame($stub, $result);
        $this->assertSame('content-7', $stub->loadedByOxid);
    }

    public function testGetContentReturnsNullWhenLoadFails(): void
    {
        $stub = new ContentFactoryTest_StubContent();
        ContentFactoryTest_StubContent::$loadReturns = false;
        \oxTestModules::addModuleObject('oxcontent', $stub);

        $this->assertNull((new ContentFactory())->getContent('oxid', 'missing'));
    }

    public function testGetContentThrowsForUnknownKey(): void
    {
        $stub = new ContentFactoryTest_StubContent();
        \oxTestModules::addModuleObject('oxcontent', $stub);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot load content');
        (new ContentFactory())->getContent('something-else', 'value');
    }
}
