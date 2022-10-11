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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Smarty\Plugin;

use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererInterface;
use OxidEsales\TestingLibrary\UnitTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

final class BlockOxIfContentTest extends UnitTestCase
{
    private string $cmsContentId = 'test-smarty-content';
    private string $unparsedCmsContent = '[{1|cat:2|cat:3}]';
    private string $parsedCmsContent = '123';
    private string $templatePath;
    private TemplateRendererInterface $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareTestData();
    }

    protected function tearDown(): void
    {
        $this->clearTestData();
        parent::tearDown();
    }

    public function testRender(): void
    {
        $renderedTemplate = $this->renderer->renderTemplate($this->templatePath);

        $this->assertSame($this->parsedCmsContent, $renderedTemplate);
    }

    public function testRenderWithDeactivatedCmsParsing(): void
    {
        Registry::getConfig()->setConfigParam('deactivateSmartyForCmsContent', true);

        $renderTemplate = $this->renderer->renderTemplate($this->templatePath);

        $this->assertSame($this->unparsedCmsContent, $renderTemplate);
    }

    private function prepareTestData(): void
    {
        $this->addCmsContent();
        $this->addTemplateFile();
        $this->renderer = ContainerFactory::getInstance()->getContainer()->get(TemplateRendererInterface::class);
    }

    private function clearTestData(): void
    {
        $this->removeTemplateFile();
    }

    private function addCmsContent(): void
    {
        $content = oxNew(Content::class);
        $content->oxcontents__oxcontent = new Field($this->unparsedCmsContent);
        $content->setId($this->cmsContentId);
        $content->oxcontents__oxloadid = new Field($this->cmsContentId);
        $content->oxcontents__oxactive = new Field(1);
        $content->save();
    }

    private function addTemplateFile(): void
    {
        $templateContents =
            '[{oxifcontent ident="test-smarty-content" object="content"}][{$content->oxcontents__oxcontent->value}][{/oxifcontent}]';
        $this->templatePath = Path::join(
            $this->getTemplateDir(),
            uniqid('test-tpl-', true)
        );
        file_put_contents($this->templatePath, $templateContents);
    }

    private function getTemplateDir(): string
    {
        $templateDir = Registry::getUtilsView()->getTemplateDirs(false)[0];
        /** @var Filesystem $filesystem */
        $filesystem = ContainerFactory::getInstance()->getContainer()->get('oxid_esales.symfony.file_system');
        if (!$filesystem->exists($templateDir)) {
            $filesystem->mkdir($templateDir);
        }
        return $templateDir;
    }

    private function removeTemplateFile(): void
    {
        if (is_file($this->templatePath)) {
            unlink($this->templatePath);
        }
    }
}
