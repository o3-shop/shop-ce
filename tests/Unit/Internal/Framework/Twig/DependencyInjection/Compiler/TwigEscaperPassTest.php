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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\DependencyInjection\Compiler;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\DependencyInjection\Compiler\TwigEscaperPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TwigEscaperPassTest
 */
class TwigEscaperPassTest extends TestCase
{
    public function testEscaperPass()
    {
        $builder = new ContainerBuilder();
        $builder->register('twig');
        $builder->register('oxid_esales.templating.twig_engine');
        $pass = new TwigEscaperPass();

        $builder->register('test_escaper_1')
            ->addTag('twig.escaper');

        $builder->register('test_escaper_2')
            ->addTag('twig.escaper');

        $pass->process($builder);

        $calls = $builder->getDefinition('oxid_esales.templating.twig_engine')->getMethodCalls();
        $this->assertCount(2, $calls);
        $this->assertSame('addEscaper', $calls[0][0]);

        $this->assertArrayHasKey('test_escaper_1', $builder->findTaggedServiceIds('twig.escaper'));
        $this->assertArrayHasKey('test_escaper_2', $builder->findTaggedServiceIds('twig.escaper'));
    }
}
