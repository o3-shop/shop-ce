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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain;
use PHPUnit\Framework\TestCase;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;

/**
 * @internal
 */
class ClassExtensionsChainTest extends TestCase
{
    public function testAddExtensionsIfChainIsEmpty()
    {
        $chain = new ClassExtensionsChain();

        $chain->addExtensions(
            [
                new ClassExtension(
                    'extendedClass',
                    'firstExtension'
                ),
                new ClassExtension(
                    'anotherExtendedClass',
                    'someExtension'
                )
            ]
        );

        $this->assertEquals(
            [
                'extendedClass' => [
                    'firstExtension',
                ],
                'anotherExtendedClass' => [
                    'someExtension',
                ],
            ],
            $chain->getChain()
        );
    }

    public function testAddExtensionToChainIfAnotherExtensionsAlreadyExist()
    {
        $chain = new ClassExtensionsChain();

        $chain->addExtensions(
            [
                new ClassExtension(
                    'extendedClass',
                    'firstExtension'
                ),
                new ClassExtension(
                    'anotherExtendedClass',
                    'someExtension'
                ),
                new ClassExtension(
                    'extendedClass',
                    'secondExtension'
                ),
                new ClassExtension(
                    'extendedClass',
                    'firstExtension'
                )
            ]
        );

        $chain->addExtension(
            new ClassExtension(
                'extendedClass',
                'firstExtension'
            )
        );

        $this->assertEquals(
            [
                'extendedClass' => [
                    'firstExtension',
                    'secondExtension',
                ],
                'anotherExtendedClass' => [
                    'someExtension',
                ]
            ],
            $chain->getChain()
        );
    }

    public function testRemoveExtension()
    {
        $chain = new ClassExtensionsChain();
        $chain->setChain(
            [
                'extendedClass1' => [
                    'extension1',
                    'extension2',
                ],
                'extendedClass2' => [
                    'extension3',
                ],
                'extendedClass3' => [
                    'extension4'
                ]
            ]
        );
        $chain->removeExtension(
            new ClassExtension(
                'extendedClass1',
                'extension1'
            )
        );
        $chain->removeExtension(
            new ClassExtension(
                'extendedClass2',
                'extension3'
            )
        );

        $this->assertEquals(
            [
                'extendedClass1' => [
                    'extension2',
                ],
                'extendedClass3' => [
                    'extension4'
                ]
            ],
            $chain->getChain()
        );
    }

    /**
     * @dataProvider invalidExtensionProvider
     *
     * @param ClassExtension $extension
     *
     */
    public function testRemoveExtensionThrowsExceptionIfClassNotExistsInChain(ClassExtension $extension)
    {
        $chain = new ClassExtensionsChain();
        $chain->setChain(
            [
                'extendedClass1' => [
                    'extension1',
                    'extension2',
                ]
            ]
        );
        $this->expectException(
            \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ExtensionNotInChainException::class
        );
        $chain->removeExtension($extension);
    }

    public function invalidExtensionProvider()
    {
        return [
            [
                new ClassExtension(
                    'notExistingExtended',
                    'notExistingExtension'
                )
            ],
            [
                new ClassExtension(
                    'extendedClass1',
                    'notExistingExtension'
                )
            ],
        ];
    }
}
