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

namespace OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

class FilesystemContainerCache implements ContainerCacheInterface
{
    /**
     * @var BasicContextInterface
     */
    private $context;

    public function __construct(BasicContextInterface $context)
    {
        $this->context = $context;
    }

    public function put(ContainerBuilder $container): void
    {
        $dumper = new PhpDumper($container);
        file_put_contents($this->context->getContainerCacheFilePath(), $dumper->dump());
    }

    public function get(): ContainerInterface
    {
        include_once $this->context->getContainerCacheFilePath();
        return new \ProjectServiceContainer();
    }

    public function exists(): bool
    {
        return file_exists($this->context->getContainerCacheFilePath());
    }

    public function invalidate(): void
    {
        if (file_exists($this->context->getContainerCacheFilePath())) {
            unlink($this->context->getContainerCacheFilePath());
        }
    }
}
