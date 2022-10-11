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

namespace OxidEsales\EshopCommunity\Core\Module;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;

/**
 * Class responsible for cleaning not used extensions for module which is going to be activated.
 *
 * @deprecated since v6.4.0 (2019-03-22); the whole chain is updated during module activation and deactivation in the database we do not need this functionality any more
 * @package  OxidEsales\EshopCommunity\Core\Module
 * @internal Do not make a module extension for this class.
 */
class ModuleExtensionsCleaner
{

    /**
     * Removes garbage ( module not used extensions ) from all installed extensions list.
     * For example: some classes were renamed, so these should be removed.
     *
     * @param array                                $installedExtensions
     * @param \OxidEsales\Eshop\Core\Module\Module $module
     *
     * @return array
     * @throws ModuleConfigurationNotFoundException
     */
    public function cleanExtensions($installedExtensions, \OxidEsales\Eshop\Core\Module\Module $module)
    {
        $moduleExtensions = $module->getExtensions();

        $installedModuleExtensions = $this->filterExtensionsByModuleId($installedExtensions, $module->getId());

        if (count($installedModuleExtensions)) {
            $garbage = $this->getModuleExtensionsGarbage($moduleExtensions, $installedModuleExtensions);

            if (count($garbage)) {
                $installedExtensions = $this->removeGarbage($installedExtensions, $garbage);
            }
        }

        return $installedExtensions;
    }

    /**
     * Returns extensions list by module id.
     *
     * @param array  $installedExtensions
     * @param string $moduleId
     *
     * @return array
     * @throws ModuleConfigurationNotFoundException
     */
    private function filterExtensionsByModuleId(array $installedExtensions, string $moduleId)
    {
        $container = ContainerFactory::getInstance()->getContainer();

        $moduleConfiguration = $container
            ->get(ShopConfigurationDaoBridgeInterface::class)
            ->get()
            ->getModuleConfiguration($moduleId);

        $filteredExtensions = [];

        foreach ($installedExtensions as $class => $extend) {
            foreach ($extend as $extendPath) {
                if (strpos($extendPath, $moduleConfiguration->getPath()) === 0) {
                    $filteredExtensions[$class][] = $extendPath;
                }
            }
        }

        return $filteredExtensions;
    }

    /**
     * Returns extension which is no longer in metadata - garbage
     *
     * @param array $moduleMetaDataExtensions  extensions defined in metadata.
     * @param array $moduleInstalledExtensions extensions which are installed
     *
     * @return array
     */
    protected function getModuleExtensionsGarbage($moduleMetaDataExtensions, $moduleInstalledExtensions)
    {
        $garbage = $moduleInstalledExtensions;

        foreach ($garbage as $installedClassName => $installedClassPaths) {
            if (isset($moduleMetaDataExtensions[$installedClassName])) {
                // In case more than one extension is specified per module.
                $metaDataExtensionPaths = $moduleMetaDataExtensions[$installedClassName];
                if (!is_array($metaDataExtensionPaths)) {
                    $metaDataExtensionPaths = [$metaDataExtensionPaths];
                }

                foreach ($installedClassPaths as $index => $installedClassPath) {
                    if (in_array($installedClassPath, $metaDataExtensionPaths)) {
                        unset($garbage[$installedClassName][$index]);
                    }
                }

                if (count($garbage[$installedClassName]) == 0) {
                    unset($garbage[$installedClassName]);
                }
            }
        }

        return $garbage;
    }

    /**
     * Removes garbage - not exiting module extensions, returns clean array of installed extensions
     *
     * @param array $installedExtensions all installed extensions ( from all modules )
     * @param array $garbage             extension which are not used and should be removed
     *
     * @return array
     */
    protected function removeGarbage($installedExtensions, $garbage)
    {
        foreach ($garbage as $className => $classPaths) {
            foreach ($classPaths as $sClassPath) {
                if (isset($installedExtensions[$className])) {
                    unset($installedExtensions[$className][array_search($sClassPath, $installedExtensions[$className])]);
                    if (count($installedExtensions[$className]) == 0) {
                        unset($installedExtensions[$className]);
                    }
                }
            }
        }

        return $installedExtensions;
    }
}
