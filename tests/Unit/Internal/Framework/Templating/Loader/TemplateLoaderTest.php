<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Templating\Loader;

use org\bovigo\vfs\vfsStream;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Exception\TemplateFileNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Locator\FileLocatorInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoader;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Resolver\TemplateNameResolverInterface;

class TemplateLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function testExists(): void
    {
        $name = 'test_template.tpl';
        $locator = $this->getFileLocatorMock($name);
        $nameResolver = $this->getTemplateNameResolverMock($name);
        $loader = new TemplateLoader($locator, $nameResolver);

        $this->assertTrue($loader->exists($name));
    }

    public function testIfTemplateDoNotExists(): void
    {
        $name = 'not_existing_template.tpl';
        $locator = $this->getFileLocatorMock('');
        $nameResolver = $this->getTemplateNameResolverMock($name);
        $loader = new TemplateLoader($locator, $nameResolver);

        $this->assertFalse($loader->exists('not_existing_template.tpl'));
    }

    public function testGetContext(): void
    {
        $name = 'testSmartyTemplate.tpl';
        $context = "The new contents of the file";
        $templateDir = vfsStream::setup('testTemplateDir');
        $template = vfsStream::newFile($name)
            ->at($templateDir)
            ->setContent($context)
            ->url();

        $locator = $this->getFileLocatorMock($template);
        $nameResolver = $this->getTemplateNameResolverMock($name);
        $loader = new TemplateLoader($locator, $nameResolver);

        $this->assertSame($context, $loader->getContext($template));
    }

    public function testGetPath(): void
    {
        $name = 'testSmartyTemplate.tpl';
        $context = "The new contents of the file";
        $templateDir = vfsStream::setup('testTemplateDir');
        $template = vfsStream::newFile($name)
            ->at($templateDir)
            ->setContent($context)
            ->url();

        $locator = $this->getFileLocatorMock($template);
        $nameResolver = $this->getTemplateNameResolverMock($name);
        $loader = new TemplateLoader($locator, $nameResolver);

        $this->assertSame($template, $loader->getPath($template));
    }

    public function testGetPathIfTemplateDoNotExits(): void
    {
        $this->expectException(TemplateFileNotFoundException::class);
        $name = 'not_existing_template.tpl';
        $locator = $this->getFileLocatorMock('');
        $nameResolver = $this->getTemplateNameResolverMock($name);
        $loader = new TemplateLoader($locator, $nameResolver);
        $loader->getPath($name);
    }

    /**
     * @param $path
     *
     * @return FileLocatorInterface
     */
    private function getFileLocatorMock($path): FileLocatorInterface
    {
        $locator = $this
            ->getMockBuilder('OxidEsales\EshopCommunity\Internal\Framework\Templating\Locator\FileLocatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $locator->expects($this->any())
            ->method('locate')
            ->will($this->returnValue($path));

        return $locator;
    }

    /**
     * @param $path
     *
     * @return TemplateNameResolverInterface
     */
    private function getTemplateNameResolverMock($name): TemplateNameResolverInterface
    {
        $locator = $this
            ->getMockBuilder('OxidEsales\EshopCommunity\Internal\Framework\Templating\Resolver\TemplateNameResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $locator->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue($name));

        return $locator;
    }
}
