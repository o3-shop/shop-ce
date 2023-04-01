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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ExtensionNotInChainException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use Traversable;

class ClassExtensionsChain implements \IteratorAggregate
{
    const NAME = 'classExtensions';

    /**
     * @var array
     */
    private $chain = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array
     */
    public function getChain(): array
    {
        return $this->chain;
    }

    /**
     * @param array $chain
     * @return ClassExtensionsChain
     */
    public function setChain(array $chain): ClassExtensionsChain
    {
        $this->chain = $chain;
        return $this;
    }

    /**
     * @param ClassExtension[] $extensions
     *
     * @return void
     */
    public function addExtensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    /**
     * @param ClassExtension $classExtension
     *
     * @throws ExtensionNotInChainException
     */
    public function removeExtension(ClassExtension $classExtension): void
    {
        $extended = $classExtension->getShopClassName();
        $extension = $classExtension->getModuleExtensionClassName();

        if (
            false === array_key_exists($extended, $this->chain) ||
            false === \array_search($extension, $this->chain[$extended], true)
        ) {
            throw new ExtensionNotInChainException(
                'There is no class ' . $extended . ' extended by class ' .
                $extension . ' in the current chain'
            );
        }

        $resultOffset = \array_search($extension, $this->chain[$extended], true);
        unset($this->chain[$extended][$resultOffset]);
        $this->chain[$extended] = \array_values($this->chain[$extended]);

        if (empty($this->chain[$extended])) {
            unset($this->chain[$extended]);
        }
    }

    /**
     * @param ClassExtension $extension
     */
    public function addExtension(ClassExtension $extension): void
    {
        if (array_key_exists($extension->getShopClassName(), $this->chain)) {
            if (!$this->isModuleExtensionClassNameInChain($extension)) {
                array_push(
                    $this->chain[$extension->getShopClassName()],
                    $extension->getModuleExtensionClassName()
                );
            }
        } else {
            $this->chain[$extension->getShopClassName()] = [$extension->getModuleExtensionClassName()];
        }
    }

    /**
     * @param ClassExtension $extension
     *
     * @return bool
     */
    private function isModuleExtensionClassNameInChain(ClassExtension $extension): bool
    {
        if (in_array($extension->getModuleExtensionClassName(), $this->chain[$extension->getShopClassName()])) {
            return true;
        }

        return false;
    }

    /**
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->chain);
    }
}
