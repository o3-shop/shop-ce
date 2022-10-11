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

namespace OxidEsales\EshopCommunity\Internal\Framework\Templating\Cache;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Class TemplateCacheService
 * @package OxidEsales\EshopCommunity\Internal\Framework\Templating\Cache
 */
class TemplateCacheService implements TemplateCacheServiceInterface
{
    /** @var Filesystem */
    private $filesystem;

    /** @var BasicContextInterface */
    private $basicContext;

    /**
     * TemplateCacheService constructor.
     *
     * @param BasicContextInterface $basicContext
     * @param Filesystem            $filesystem
     */
    public function __construct(BasicContextInterface $basicContext, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->basicContext = $basicContext;
    }

    public function invalidateTemplateCache(): void
    {
        $templateCacheDirectory = $this->getTemplateCacheDirectory();

        if ($this->filesystem->exists($templateCacheDirectory)) {
            $recursiveIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($templateCacheDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            $this->filesystem->remove($recursiveIterator);
        }
    }

    private function getTemplateCacheDirectory(): string
    {
        return Path::join(
            $this->basicContext->getCacheDirectory(),
            'smarty'
        );
    }
}
