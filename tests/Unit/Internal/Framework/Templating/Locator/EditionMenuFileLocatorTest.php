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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Templating\Locator;

use org\bovigo\vfs\vfsStream;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Locator\EditionMenuFileLocator;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\AdminThemeBridgeInterface;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\BasicContextStub;
use Symfony\Component\Filesystem\Filesystem;

class EditionMenuFileLocatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var vfsStream */
    private $vfsStreamDirectory;

    /**
     * @dataProvider dataProviderTestLocate
     */
    public function testLocate($edition)
    {
        $this->createModuleStructure($edition);
        $locator = new EditionMenuFileLocator(
            $this->getAdminThemeMock(),
            $this->getContext($edition),
            new Filesystem()
        );

        $expectedPath = $this->vfsStreamDirectory->url() .
            '/testSourcePath' .
            $edition .
            '/Application/views/admin/menu.xml';
        $this->assertSame([$expectedPath], $locator->locate());
    }

    public function dataProviderTestLocate(): array
    {
        return [
            ['CE'],
            ['PE'],
            ['EE'],
        ];
    }

    /**
     * @return AdminThemeBridgeInterface
     */
    private function getAdminThemeMock()
    {
        $adminTheme = $this->getMockBuilder(AdminThemeBridgeInterface::class)->getMock();
        $adminTheme->method('getActiveTheme')->willReturn('admin');

        return $adminTheme;
    }

    /**
     * @param string $edition
     *
     * @return BasicContextStub
     */
    private function getContext(string $edition)
    {
        $context = new BasicContextStub();
        $context->setEdition($edition);
        $context->setSourcePath($this->vfsStreamDirectory->url() . '/testSourcePathCE');

        return $context;
    }

    private function createModuleStructure($edition)
    {
        $shopPath = 'testSourcePath' . $edition;
        $structure = [
            $shopPath => [
                'Application' => [
                    'views' => [
                        'admin' => [
                            'menu.xml' => '*this is menu xml for test*'
                        ]
                    ]
                ]
            ]
        ];
        $this->vfsStreamDirectory = vfsStream::setup('root', null, $structure);
    }
}
