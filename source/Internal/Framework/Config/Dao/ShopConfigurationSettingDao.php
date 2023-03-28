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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Config\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Config\Utility\ShopSettingEncoderInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\Event\ShopConfigurationChangedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShopConfigurationSettingDao implements ShopConfigurationSettingDaoInterface
{
    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ShopSettingEncoderInterface
     */
    private $shopSettingEncoder;

    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param ContextInterface             $context
     * @param ShopSettingEncoderInterface  $shopSettingEncoder
     * @param ShopAdapterInterface         $shopAdapter
     * @param EventDispatcherInterface     $eventDispatcher
     */
    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ContextInterface $context,
        ShopSettingEncoderInterface $shopSettingEncoder,
        ShopAdapterInterface $shopAdapter,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->shopSettingEncoder = $shopSettingEncoder;
        $this->shopAdapter = $shopAdapter;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ShopConfigurationSetting $shopConfigurationSetting
     */
    public function save(ShopConfigurationSetting $shopConfigurationSetting)
    {
        $this->delete($shopConfigurationSetting);

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->insert('oxconfig')
            ->values([
                'oxid'          => ':id',
                'oxshopid'      => ':shopId',
                'oxvarname'     => ':name',
                'oxvartype'     => ':type',
                'oxvarvalue'    => ':value',
            ])
            ->setParameters([
                'id'        => $this->shopAdapter->generateUniqueId(),
                'shopId'    => $shopConfigurationSetting->getShopId(),
                'name'      => $shopConfigurationSetting->getName(),
                'type'      => $shopConfigurationSetting->getType(),
                'value'     => $this->shopSettingEncoder->encode(
                    $shopConfigurationSetting->getType(),
                    $shopConfigurationSetting->getValue()
                ),
            ]);

        $queryBuilder->execute();

        $this->eventDispatcher->dispatch(
            ShopConfigurationChangedEvent::NAME,
            new ShopConfigurationChangedEvent(
                $shopConfigurationSetting->getName(),
                $shopConfigurationSetting->getShopId()
            )
        );
    }

    /**
     * @param string $name
     * @param int    $shopId
     * @return ShopConfigurationSetting
     * @throws EntryDoesNotExistDaoException
     */
    public function get(string $name, int $shopId): ShopConfigurationSetting
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('oxvarvalue as value, oxvartype as type, oxvarname as name')
            ->from('oxconfig')
            ->where('oxshopid = :shopId')
            ->andWhere('oxvarname = :name')
            ->andWhere('oxmodule = ""')
            ->setParameters([
                'shopId'    => $shopId,
                'name'      => $name
            ]);

        $result = $queryBuilder->execute()->fetch();

        if (false === $result) {
            throw new EntryDoesNotExistDaoException(
                'Setting ' . $name . ' doesn\'t exist in the shop with id ' . $shopId
            );
        }

        $setting = new ShopConfigurationSetting();
        $setting
            ->setName($name)
            ->setValue($this->shopSettingEncoder->decode($result['type'], $result['value']))
            ->setShopId($shopId)
            ->setType($result['type']);

        return $setting;
    }

    /**
     * @param ShopConfigurationSetting $setting
     */
    public function delete(ShopConfigurationSetting $setting)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxconfig')
            ->where('oxshopid = :shopId')
            ->andWhere('oxvarname = :name')
            ->andWhere('oxmodule = ""')
            ->setParameters([
                'shopId'    => $setting->getShopId(),
                'name'      => $setting->getName(),
            ]);

        $queryBuilder->execute();
    }
}
