<?php

declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Transition\Smarty\Bridge;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\DelegatingTemplateEngine;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;

class SmartyTemplateRendererBridgeTest extends \PHPUnit\Framework\TestCase
{
    public function testSetGetEngine()
    {
        $smarty = new \Smarty();
        $bridge = ContainerFactory::getInstance()->getContainer()->get(TemplateRendererBridgeInterface::class);
        $bridge->setEngine($smarty);

        $this->assertSame($smarty, $bridge->getEngine());
    }

    public function testRendererUsesThePerCallEngineWithSmartyForTheSmartyTheme()
    {
        // wave is a Smarty theme (O3-Theme 2.x is Twig).
        $config = Registry::getConfig();
        $themes = [$config->getConfigParam('sTheme'), $config->getConfigParam('sCustomTheme')];
        $config->setConfigParam('sTheme', 'wave');
        $config->setConfigParam('sCustomTheme', '');

        try {
            $engine = ContainerFactory::getInstance()->getContainer()
                ->get(TemplateRendererBridgeInterface::class)
                ->getTemplateRenderer()
                ->getTemplateEngine();

            $this->assertInstanceOf(DelegatingTemplateEngine::class, $engine);
            $this->assertSame('tpl', $engine->getDefaultFileExtension());
        } finally {
            $config->setConfigParam('sTheme', $themes[0]);
            $config->setConfigParam('sCustomTheme', $themes[1]);
        }
    }
}
