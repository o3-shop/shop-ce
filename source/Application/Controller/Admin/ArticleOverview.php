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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article overview manager.
 * Collects and previews such article information as article creation date,
 * last modification date, sales rating etc.
 * Admin Menu: Manage Products -> Articles -> Overview.
 */
class ArticleOverview extends AdminDetailsController
{
    /**
     * Loads article overview data, passes to Smarty engine and returns name
     * of template file "article_overview.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        parent::render();

        $this->_aViewData['edit'] = $oArticle = oxNew(Article::class);

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != '-1') {
            $oDB = $this->getDatabase();

            // load object
            $this->updateArticle($oArticle, $soxId);

            $sShopID = $myConfig->getShopID();

            $sSelect = $this->formOrderAmountQuery($soxId);
            $this->_aViewData['totalordercnt'] = $iTotalOrderCnt = (float) $oDB->getOne($sSelect);

            $sSelect = $this->formSoldOutAmountQuery($soxId);
            $this->_aViewData['soldcnt'] = $iSoldCnt = (float) $oDB->getOne($sSelect);

            $sSelect = $this->formCanceledAmountQuery($soxId);
            $this->_aViewData['canceledcnt'] = $iCanceledCnt = (float) $oDB->getOne($sSelect);

            // not yet processed
            $this->_aViewData['leftordercnt'] = $iTotalOrderCnt - $iSoldCnt - $iCanceledCnt;

            // position in top ten
            $sSelect = 'select oxartid,sum(oxamount) as cnt from oxorderarticles ' .
                       'where oxordershopid = :oxordershopid group by oxartid order by cnt desc';

            $rs = $oDB->select($sSelect, [
                ':oxordershopid' => $sShopID,
            ]);
            $iTopPos = 0;
            $iPos = 0;
            if ($rs && $rs->count() > 0) {
                while (!$rs->EOF) {
                    $iPos++;
                    if ($rs->fields[0] == $soxId) {
                        $iTopPos = $iPos;
                    }
                    $rs->fetchRow();
                }
            }

            $this->_aViewData['postopten'] = $iTopPos;
            $this->_aViewData['toptentotal'] = $iPos;
        }

        $this->_aViewData['afolder'] = $myConfig->getConfigParam('aProductfolder');
        $this->_aViewData['aSubclass'] = $myConfig->getConfigParam('aArticleClasses');

        return 'article_overview.tpl';
    }

    /**
     * @return DatabaseInterface
     * @throws DatabaseConnectionException
     */
    protected function getDatabase()
    {
        return DatabaseProvider::getDb();
    }

    /**
     * Forms query to get total order count.
     *
     * @param string $oxId
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function formOrderAmountQuery($oxId)
    {
        $query = 'select sum(oxamount) from oxorderarticles ';
        $query .= 'where oxartid=' . $this->getDatabase()->quote($oxId);

        return $query;
    }

    /**
     * Forms query to get sold out amount count.
     *
     * @param string $oxId
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function formSoldOutAmountQuery($oxId)
    {
        return 'select sum(oxorderarticles.oxamount) from  oxorderarticles, oxorder ' .
            "where (oxorder.oxpaid>0 or oxorder.oxsenddate > 0) and oxorderarticles.oxstorno != '1' " .
            'and oxorderarticles.oxartid=' . $this->getDatabase()->quote($oxId) .
            'and oxorder.oxid =oxorderarticles.oxorderid';
    }

    /**
     * Forms query to get canceled amount count.
     *
     * @param string $soxId
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function formCanceledAmountQuery($soxId)
    {
        return "select sum(oxamount) from oxorderarticles where oxstorno = '1' " .
            'and oxartid=' . $this->getDatabase()->quote($soxId);
    }

    /**
     * Loads language for article object.
     *
     * @param Article $article
     * @param string                                      $oxId
     *
     * @return Article
     */
    protected function updateArticle($article, $oxId)
    {
        $article->loadInLang(Registry::getRequest()->getRequestEscapedParameter('editlanguage'), $oxId);

        return $article;
    }
}
