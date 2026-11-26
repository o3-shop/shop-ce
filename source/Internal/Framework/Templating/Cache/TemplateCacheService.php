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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Class TemplateCacheService
 * @package OxidEsales\EshopCommunity\Internal\Framework\Templating\Cache
 */
class TemplateCacheService implements TemplateCacheServiceInterface
{
    /** Cache directory names below the compile directory, one per template engine. */
    private const TEMPLATE_ENGINE_CACHE_DIRECTORIES = ['smarty', 'twig'];

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
        foreach (self::TEMPLATE_ENGINE_CACHE_DIRECTORIES as $directoryName) {
            $this->emptyDirectory(Path::join($this->basicContext->getCacheDirectory(), $directoryName));
        }
    }

    private function emptyDirectory(string $directory): void
    {
        if ($this->filesystem->exists($directory)) {
            $recursiveIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            $this->filesystem->remove($recursiveIterator);
        }
    }
}
