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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setting;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Utility\ShopSettingEncoderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\TransactionServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function is_string;

class SettingDao implements SettingDaoInterface
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
     * @var TransactionServiceInterface
     */
    private $transactionService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param ContextInterface             $context
     * @param ShopSettingEncoderInterface  $shopSettingEncoder
     * @param ShopAdapterInterface         $shopAdapter
     * @param TransactionServiceInterface  $transactionService
     * @param EventDispatcherInterface     $eventDispatcher
     */
    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ContextInterface $context,
        ShopSettingEncoderInterface $shopSettingEncoder,
        ShopAdapterInterface $shopAdapter,
        TransactionServiceInterface $transactionService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->shopSettingEncoder = $shopSettingEncoder;
        $this->shopAdapter = $shopAdapter;
        $this->transactionService = $transactionService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Setting $moduleSetting
     * @param string $moduleId
     * @param int $shopId
     * @throws \Throwable
     */
    public function save(Setting $moduleSetting, string $moduleId, int $shopId): void
    {
        $this->transactionService->begin();

        try {
            /**
             * The same entity was splitted between two tables.
             * Till we can't refactor tables we have to save data in both.
             */
            $this->deleteFromOxConfigTable($moduleSetting, $moduleId, $shopId);
            $this->deleteFromOxConfigDisplayTable($moduleSetting, $moduleId);

            $this->saveDataToOxConfigTable($moduleSetting, $moduleId, $shopId);
            $this->saveDataToOxConfigDisplayTable($moduleSetting, $moduleId);

            $this->transactionService->commit();

            $this->dispatchEvent($moduleSetting, $moduleId, $shopId);
        } catch (\Throwable $throwable) {
            $this->transactionService->rollback();
            throw $throwable;
        }
    }

    /**
     * @param Setting $moduleSetting
     * @param string $moduleId
     * @param int $shopId
     */
    public function delete(Setting $moduleSetting, string $moduleId, int $shopId): void
    {
        $this->deleteFromOxConfigTable($moduleSetting, $moduleId, $shopId);
    }

    /**
     * @param string $name
     * @param string $moduleId
     * @param int    $shopId
     *
     * @return Setting
     * @throws EntryDoesNotExistDaoException
     */
    public function get(string $name, string $moduleId, int $shopId): Setting
    {
        /**
         * The same entity was splitted between two tables.
         * Till we can't refactor tables we have to get data from both.
         */
        $settingsData = array_merge(
            $this->getDataFromOxConfigTable($name, $moduleId, $shopId),
            $this->getDataFromOxConfigDisplayTable($name, $moduleId)
        );

        $setting = new Setting();
        $setting
            ->setName($name)
            ->setValue($this->shopSettingEncoder->decode($settingsData['type'], $settingsData['value']))
            ->setType($settingsData['type']);

        if (
            isset($settingsData['oxvarconstraint'])
            && is_string($settingsData['oxvarconstraint'])
            && $settingsData['oxvarconstraint'] !== ''
        ) {
            $setting->setConstraints(
                explode('|', $settingsData['oxvarconstraint'])
            );
        }

        if (isset($settingsData['oxgrouping'])) {
            $setting->setGroupName($settingsData['oxgrouping']);
        }

        if (isset($settingsData['oxpos'])) {
            $setting->setPositionInGroup(
                (int) $settingsData['oxpos']
            );
        }

        return $setting;
    }

    /**
     * @param Setting $shopModuleSetting
     * @param string $moduleId
     * @param int $shopId
     */
    private function saveDataToOxConfigTable(Setting $shopModuleSetting, string $moduleId, int $shopId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->insert('oxconfig')
            ->values([
                'oxid'          => ':id',
                'oxmodule'      => ':moduleId',
                'oxshopid'      => ':shopId',
                'oxvarname'     => ':name',
                'oxvartype'     => ':type',
                'oxvarvalue'    => ':value',
            ])
            ->setParameters([
                'id'        => $this->shopAdapter->generateUniqueId(),
                'moduleId'  => $this->getPrefixedModuleId($moduleId),
                'shopId'    => $shopId,
                'name'      => $shopModuleSetting->getName(),
                'type'      => $shopModuleSetting->getType(),
                'value'     => $this->shopSettingEncoder->encode(
                    $shopModuleSetting->getType(),
                    $shopModuleSetting->getValue()
                ),
            ]);

        $queryBuilder->execute();
    }

    /**
     * @param Setting $shopModuleSetting
     * @param string $moduleId
     */
    private function saveDataToOxConfigDisplayTable(Setting $shopModuleSetting, string $moduleId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->insert('oxconfigdisplay')
            ->values([
                'oxid'              => ':id',
                'oxcfgmodule'       => ':moduleId',
                'oxcfgvarname'      => ':name',
                'oxgrouping'        => ':groupName',
                'oxpos'             => ':position',
                'oxvarconstraint'   => ':constraints',
            ])
            ->setParameters([
                'id'            => $this->shopAdapter->generateUniqueId(),
                'moduleId'      => $this->getPrefixedModuleId($moduleId),
                'name'          => $shopModuleSetting->getName(),
                'groupName'     => $shopModuleSetting->getGroupName(),
                'position'      => $shopModuleSetting->getPositionInGroup(),
                'constraints'   => implode('|', $shopModuleSetting->getConstraints()),
            ]);

        $queryBuilder->execute();
    }

    /**
     * @param string $name
     * @param string $moduleId
     * @param int    $shopId
     *
     * @return array
     * @throws EntryDoesNotExistDaoException
     */
    private function getDataFromOxConfigTable(string $name, string $moduleId, int $shopId): array
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('oxvarvalue as value, oxvartype as type, oxvarname as name')
            ->from('oxconfig')
            ->where('oxshopid = :shopId')
            ->andWhere('oxmodule = :moduleId')
            ->andWhere('oxvarname = :name')
            ->setParameters([
                'shopId'    => $shopId,
                'moduleId'  => $this->getPrefixedModuleId($moduleId),
                'name'      => $name,
            ]);

        $result = $queryBuilder->execute()->fetch();

        if (false === $result) {
            throw new EntryDoesNotExistDaoException(
                'Setting ' . $name . ' for the module ' . $moduleId . ' doesn\'t exist in the shop with id ' . $shopId
            );
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $moduleId
     * @return array
     */
    private function getDataFromOxConfigDisplayTable(string $name, string $moduleId): array
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('oxgrouping, oxpos, oxvarconstraint')
            ->from('oxconfigdisplay')
            ->where('oxcfgmodule = :moduleId')
            ->andWhere('oxcfgvarname = :name')
            ->setParameters([
                'moduleId'  => $this->getPrefixedModuleId($moduleId),
                'name'      => $name,
            ]);

        $result = $queryBuilder->execute()->fetch();

        return $result ?: [];
    }

    /**
     * @param Setting $shopModuleSetting
     * @param string $moduleId
     * @param int $shopId
     */
    private function deleteFromOxConfigTable(Setting $shopModuleSetting, string $moduleId, int $shopId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxconfig')
            ->where('oxshopid = :shopId')
            ->andWhere('oxvarname = :name')
            ->andWhere('oxmodule = :moduleId')
            ->setParameters([
                'shopId'    => $shopId,
                'name'      => $shopModuleSetting->getName(),
                'moduleId'  => $this->getPrefixedModuleId($moduleId),
            ]);

        $queryBuilder->execute();
    }

    /**
     * @param Setting $shopModuleSetting
     * @param string $moduleId
     */
    private function deleteFromOxConfigDisplayTable(Setting $shopModuleSetting, string $moduleId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxconfigdisplay')
            ->where('oxcfgmodule = :moduleId')
            ->andWhere('oxcfgvarname = :name')
            ->setParameters([
                'moduleId'  => $this->getPrefixedModuleId($moduleId),
                'name'      => $shopModuleSetting->getName(),
            ]);

        $queryBuilder->execute();
    }

    /**
     * @param string $moduleId
     * @return string
     */
    private function getPrefixedModuleId(string $moduleId): string
    {
        return 'module:' . $moduleId;
    }

    /**
     * @param Setting $shopModuleSetting
     * @param string  $moduleId
     * @param int     $shopId
     */
    private function dispatchEvent(Setting $shopModuleSetting, string $moduleId, int $shopId)
    {
        $this->eventDispatcher->dispatch(
            SettingChangedEvent::NAME,
            new SettingChangedEvent(
                $shopModuleSetting->getName(),
                $shopId,
                $moduleId
            )
        );
    }
}
