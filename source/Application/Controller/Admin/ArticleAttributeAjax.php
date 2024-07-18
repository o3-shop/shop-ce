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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use oxDb;
use oxField;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class controls article assignment to attributes
 */
class ArticleAttributeAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxattribute', 1, 1, 0],
        ['oxid', 'oxattribute', 0, 0, 1]
    ],
                                 'container2' => [
                                     ['oxtitle', 'oxattribute', 1, 1, 0],
                                     ['oxid', 'oxobject2attribute', 0, 0, 1],
                                     ['oxvalue', 'oxobject2attribute', 0, 1, 1],
                                     ['oxattrid', 'oxobject2attribute', 0, 0, 1],
                                 ]
    ];

    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $sArtId = Registry::getConfig()->getRequestParameter('oxid');
        $sSynchArtId = Registry::getConfig()->getRequestParameter('synchoxid');

        $sAttrViewName = $this->_getViewName('oxattribute');
        $sO2AViewName = $this->_getViewName('oxobject2attribute');
        if ($sArtId) {
            // all categories article is in
            $sQAdd = " from {$sO2AViewName} left join {$sAttrViewName} " .
                     "on {$sAttrViewName}.oxid={$sO2AViewName}.oxattrid " .
                     " where {$sO2AViewName}.oxobjectid = " . $oDb->quote($sArtId) . " ";
        } else {
            $sQAdd = " from {$sAttrViewName} where {$sAttrViewName}.oxid not in ( select {$sO2AViewName}.oxattrid " .
                     "from {$sO2AViewName} left join {$sAttrViewName} " .
                     "on {$sAttrViewName}.oxid={$sO2AViewName}.oxattrid " .
                     " where {$sO2AViewName}.oxobjectid = " . $oDb->quote($sSynchArtId) . " ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes article attributes.
     */
    public function removeAttr()
    {
        $aChosenArt = $this->_getActionIds('oxobject2attribute.oxid');
        $sOxid = Registry::getConfig()->getRequestParameter('oxid');
        if (Registry::getConfig()->getRequestParameter('all')) {
            $sO2AViewName = $this->_getViewName('oxobject2attribute');
            $sQ = $this->_addFilter("delete $sO2AViewName.* " . $this->_getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sChosenArticles = implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenArt));
            $sQ = "delete from oxobject2attribute where oxobject2attribute.oxid in ({$sChosenArticles}) ";
            DatabaseProvider::getDb()->Execute($sQ);
        }

        $this->onArticleAttributeRelationChange($sOxid);
    }

    /**
     * Adds attributes to article.
     */
    public function addAttr()
    {
        $aAddCat = $this->_getActionIds('oxattribute.oxid');
        $soxId = Registry::getConfig()->getRequestParameter('synchoxid');

        if (Registry::getConfig()->getRequestParameter('all')) {
            $sAttrViewName = $this->_getViewName('oxattribute');
            $aAddCat = $this->_getAll($this->_addFilter("select $sAttrViewName.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aAddCat)) {
            foreach ($aAddCat as $sAdd) {
                $oNew = oxNew(BaseModel::class);
                $oNew->init("oxobject2attribute");
                $oNew->oxobject2attribute__oxobjectid = new Field($soxId);
                $oNew->oxobject2attribute__oxattrid = new Field($sAdd);
                $oNew->save();
            }

            $this->onArticleAttributeRelationChange($soxId);
        }
    }

    /**
     * Saves attribute value
     *
     * @return null
     */
    public function saveAttributeValue()
    {
        $database = DatabaseProvider::getDb();
        $this->resetContentCache();

        $articleId = Registry::getConfig()->getRequestParameter("oxid");
        $attributeId = Registry::getConfig()->getRequestParameter("attr_oxid");
        $attributeValue = Registry::getConfig()->getRequestParameter("attr_value");

        $article = oxNew(Article::class);
        if ($article->load($articleId)) {
            if ($article->isDerived()) {
                return;
            }

            $this->onAttributeValueChange($article);

            if (isset($attributeId) && ("" != $attributeId)) {
                $viewName = $this->_getViewName("oxobject2attribute");
                $quotedArticleId = $database->quote($article->oxarticles__oxid->value);
                $select = "select * from {$viewName} where {$viewName}.oxobjectid= {$quotedArticleId} and
                            {$viewName}.oxattrid= " . $database->quote($attributeId);
                $objectToAttribute = oxNew(\OxidEsales\Eshop\Core\Model\MultiLanguageModel::class);
                $objectToAttribute->setLanguage(Registry::getConfig()->getRequestParameter('editlanguage'));
                $objectToAttribute->init("oxobject2attribute");
                if ($objectToAttribute->assignRecord($select)) {
                    $objectToAttribute->oxobject2attribute__oxvalue->setValue($attributeValue);
                    $objectToAttribute->save();
                }
            }
        }
    }

    /**
     * Method is used to bind to attribute and article relation change action.
     *
     * @param string $articleId
     */
    protected function onArticleAttributeRelationChange($articleId)
    {
    }

    /**
     * Method is used to bind to attribute value change.
     *
     * @param Article $article
     */
    protected function onAttributeValueChange($article)
    {
    }
}
