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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Controller;

use OxidEsales\Eshop\Application\Controller\ContentController;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;

final class ContentControllerTest extends UnitTestCase
{
    private string $smartyTagsContent = '[{1|cat:2|cat:3}]';
    private string $smartyParsedContent = '123';
    private string $testSmartyContentId = 'test-smarty-content';
    private ContentController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareTestData();
    }

    public function testGetParsedContent(): void
    {
        $parsedContent = $this->controller->getParsedContent();

        $this->assertSame($this->smartyParsedContent, $parsedContent);
    }

    public function testGetParsedContentWithConfigurationOff(): void
    {
        Registry::getConfig()->setConfigParam('deactivateSmartyForCmsContent', true);

        $parsedContent = $this->controller->getParsedContent();

        $this->assertSame($this->smartyTagsContent, $parsedContent);
    }

    private function prepareTestData(): void
    {
        $content = oxNew(Content::class);
        $content->oxcontents__oxcontent = new Field($this->smartyTagsContent);
        $content->setId($this->testSmartyContentId);
        $content->oxcontents__oxloadid = new Field($this->testSmartyContentId);
        $content->oxcontents__oxactive = new Field(1);
        $content->save();

        $_GET['oxloadid'] = $this->testSmartyContentId;
        $this->controller = oxNew(ContentController::class);
    }
}
