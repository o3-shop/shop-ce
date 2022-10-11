<?php
declare(strict_types=1);

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

namespace OxidEsales\EshopCommunity\Internal\Framework\Config\Dao;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ShopConfigurationSettingDaoTest extends TestCase
{
    use ContainerTrait;

    /**
     * @dataProvider settingValueDataProvider
     */
    public function testSave(string $name, string $type, $value)
    {
        $settingDao = $this->getConfigurationSettingDao();

        $shopConfigurationSetting = new ShopConfigurationSetting();
        $shopConfigurationSetting
            ->setShopId(1)
            ->setName($name)
            ->setType($type)
            ->setValue($value);

        $settingDao->save($shopConfigurationSetting);

        $this->assertEquals(
            $shopConfigurationSetting,
            $settingDao->get($name, 1)
        );
    }

    public function testGetNonExistentSetting()
    {
        $settingDao = $this->getConfigurationSettingDao();

        $this->expectException(EntryDoesNotExistDaoException::class);
        $settingDao->get('onExistentSetting', 1);
    }

    /**
     * Checks if DAO is compatible with OxidEsales\Eshop\Core\Config
     *
     * @dataProvider settingValueDataProvider
     */
    public function testBackwardsCompatibility(string $name, string $type, $value)
    {
        $settingDao = $this->getConfigurationSettingDao();

        $shopConfigurationSetting = new ShopConfigurationSetting();
        $shopConfigurationSetting
            ->setShopId(1)
            ->setName($name)
            ->setType($type)
            ->setValue($value);

        $settingDao->save($shopConfigurationSetting);

        $this->assertSame(
            $settingDao->get($name, 1)->getValue(),
            Registry::getConfig()->getShopConfVar($name, 1)
        );
    }

    public function testDelete()
    {
        $this->expectException(EntryDoesNotExistDaoException::class);
        $settingDao = $this->getConfigurationSettingDao();

        $shopConfigurationSetting = new ShopConfigurationSetting();
        $shopConfigurationSetting
            ->setShopId(1)
            ->setName('testDelete')
            ->setType('someType')
            ->setValue('value');

        $settingDao->save($shopConfigurationSetting);

        $settingDao->delete($shopConfigurationSetting);
        $settingDao->get('testDelete', 1);
    }

    public function testUpdate()
    {
        $settingDao = $this->getConfigurationSettingDao();

        $shopConfigurationSetting = new ShopConfigurationSetting();
        $shopConfigurationSetting
            ->setShopId(1)
            ->setName('testUpdate')
            ->setType('someType')
            ->setValue('firstSaving');

        $settingDao->save($shopConfigurationSetting);

        $shopConfigurationSetting->setValue('secondSaving');

        $settingDao->save($shopConfigurationSetting);

        $this->assertEquals(
            $shopConfigurationSetting,
            $settingDao->get('testUpdate', 1)
        );
    }

    public function testUpdateDoesNotCreateDuplicationsInDatabase()
    {
        $this->assertSame(
            0,
            $this->getRowCount('testDuplications', 1)
        );

        $settingDao = $this->getConfigurationSettingDao();

        $shopConfigurationSetting = new ShopConfigurationSetting();
        $shopConfigurationSetting
            ->setShopId(1)
            ->setName('testDuplications')
            ->setType('someType')
            ->setValue('firstSaving');

        $settingDao->save($shopConfigurationSetting);

        $this->assertSame(
            1,
            $this->getRowCount('testDuplications', 1)
        );

        $shopConfigurationSetting->setValue('secondSaving');

        $settingDao->save($shopConfigurationSetting);

        $this->assertSame(
            1,
            $this->getRowCount('testDuplications', 1)
        );
    }

    public function settingValueDataProvider()
    {
        return [
            [
                'string',
                'str',
                'testString',
            ],
            [
                'int',
                'int',
                1,
            ],
            [
                'float',
                'num',
                1.333,
            ],
            [
                'bool',
                'bool',
                true,
            ],
            [
                'array',
                'arr',
                [
                    'element'   => 'value',
                    'element2'  => 'value',
                ],
            ],
        ];
    }

    private function getConfigurationSettingDao(): ShopConfigurationSettingDaoInterface
    {
        return $this->get(ShopConfigurationSettingDaoInterface::class);
    }

    private function getRowCount(string $settingName, int $shopId): int
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder
            ->select('*')
            ->from('oxconfig')
            ->where('oxshopid = :shopId')
            ->andWhere('oxvarname = :name')
            ->andWhere('oxmodule = ""')
            ->setParameters([
                'shopId'    => $shopId,
                'name'      => $settingName,
            ]);

        return $queryBuilder->execute()->rowCount();
    }
}
