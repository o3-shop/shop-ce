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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ExtensionNotInChainException;

class ModuleClassExtensionsMergingService implements ModuleClassExtensionsMergingServiceInterface
{
    /**
     * @param ShopConfiguration   $shopConfiguration
     * @param ModuleConfiguration $moduleConfiguration
     *
     * @return ClassExtensionsChain
     * @throws ExtensionNotInChainException
     * @throws ModuleConfigurationNotFoundException
     */
    public function merge(
        ShopConfiguration $shopConfiguration,
        ModuleConfiguration $moduleConfiguration
    ): ClassExtensionsChain {

        $chain = $shopConfiguration->getClassExtensionsChain();

        if (!$shopConfiguration->hasModuleConfiguration($moduleConfiguration->getId())) {
            $chain->addExtensions($moduleConfiguration->getClassExtensions());
        } else {
            $chain = $this->addNewModuleExtensionsToChain($moduleConfiguration, $shopConfiguration, $chain);
            $chain = $this->replaceExistingModuleExtensionsInChain($moduleConfiguration, $shopConfiguration, $chain);
            $chain = $this->removeDeletedModuleExtensionsFromChain($moduleConfiguration, $shopConfiguration, $chain);
        }

        return $chain;
    }

    /**
     * @param ModuleConfiguration  $moduleConfiguration
     * @param ShopConfiguration    $shopConfiguration
     * @param ClassExtensionsChain $classExtensionChain
     *
     * @return ClassExtensionsChain
     * @throws ModuleConfigurationNotFoundException
     * @throws ExtensionNotInChainException
     */
    private function removeDeletedModuleExtensionsFromChain(
        ModuleConfiguration $moduleConfiguration,
        ShopConfiguration $shopConfiguration,
        ClassExtensionsChain $classExtensionChain
    ): ClassExtensionsChain {
        $existentModuleConfiguration = $shopConfiguration->getModuleConfiguration(
            $moduleConfiguration->getId()
        );

        foreach ($existentModuleConfiguration->getClassExtensions() as $extension) {
            if (!$this->isExtendingShopClass($extension, $moduleConfiguration->getClassExtensions())) {
                $classExtensionChain->removeExtension($extension);
            }
        }

        return $classExtensionChain;
    }

    /**
     * @param ModuleConfiguration  $moduleConfiguration
     * @param ShopConfiguration    $shopConfiguration
     * @param ClassExtensionsChain $chain
     *
     * @return ClassExtensionsChain
     * @throws ModuleConfigurationNotFoundException
     */
    private function replaceExistingModuleExtensionsInChain(
        ModuleConfiguration $moduleConfiguration,
        ShopConfiguration $shopConfiguration,
        ClassExtensionsChain $chain
    ): ClassExtensionsChain {
        $existentModuleConfiguration = $shopConfiguration->getModuleConfiguration(
            $moduleConfiguration->getId()
        );

        foreach ($existentModuleConfiguration->getClassExtensions() as $existingExtension) {
            foreach ($moduleConfiguration->getClassExtensions() as $newExtension) {
                if ($this->areExtensionsEqual($existingExtension, $newExtension)) {
                    $this->replaceExistingExtension($chain, $existingExtension, $newExtension);
                }
            }
        }

        return $chain;
    }

    /**
     * @param ModuleConfiguration  $moduleConfiguration
     * @param ShopConfiguration    $shopConfiguration
     * @param ClassExtensionsChain $chain
     *
     * @return ClassExtensionsChain
     * @throws ModuleConfigurationNotFoundException
     */
    private function addNewModuleExtensionsToChain(
        ModuleConfiguration $moduleConfiguration,
        ShopConfiguration $shopConfiguration,
        ClassExtensionsChain $chain
    ): ClassExtensionsChain {
        foreach ($moduleConfiguration->getClassExtensions() as $classExtension) {
            $existentModuleConfiguration = $shopConfiguration->getModuleConfiguration(
                $moduleConfiguration->getId()
            );

            if (!$existentModuleConfiguration->isExtendingShopClass($classExtension->getShopClassName())) {
                $chain->addExtension($classExtension);
            }
        }

        return $chain;
    }

    /**
     * @param ClassExtension $existingClassExtension
     * @param ClassExtension[]          $newClassExtensions
     *
     * @return bool
     */
    private function isExtendingShopClass(ClassExtension $existingClassExtension, array $newClassExtensions): bool
    {
        foreach ($newClassExtensions as $newExtension) {
            if ($newExtension->getShopClassName() === $existingClassExtension->getShopClassName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassExtension $existingExtension
     * @param ClassExtension $newExtension
     *
     * @return bool
     */
    private function areExtensionsEqual(ClassExtension $existingExtension, ClassExtension $newExtension): bool
    {
        return $existingExtension->getShopClassName() === $newExtension->getShopClassName()
               && $existingExtension->getModuleExtensionClassName() !==
                  $newExtension->getModuleExtensionClassName();
    }

    /**
     * Converts e.g. the chain [Class1, ClassOld, Class3] to [Class1, ClassNew, Class3]. Keeping the order is important
     * as the order can be changed in O3-Shop admin.
     *
     * @param ClassExtensionsChain $chain
     * @param ClassExtension       $existingExtension
     * @param ClassExtension       $newExtension
     */
    private function replaceExistingExtension(
        ClassExtensionsChain $chain,
        ClassExtension $existingExtension,
        ClassExtension $newExtension
    ): void {
        $classExtensionChain = $chain->getChain();
        $shopClassNamespaceInChain = $classExtensionChain[$existingExtension->getShopClassName()];
        foreach ($shopClassNamespaceInChain as $key => $existingExtensionInChain) {
            if ($existingExtensionInChain === $existingExtension->getModuleExtensionClassName()) {
                $classExtensionChain[$existingExtension->getShopClassName()][$key] =
                    $newExtension->getModuleExtensionClassName();
            }
        }

        $chain->setChain($classExtensionChain);
    }
}
