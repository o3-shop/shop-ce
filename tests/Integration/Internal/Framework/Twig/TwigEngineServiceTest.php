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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig;

use OxidEsales\EshopCommunity\Internal\Container\ContainerBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigEngine;
use PHPUnit\Framework\TestCase;

class TwigEngineServiceTest extends TestCase
{
    public function testTwigEngineIsRegisteredUnderItsServiceId(): void
    {
        $engine = ContainerFactory::getInstance()->getContainer()->get('oxid_esales.templating.twig_engine');

        $this->assertInstanceOf(TwigEngine::class, $engine);
        $this->assertSame('html.twig', $engine->getDefaultFileExtension());
    }

    public function testTwigEngineIsNotTheDefaultTemplateEngine(): void
    {
        $uncompiledContainer = (new ContainerBuilderFactory())->create()->getContainer();

        $this->assertNotSame(
            TwigEngine::class,
            $uncompiledContainer->getDefinition(TemplateEngineInterface::class)->getClass()
        );
    }

    public function testEscapersAreRegisteredOnTheEngine(): void
    {
        $engine = ContainerFactory::getInstance()->getContainer()->get('oxid_esales.templating.twig_engine');

        $this->assertSame(
            'user [AT] example [DOT] com',
            $engine->renderFragment("{{ 'user@example.com'|escape('mail') }}", 'ox:mailescaper')
        );
    }
}
