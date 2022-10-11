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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\TemplateBlock;
use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

class TemplateBlockModuleSettingHandler implements ModuleConfigurationHandlerInterface
{
    /** @var TemplateBlockExtensionDaoInterface */
    private $templateBlockExtensionDao;

    /** @param TemplateBlockExtensionDaoInterface $templateBlockExtensionDao */
    public function __construct(TemplateBlockExtensionDaoInterface $templateBlockExtensionDao)
    {
        $this->templateBlockExtensionDao = $templateBlockExtensionDao;
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     */
    public function handleOnModuleActivation(ModuleConfiguration $configuration, int $shopId)
    {
        if ($configuration->hasTemplateBlocks()) {
            foreach ($configuration->getTemplateBlocks() as $templateBlock) {
                $templateBlockExtension = $this->mapDataToObject($templateBlock);
                $templateBlockExtension->setShopId($shopId);
                $templateBlockExtension->setModuleId($configuration->getId());

                $this->templateBlockExtensionDao->add($templateBlockExtension);
            }
        }
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     */
    public function handleOnModuleDeactivation(ModuleConfiguration $configuration, int $shopId)
    {
        if ($configuration->hasTemplateBlocks()) {
            $this->templateBlockExtensionDao->deleteExtensions($configuration->getId(), $shopId);
        }
    }

    /**
     * @param TemplateBlock $templateBlock
     * @return TemplateBlockExtension
     */
    private function mapDataToObject(TemplateBlock $templateBlock): TemplateBlockExtension
    {
        $templateBlockExtension = new TemplateBlockExtension();
        $templateBlockExtension
            ->setName($templateBlock->getBlockName())
            ->setFilePath($templateBlock->getModuleTemplatePath())
            ->setExtendedBlockTemplatePath($templateBlock->getShopTemplatePath());

        if ($templateBlock->getPosition() !== 0) {
            $templateBlockExtension->setPosition(
                $templateBlock->getPosition()
            );
        }

        if ($templateBlock->getTheme() !== '') {
            $templateBlockExtension->setThemeId(
                $templateBlock->getTheme()
            );
        }

        return $templateBlockExtension;
    }
}
