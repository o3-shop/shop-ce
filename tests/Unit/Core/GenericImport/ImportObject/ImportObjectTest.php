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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\GenericImport\ImportObject;

use OxidEsales\Eshop\Core\GenericImport\GenericImport;
use OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject;

/**
 * Concrete subclass that lets us drive the abstract ImportObject in tests
 * without touching the database.
 */
class ImportObjectTest_Stub extends ImportObject
{
    /** @var \OxidEsales\Eshop\Core\Model\BaseModel|null */
    public $shopObjectToReturn = null;
    public bool $checkWriteAccessCalled = false;
    public bool $checkCreateAccessCalled = false;
    public bool $preSaveResult = true;
    public bool $preSaveCalled = false;

    public function __construct(string $tableName = 'oxtable', ?array $keyFieldList = null)
    {
        $this->tableName = $tableName;
        $this->keyFieldList = $keyFieldList;
    }

    public function setShopObjectName(?string $name): void
    {
        $this->shopObjectName = $name;
    }

    public function setKeyFieldList(?array $keyFieldList): void
    {
        $this->keyFieldList = $keyFieldList;
    }

    protected function createShopObject()
    {
        return $this->shopObjectToReturn ?: parent::createShopObject();
    }

    public function checkWriteAccess($shopObject, $data = null)
    {
        $this->checkWriteAccessCalled = true;
        parent::checkWriteAccess($shopObject, $data);
    }

    public function checkCreateAccess($data)
    {
        $this->checkCreateAccessCalled = true;
        parent::checkCreateAccess($data);
    }

    protected function preSaveObject($shopObject, $data)
    {
        $this->preSaveCalled = true;
        return $this->preSaveResult;
    }
}

class ImportObjectTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getBaseTableName
     */
    public function testGetBaseTableName(): void
    {
        $importer = new ImportObjectTest_Stub('oxsomething');
        $this->assertSame('oxsomething', $importer->getBaseTableName());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::setFieldList
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getRightFields
     */
    public function testGetRightFieldsWhenFieldListIsAlreadySet(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');
        $importer->setFieldList(['OXID', 'OXTITLE']);

        $this->assertSame(['oxtable__oxid', 'oxtable__oxtitle'], $importer->getRightFields());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getKeyFields
     */
    public function testGetKeyFieldsReturnsConfiguredKeys(): void
    {
        $importer = new ImportObjectTest_Stub('oxt', ['OXID', 'OXSHOPID']);
        $this->assertSame(['OXID', 'OXSHOPID'], $importer->getKeyFields());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getKeyFields
     */
    public function testGetKeyFieldsDefaultsToNull(): void
    {
        $importer = new ImportObjectTest_Stub();
        $this->assertNull($importer->getKeyFields());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::checkWriteAccess
     */
    public function testCheckWriteAccessThrowsWhenObjectIsDerived(): void
    {
        $importer = new ImportObjectTest_Stub();
        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('isDerived')->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(GenericImport::ERROR_USER_NO_RIGHTS);

        $importer->checkWriteAccess($shopObject, []);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::checkWriteAccess
     */
    public function testCheckWriteAccessAllowsNonDerivedObject(): void
    {
        $importer = new ImportObjectTest_Stub();
        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('isDerived')->willReturn(false);

        $importer->checkWriteAccess($shopObject, []);
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::checkCreateAccess
     */
    public function testCheckCreateAccessIsNoopByDefault(): void
    {
        $importer = new ImportObjectTest_Stub();
        $importer->checkCreateAccess(['OXID' => 'x']);
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::saveObject
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::import
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::preAssignObject
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::preSaveObject
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::postSaveObject
     */
    public function testImportCreatesNewObjectAndReturnsId(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');

        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->expects($this->once())->method('load')->with('new-1')->willReturn(false);
        $shopObject->expects($this->once())
            ->method('assign')
            ->with($this->callback(function ($data) {
                $this->assertSame('new-1', $data['OXID']);
                $this->assertNull($data['OXEMPTY']);
                return true;
            }));
        $shopObject->expects($this->once())->method('save')->willReturn(true);
        $shopObject->method('getId')->willReturn('new-1');

        $importer->shopObjectToReturn = $shopObject;

        $result = $importer->import(['OXID' => 'new-1', 'OXEMPTY' => '', 'oxtitle' => 'Foo']);

        $this->assertSame('new-1', $result);
        $this->assertTrue($importer->checkCreateAccessCalled);
        $this->assertFalse($importer->checkWriteAccessCalled);
        $this->assertTrue($importer->preSaveCalled);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::saveObject
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::import
     */
    public function testImportLoadsExistingObjectAndDelegatesToWriteAccessCheck(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');

        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('load')->with('exists-1')->willReturn(true);
        $shopObject->method('isDerived')->willReturn(false);
        $shopObject->method('save')->willReturn(true);
        $shopObject->method('getId')->willReturn('exists-1');
        $shopObject->expects($this->once())->method('assign');

        $importer->shopObjectToReturn = $shopObject;

        $result = $importer->import(['OXID' => 'exists-1']);

        $this->assertSame('exists-1', $result);
        $this->assertTrue($importer->checkWriteAccessCalled);
        $this->assertFalse($importer->checkCreateAccessCalled);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::saveObject
     */
    public function testSaveObjectReturnsFalseWhenSaveFails(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');

        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('load')->willReturn(false);
        $shopObject->method('save')->willReturn(false);

        $importer->shopObjectToReturn = $shopObject;

        $this->assertFalse($importer->import(['OXID' => 'new-x']));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::saveObject
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::preSaveObject
     */
    public function testSaveObjectShortCircuitsWhenPreSaveReturnsFalse(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');
        $importer->preSaveResult = false;

        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('load')->willReturn(false);
        $shopObject->expects($this->never())->method('save');

        $importer->shopObjectToReturn = $shopObject;

        $this->assertFalse($importer->import(['OXID' => 'no-go']));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::saveObject
     */
    public function testSaveObjectUppercasesKeysBeforeAssign(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');

        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('load')->willReturn(false);
        $shopObject->method('save')->willReturn(true);
        $shopObject->method('getId')->willReturn('id-1');
        $shopObject->expects($this->once())
            ->method('assign')
            ->with($this->callback(function ($data) {
                $this->assertArrayHasKey('OXID', $data);
                $this->assertArrayHasKey('OXTITLE', $data);
                $this->assertArrayNotHasKey('oxtitle', $data);
                $this->assertSame('Hello', $data['OXTITLE']);
                return true;
            }));

        $importer->shopObjectToReturn = $shopObject;

        $importer->import(['OXID' => 'id-1', 'oxtitle' => 'Hello']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::saveObject
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::preAssignObject
     */
    public function testSaveObjectRewritesShopId(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');

        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('load')->willReturn(false);
        $shopObject->method('save')->willReturn(true);
        $shopObject->method('getId')->willReturn('id-2');
        $shopObject->expects($this->once())
            ->method('assign')
            ->with($this->callback(function ($data) {
                $expected = (string) \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();
                $this->assertSame($expected, (string) $data['OXSHOPID']);
                return true;
            }));

        $importer->shopObjectToReturn = $shopObject;

        $importer->import(['OXID' => 'id-2', 'OXSHOPID' => '999']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::saveObject
     */
    public function testSaveObjectMarksDerivedAllowedWhenAllowCustomShopId(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable');

        $shopObject = $this->createMock(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $shopObject->method('load')->willReturn(false);
        $shopObject->method('save')->willReturn(true);
        $shopObject->method('getId')->willReturn('id-3');
        $shopObject->expects($this->once())
            ->method('setIsDerived')
            ->with(false);

        $importer->shopObjectToReturn = $shopObject;

        $method = new \ReflectionMethod($importer, 'saveObject');
        $method->setAccessible(true);
        $method->invoke($importer, ['OXID' => 'id-3'], true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::checkIdField
     */
    public function testCheckIdFieldRejectsEmpty(): void
    {
        $importer = new ImportObjectTest_Stub();
        $method = new \ReflectionMethod($importer, 'checkIdField');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $method->invoke($importer, '');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::checkIdField
     */
    public function testCheckIdFieldRejectsTooLong(): void
    {
        $importer = new ImportObjectTest_Stub();
        $method = new \ReflectionMethod($importer, 'checkIdField');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('longer then allowed');
        $method->invoke($importer, str_repeat('y', 33));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::checkIdField
     */
    public function testCheckIdFieldAcceptsValid(): void
    {
        $importer = new ImportObjectTest_Stub();
        $method = new \ReflectionMethod($importer, 'checkIdField');
        $method->setAccessible(true);

        $method->invoke($importer, 'valid-id-32-chars-or-less');
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getOxidFromKeyFields
     */
    public function testGetOxidFromKeyFieldsReturnsNullWhenNoKeysConfigured(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable', null);

        $method = new \ReflectionMethod($importer, 'getOxidFromKeyFields');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($importer, []));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getOxidFromKeyFields
     */
    public function testGetOxidFromKeyFieldsReturnsNullWhenNotAllKeysPresent(): void
    {
        $importer = new ImportObjectTest_Stub('oxtable', ['OXID', 'OXSHOPID']);

        $method = new \ReflectionMethod($importer, 'getOxidFromKeyFields');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($importer, ['OXID' => '1']));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getShopObjectName
     */
    public function testGetShopObjectNameReturnsConfiguredValue(): void
    {
        $importer = new ImportObjectTest_Stub();
        $importer->setShopObjectName('oxarticle');

        $method = new \ReflectionMethod($importer, 'getShopObjectName');
        $method->setAccessible(true);

        $this->assertSame('oxarticle', $method->invoke($importer));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getTableName
     */
    public function testGetTableNameDelegatesToViewName(): void
    {
        $importer = new ImportObjectTest_Stub('oxarticles');

        $method = new \ReflectionMethod($importer, 'getTableName');
        $method->setAccessible(true);

        $shopId = (string) \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();
        $expected = getViewName('oxarticles', -1, $shopId);
        $this->assertSame($expected, $method->invoke($importer));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::createShopObject
     */
    public function testCreateShopObjectFallsBackToOxBaseForUnnamedTable(): void
    {
        $importer = new ImportObjectTest_Stub('oxarticles');
        $importer->setShopObjectName(null);

        $reflection = new \ReflectionMethod(ImportObject::class, 'createShopObject');
        $reflection->setAccessible(true);
        $shopObject = $reflection->invoke($importer);

        $this->assertInstanceOf(\OxidEsales\Eshop\Core\Model\BaseModel::class, $shopObject);
        $this->assertSame('oxarticles', $shopObject->getCoreTableName());
    }
}
