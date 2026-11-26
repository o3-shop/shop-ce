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

use org\bovigo\vfs\vfsStream;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Escaper\MailEscaper;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigEngine;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigEngineConfigurationInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigEngineTest extends \PHPUnit\Framework\TestCase
{
    private $templateDir;
    private $templateDirPath;
    private $template;

    protected function setUp(): void
    {
        parent::setUp();
        $templateDir = vfsStream::setup($this->getTemplateDir());
        $this->template = vfsStream::newFile($this->getTemplateName())->at($templateDir)->setContent("{{ 'twig' }}")->url();
        $this->templateDir = $templateDir;
        $this->templateDirPath = vfsStream::url($this->getTemplateDir());
    }

    public function testExists()
    {
        $engine = $this->getEngine();
        $this->assertTrue($engine->exists($this->getTemplateName()));
        $this->assertFalse($engine->exists('foo'));
    }

    public function testAddGlobal()
    {
        $engine = $this->getEngine();
        $engine->addGlobal('foo', 'bar');
        $this->assertEquals(['foo' => 'bar'], $engine->getGlobals());
        $this->assertNotEquals(['not_foo' => 'not_bar'], $engine->getGlobals());
    }

    public function testRender()
    {
        $engine = $this->getEngine();
        $this->assertTrue(file_exists($this->template));
        $rendered = $engine->render($this->getTemplateName());
        $this->assertEquals('twig', $rendered);
        $this->assertNotEquals('foo', $rendered);
    }

    public function testDefaultFileExtension()
    {
        $this->assertSame('html.twig', $this->getEngine()->getDefaultFileExtension());
    }

    public function testRegisteredEscaperIsUsedByTemplates()
    {
        $engine = $this->getEngine();
        $engine->addEscaper(new MailEscaper());

        $this->assertSame(
            'user [AT] example [DOT] com',
            $engine->renderFragment("{{ 'user@example.com'|escape('mail') }}", 'ox:mailescaper')
        );
    }

    public function testRenderFragment()
    {
        $engine = $this->getEngine();
        $rendered = $engine->renderFragment("{{ 'twig' }}", 'ox:testid');
        $this->assertEquals('twig', $rendered);
    }

    private function getEngine($engine_type = 'twig')
    {
        /** @var TwigEngineConfigurationInterface $configuration */
        $configuration = $this->getMockBuilder('OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigEngineConfigurationInterface')->getMock();
        $configuration->method('getParameters')->willReturn(['template_dir' => [$this->templateDirPath], 'is_debug' => 'false', 'cache_dir' => 'foo']);

        $loader = new FilesystemLoader($this->templateDirPath);

        $engine = new Environment($loader);

        return new TwigEngine($engine);
    }

    private function getTemplateName()
    {
        return 'testTwigTemplate.twig';
    }

    private function getTemplateDir()
    {
        return 'testTemplateDir';
    }
}
