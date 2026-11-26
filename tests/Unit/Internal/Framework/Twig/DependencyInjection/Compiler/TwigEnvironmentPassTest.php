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

/*
 * Copyright (c) 2004-2018 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\DependencyInjection\Compiler;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\DependencyInjection\Compiler\TwigEnvironmentPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Twig\Extension\DebugExtension;

/**
 * Class TwigEnvironmentPassTest
 */
class TwigEnvironmentPassTest extends TestCase
{
    public function testTwigBridgeExtensionsAreRegisteredFirst(): void
    {
        $container = new ContainerBuilder();
        $twigDefinition = $container->register('twig');
        $container->register('other_extension', 'Foo\Bar')
            ->addTag('twig.extension');
        $container->register('twig_bridge_extension', DebugExtension::class)
            ->addTag('twig.extension');

        $twigEnvironmentPass = new TwigEnvironmentPass();
        $twigEnvironmentPass->process($container);

        $methodCalls = $twigDefinition->getMethodCalls();
        $this->assertCount(2, $methodCalls);

        $otherExtensionReference = $methodCalls[0][1][0];
        $this->assertInstanceOf(Reference::class, $otherExtensionReference);
        $this->assertSame('other_extension', (string) $otherExtensionReference);

        $twigBridgeExtensionReference = $methodCalls[1][1][0];
        $this->assertInstanceOf(Reference::class, $twigBridgeExtensionReference);
        $this->assertSame('twig_bridge_extension', (string) $twigBridgeExtensionReference);
    }
}
