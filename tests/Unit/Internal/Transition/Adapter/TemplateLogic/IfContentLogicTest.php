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

use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IfContentLogic;

class IfContentLogicTest_StubContent extends Content
{
    public static bool $loadByIdentReturns = true;
    public static bool $loadReturns = true;
    public static bool $isActive = true;
    public static string $oxid = 'content-7';
    public static string $loadId = 'about';

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

    public function isActive()
    {
        return self::$isActive;
    }

    public function getId()
    {
        return self::$oxid;
    }

    public function getLoadId()
    {
        return self::$loadId;
    }
}

class IfContentLogicTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        IfContentLogicTest_StubContent::$loadByIdentReturns = true;
        IfContentLogicTest_StubContent::$loadReturns = true;
        IfContentLogicTest_StubContent::$isActive = true;
    }

    public function testGetContentByOxidReturnsLoadedActiveContent(): void
    {
        $stub = new IfContentLogicTest_StubContent();
        \oxTestModules::addModuleObject('oxContent', $stub);

        $logic = new IfContentLogic();
        $result = $logic->getContent(null, 'content-7');
        $this->assertSame($stub, $result);
        $this->assertSame('content-7', $stub->loadedByOxid);
    }

    public function testGetContentByIdentLoadsViaLoadByIdent(): void
    {
        $stub = new IfContentLogicTest_StubContent();
        IfContentLogicTest_StubContent::$loadId = 'about-different-' . uniqid();
        IfContentLogicTest_StubContent::$oxid = 'content-different-' . uniqid();
        \oxTestModules::addModuleObject('oxContent', $stub);

        $logic = new IfContentLogic();
        $result = $logic->getContent(IfContentLogicTest_StubContent::$loadId);
        $this->assertSame($stub, $result);
        $this->assertSame(IfContentLogicTest_StubContent::$loadId, $stub->loadedByIdent);
    }

    public function testGetContentReturnsFalseWhenLoadFails(): void
    {
        $stub = new IfContentLogicTest_StubContent();
        IfContentLogicTest_StubContent::$loadReturns = false;
        \oxTestModules::addModuleObject('oxContent', $stub);

        $logic = new IfContentLogic();
        $this->assertFalse($logic->getContent(null, 'missing-' . uniqid()));
    }

    public function testGetContentReturnsFalseWhenInactive(): void
    {
        $stub = new IfContentLogicTest_StubContent();
        IfContentLogicTest_StubContent::$isActive = false;
        \oxTestModules::addModuleObject('oxContent', $stub);

        $logic = new IfContentLogic();
        $this->assertFalse($logic->getContent(null, 'inactive-' . uniqid()));
    }
}
