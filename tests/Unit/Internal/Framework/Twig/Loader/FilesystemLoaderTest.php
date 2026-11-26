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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Loader;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoaderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Loader\FilesystemLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Error\LoaderError;

/**
 * Class FilesystemLoaderTest
 */
class FilesystemLoaderTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $loader = new FilesystemLoader();
        $this->assertEquals([], $loader->getPaths());
    }

    public function testFindTemplateLoadError()
    {
        /** @var TemplateLoaderInterface|MockObject $internalLoader */
        $internalLoader = $this->createMock(TemplateLoaderInterface::class);
        $internalLoader->method('getPath')->willReturn('');

        $loader = new FilesystemLoader([], null, $internalLoader, $internalLoader);

        $this->expectException(LoaderError::class);
        $loader->getSourceContext('foo')->getCode();
    }

    public function testFindTemplateByParentClass()
    {
        $basePath = __DIR__ . '/Fixtures';

        $loader = new FilesystemLoader([$basePath]);

        $this->assertEquals('index file', $loader->getSourceContext('index.html.twig')->getCode());
    }

    public function testFindTemplateByInternalLoader()
    {
        /** @var TemplateLoaderInterface|MockObject $internalLoader */
        $internalLoader = $this->createMock(TemplateLoaderInterface::class);
        $internalLoader->method('getPath')->willReturnArgument(0);

        $loader = new FilesystemLoader([], null, $internalLoader, $internalLoader);

        $templateName = 'internal_index.html.twig';
        $this->assertEquals($templateName, $loader->findTemplate($templateName));
    }
}
