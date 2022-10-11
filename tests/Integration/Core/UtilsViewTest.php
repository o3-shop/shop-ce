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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use OxidEsales\Eshop\Application\Model\Actions;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\News;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;

final class UtilsViewTest extends UnitTestCase
{
    /** @var string */
    private $smartyUnparsedContent = '[{1|cat:2|cat:3}]';
    /** @var string  */
    private $smartyParsedContent = '123';

    public function testDisableSmartyForCmsContentWithProduct(): void
    {
        $model = oxNew(Article::class);
        $model->setArticleLongDesc($this->smartyUnparsedContent);

        $this->assertSame($this->smartyParsedContent, $model->getLongDesc());
        Registry::getConfig()->setConfigParam('deactivateSmartyForCmsContent', true);
        $this->assertSame($this->smartyUnparsedContent, $model->getLongDesc());
    }

    public function testDisableSmartyForCmsContentWithCategory(): void
    {
        $model = oxNew(Category::class);
        $model->oxcategories__oxlongdesc = new Field($this->smartyUnparsedContent);

        $this->assertSame($this->smartyParsedContent, $model->getLongDesc());
        Registry::getConfig()->setConfigParam('deactivateSmartyForCmsContent', true);
        $this->assertSame($this->smartyUnparsedContent, $model->getLongDesc());
    }

    public function testDisableSmartyForCmsContentWithAction(): void
    {
        $model = oxNew(Actions::class);
        $model->oxactions__oxlongdesc = new Field($this->smartyUnparsedContent);

        $this->assertSame($this->smartyParsedContent, $model->getLongDesc());
        Registry::getConfig()->setConfigParam('deactivateSmartyForCmsContent', true);
        $this->assertSame($this->smartyUnparsedContent, $model->getLongDesc());
    }

    public function testDisableSmartyForCmsContentWithNews(): void
    {
        $model = oxNew(News::class);
        $model->oxnews__oxlongdesc = new Field($this->smartyUnparsedContent);

        $this->assertSame($this->smartyParsedContent, $model->getLongDesc());
        Registry::getConfig()->setConfigParam('deactivateSmartyForCmsContent', true);
        $this->assertSame($this->smartyUnparsedContent, $model->getLongDesc());
    }
}
