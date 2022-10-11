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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\InvalidClassExtensionNamespaceException;

class ClassExtensionsValidator implements ModuleConfigurationValidatorInterface
{
    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * ClassExtensionsModuleSettingValidator constructor.
     * @param ShopAdapterInterface $shopAdapter
     */
    public function __construct(ShopAdapterInterface $shopAdapter)
    {
        $this->shopAdapter = $shopAdapter;
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     *
     * @throws InvalidClassExtensionNamespaceException
     */
    public function validate(ModuleConfiguration $configuration, int $shopId)
    {
        if ($configuration->hasClassExtensions()) {
            foreach ($configuration->getClassExtensions() as $extension) {
                if ($this->shopAdapter->isNamespace($extension->getShopClassName())) {
                    $this->validateClassToBePatchedNamespace($extension->getShopClassName());
                }
            }
        }
    }

    /**
     * @param string $namespace
     * @throws InvalidClassExtensionNamespaceException
     */
    private function validateClassToBePatchedNamespace(string $namespace)
    {
        if ($this->shopAdapter->isShopEditionNamespace($namespace)) {
            throw new InvalidClassExtensionNamespaceException(
                'Module should not extend shop edition class: ' . $namespace
            );
        }

        if ($this->shopAdapter->isShopUnifiedNamespace($namespace) && !class_exists($namespace)) {
            throw new InvalidClassExtensionNamespaceException(
                'Module tries to extend non existent shop class: ' . $namespace
            );
        }
    }
}
