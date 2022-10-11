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

/**
 * Testing oxArticle class.
 */
class Unit_Core_oxArticleTest extends OxidTestCase
{
    public function testHasSortingFieldsChangedWhenNoFieldsWereChanged()
    {
        $oArticle = oxNew('oxArticle');
        $oArticle->setId('_testArticleId');
        $oArticle->oxarticles__oxprice = new oxField(10, oxField::T_RAW);
        $oArticle->oxarticles__oxtitle = new oxField("title", oxField::T_RAW);
        $oArticle->save();

        $oArticle = oxNew('oxArticle');
        $oArticle->load('_testArticleId');
        $this->assertFalse($oArticle->hasSortingFieldsChanged());
    }

    public function testHasSortingFieldsChangedWhenSortingFieldsWereChanged()
    {
        $this->getConfig()->setConfigParam('aSortCols', array('oxtitle', 'oxprice'));

        $oArticle = oxNew('oxArticle');
        $oArticle->setId('_testArticleId');
        $oArticle->oxarticles__oxprice = new oxField(10, oxField::T_RAW);
        $oArticle->oxarticles__oxtitle = new oxField("title", oxField::T_RAW);
        $oArticle->save();

        $oArticle = oxNew('oxArticle');
        $oArticle->load('_testArticleId');
        $oArticle->oxarticles__oxtitle = new oxField("NewTitle", oxField::T_RAW);
        $this->assertTrue($oArticle->hasSortingFieldsChanged());
    }

    public function testHasSortingFieldsChangedWhenSortingFieldValueSetToTheSameOne()
    {
        $this->getConfig()->setConfigParam('aSortCols', array('oxtitle', 'oxprice'));

        $oArticle = oxNew('oxArticle');
        $oArticle->setId('_testArticleId');
        $oArticle->oxarticles__oxprice = new oxField(10);
        $oArticle->oxarticles__oxtitle = new oxField("title");
        $oArticle->save();

        $oArticle = oxNew('oxArticle');
        $oArticle->load('_testArticleId');
        $oArticle->oxarticles__oxtitle = new oxField("title");
        $this->assertFalse($oArticle->hasSortingFieldsChanged());
    }

    public function testHasSortingFieldsChangedWhenNonSortingFieldChanged()
    {
        $this->getConfig()->setConfigParam('aSortCols', array('oxprice'));

        $oArticle = oxNew('oxArticle');
        $oArticle->setId('_testArticleId');
        $oArticle->oxarticles__oxprice = new oxField(10);
        $oArticle->oxarticles__oxtitle = new oxField("title");
        $oArticle->save();

        $oArticle = oxNew('oxArticle');
        $oArticle->load('_testArticleId');
        $oArticle->oxarticles__oxtitle = new oxField("changed title");
        $this->assertFalse($oArticle->hasSortingFieldsChanged());
    }

    public function testHasSortingFieldsChangedWhenNoSortingFieldsSet()
    {
        $this->getConfig()->setConfigParam('aSortCols', '');

        $oArticle = oxNew('oxArticle');
        $oArticle->setId('_testArticleId');
        $oArticle->oxarticles__oxprice = new oxField(10, oxField::T_RAW);
        $oArticle->oxarticles__oxtitle = new oxField("title", oxField::T_RAW);
        $oArticle->save();

        $oArticle = oxNew('oxArticle');
        $oArticle->load('_testArticleId');
        $oArticle->oxarticles__oxprice = new oxField(100, oxField::T_RAW);
        $this->assertFalse($oArticle->hasSortingFieldsChanged());
    }
}
