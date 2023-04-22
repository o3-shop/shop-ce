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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class TemplateBlockExtensionDao implements TemplateBlockExtensionDaoInterface
{
    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * TemplateBlockExtensionDao constructor.
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param ShopAdapterInterface         $shopAdapter
     */
    public function __construct(QueryBuilderFactoryInterface $queryBuilderFactory, ShopAdapterInterface $shopAdapter)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->shopAdapter = $shopAdapter;
    }

    /**
     * @param TemplateBlockExtension $templateBlockExtension
     */
    public function add(TemplateBlockExtension $templateBlockExtension)
    {
        if ($this->exists($templateBlockExtension)) {
            return;
        };

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->insert('oxtplblocks')
            ->values([
                'oxid'          => ':id',
                'oxshopid'      => ':shopId',
                'oxmodule'      => ':moduleId',
                'oxtheme'       => ':themeId',
                'oxblockname'   => ':name',
                'oxfile'        => ':filePath',
                'oxtemplate'    => ':templatePath',
                'oxpos'         => ':priority',
                'oxactive'      => '1',
            ])
            ->setParameters([
                'id'            => $this->shopAdapter->generateUniqueId(),
                'shopId'        => $templateBlockExtension->getShopId(),
                'moduleId'      => $templateBlockExtension->getModuleId(),
                'themeId'       => $templateBlockExtension->getThemeId(),
                'name'          => $templateBlockExtension->getName(),
                'filePath'      => $templateBlockExtension->getFilePath(),
                'templatePath'  => $templateBlockExtension->getExtendedBlockTemplatePath(),
                'priority'      => $templateBlockExtension->getPosition(),
            ]);

        $queryBuilder->execute();
    }

    public function exists(TemplateBlockExtension $templateBlockExtension)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select(1)
            ->from('oxtplblocks')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('oxshopid', ':shopId'),
                    $queryBuilder->expr()->eq('oxmodule', ':moduleId'),
                    $queryBuilder->expr()->eq('oxtheme', ':themeId'),
                    $queryBuilder->expr()->eq('oxblockname', ':name'),
                    $queryBuilder->expr()->eq('oxfile', ':filePath'),
                    $queryBuilder->expr()->eq('oxtemplate', ':templatePath')
                )
            )
            ->setParameters([
                'shopId'        => $templateBlockExtension->getShopId(),
                'moduleId'      => $templateBlockExtension->getModuleId(),
                'themeId'       => $templateBlockExtension->getThemeId(),
                'name'          => $templateBlockExtension->getName(),
                'filePath'      => $templateBlockExtension->getFilePath(),
                'templatePath'  => $templateBlockExtension->getExtendedBlockTemplatePath(),
            ]);

        return (bool) $queryBuilder->execute()->fetchOne();
    }

    /**
     * @param string $name
     * @param int    $shopId
     * @return array
     */
    public function getExtensions(string $name, int $shopId): array
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('*')
            ->from('oxtplblocks')
            ->where('oxshopid = :shopId')
            ->andWhere('oxblockname = :name')
            ->andWhere('oxmodule != \'\'')
            ->setParameters([
                'shopId'    => $shopId,
                'name'      => $name,
            ]);

        $blocksData = $queryBuilder->execute()->fetchAll();

        return $this->mapDataToObjects($blocksData);
    }

    /**
     * @param string $moduleId
     * @param int    $shopId
     */
    public function deleteExtensions(string $moduleId, int $shopId)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxtplblocks')
            ->where('oxshopid = :shopId')
            ->andWhere('oxmodule = :moduleId')
            ->setParameters([
                'shopId'    => $shopId,
                'moduleId'  => $moduleId,
            ]);

        $queryBuilder->execute();
    }

    /**
     * @param array $blocksData
     * @return array
     */
    private function mapDataToObjects(array $blocksData): array
    {
        $templateBlockExtensions = [];

        foreach ($blocksData as $blockData) {
            $templateBlock = new TemplateBlockExtension();
            $templateBlock
                ->setShopId(
                    (int) $blockData['OXSHOPID']
                )
                ->setModuleId(
                    $blockData['OXMODULE']
                )
                ->setThemeId(
                    $blockData['OXTHEME']
                )
                ->setName(
                    $blockData['OXBLOCKNAME']
                )
                ->setFilePath(
                    $blockData['OXFILE']
                )
                ->setExtendedBlockTemplatePath(
                    $blockData['OXTEMPLATE']
                )
                ->setPosition(
                    (int) $blockData['OXPOS']
                );

            $templateBlockExtensions[] = $templateBlock;
        }

        return $templateBlockExtensions;
    }
}
