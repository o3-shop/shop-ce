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

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * @deprecated Functionality for Newsletter management will be removed.
 * Newsletter manager.
 * Performs creation of newsletter text, assign newsletter to user groups,
 * deletes etc.
 *
 */
class Newsletter extends BaseModel
{
    /**
     * Newsletter HTML format text (default null).
     *
     * @var string
     */
    protected $_sHtmlText = null;

    /**
     * Newsletter plaintext format text (default null).
     *
     * @var string
     */
    protected $_sPlainText = null;

    /**
     * User groups object (default null).
     *
     * @var object
     */
    protected $_oGroups = null;

    /**
     * User session object (default null).
     *
     * @var User
     */
    protected $_oUser = null;

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxnewsletter';

    /**
     * Class constructor, initiates Smarty engine object, parent constructor
     * (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxnewsletter');
    }

    /**
     * Deletes object information from DB, returns true on success.
     *
     * @param null $sOxId object ID (default null)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function delete($sOxId = null)
    {
        if (!$sOxId) {
            $sOxId = $this->getId();
        }
        if (!$sOxId) {
            return false;
        }

        $blDeleted = parent::delete($sOxId);

        if ($blDeleted) {
            $oDb = DatabaseProvider::getDb();
            $sDelete = "delete from oxobject2group where oxobject2group.oxshopid = :oxshopid and oxobject2group.oxobjectid = :oxobjectid";
            $oDb->execute($sDelete, [
                ':oxshopid' => $this->getShopId(),
                ':oxobjectid' => $sOxId
            ]);
        }

        return $blDeleted;
    }

    /**
     * Returns assigned user groups list object
     *
     * @return object $_oGroups
     */
    public function getGroups()
    {
        if (isset($this->_oGroups)) {
            return $this->_oGroups;
        }

        // user-groups
        $this->_oGroups = oxNew(ListModel::class, "oxgroups");
        $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName("oxgroups");

        // performance
        $sSelect = "select {$sViewName}.* from {$sViewName}, oxobject2group
                where oxobject2group.oxobjectid = :oxobjectid
                and oxobject2group.oxgroupsid={$sViewName}.oxid ";
        $this->_oGroups->selectString($sSelect, [
            ':oxobjectid' => $this->getId()
        ]);

        return $this->_oGroups;
    }

    /**
     * Returns assigned HTML text
     *
     * @return string
     */
    public function getHtmlText()
    {
        return $this->_sHtmlText;
    }

    /**
     * Returns assigned plain text
     *
     * @return string
     */
    public function getPlainText()
    {
        return $this->_sPlainText;
    }

    /**
     * Creates oxshop object and sets base parameters (such as currency and
     * language).
     *
     * @param string|User $sUserid User ID or OBJECT
     * @param bool $blPerfLoadAktion perform option load actions
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function prepare($sUserid, $blPerfLoadAktion = false)
    {
        // switching off admin
        $blAdmin = $this->isAdmin();
        $this->setAdminMode(false);

        // add currency
        $this->_setUser($sUserid);
        $this->_setParams($blPerfLoadAktion);

        // restoring mode ..
        $this->setAdminMode($blAdmin);
    }

    /**
     * Creates oxemail object, calls mail sending function (oxEMail::sendNewsletterMail()
     * (#2542 added subject field)),
     * returns true on success.
     *
     * @return bool
     */
    public function send()
    {
        $oxEMail = oxNew(Email::class);
        $blSend = $oxEMail->sendNewsletterMail($this, $this->_oUser, $this->oxnewsletter__oxsubject->value);

        return $blSend;
    }

    /**
     * Assigns to Smarty oxuser object, add newsletter products,
     * adds products which fit to the last order of
     * this user, generates HTML and plaintext format newsletters.
     *
     * @param bool $blPerfLoadAktion perform option load actions
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "setParams" in next major
     */
    protected function _setParams($blPerfLoadAktion = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();

        $oShop = oxNew(Shop::class);
        $oShop->load($myConfig->getShopId());

        $oView = oxNew(FrontendController::class);
        $oShop = $oView->addGlobalParams($oShop);

        $oView->addTplParam('myshop', $oShop);
        $oView->addTplParam('shop', $oShop);
        $oView->addTplParam('oViewConf', $oShop);
        $oView->addTplParam('oView', $oView);
        $oView->addTplParam('mycurrency', $myConfig->getActShopCurrencyObject());
        $oView->addTplParam('myuser', $this->_oUser);

        $this->_assignProducts($oView, $blPerfLoadAktion);

        $aInput[] = [$this->getId() . 'html', $this->oxnewsletter__oxtemplate->value];
        $aInput[] = [$this->getId() . 'plain', $this->oxnewsletter__oxplaintemplate->value];
        $aRes = Registry::getUtilsView()->parseThroughSmarty($aInput, null, $oView, true);

        $this->_sHtmlText = $aRes[0];
        $this->_sPlainText = $aRes[1];
    }

    /**
     * Creates oxuser object (user ID passed to method),
     *
     * @param string|User $sUserid User ID or OBJECT
     * @deprecated underscore prefix violates PSR12, will be renamed to "setUser" in next major
     */
    protected function _setUser($sUserid) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_string($sUserid)) {
            $oUser = oxNew(User::class);
            if ($oUser->load($sUserid)) {
                $this->_oUser = $oUser;
            }
        } else {
            $this->_oUser = $sUserid; // we expect a full and valid user object
        }
    }

    /**
     * Add newsletter products (#559 only if we have user we can assign this info),
     * adds products which fit to the last order of assigned user.
     *
     * @param BaseController $oView view object to store view data
     * @param bool $blPerfLoadAktion perform option load actions
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignProducts" in next major
     */
    protected function _assignProducts($oView, $blPerfLoadAktion = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($blPerfLoadAktion) {
            $oArtList = oxNew(ArticleList::class);
            $oArtList->loadActionArticles('OXNEWSLETTER');
            $oView->addTplParam('articlelist', $oArtList);
        }

        if ($this->_oUser->getId()) {
            $oArticle = oxNew(Article::class);
            $sArticleTable = $oArticle->getViewName();

            // add products which fit to the last order of this user
            $sSelect = "select $sArticleTable.* from oxorder left join oxorderarticles on oxorderarticles.oxorderid = oxorder.oxid";
            $sSelect .= " left join $sArticleTable on oxorderarticles.oxartid = $sArticleTable.oxid";
            $sSelect .= " where " . $oArticle->getSqlActiveSnippet();
            $sSelect .= " and oxorder.oxuserid = '" . $this->_oUser->getId() . "' order by oxorder.oxorderdate desc limit 1";

            if ($oArticle->assignRecord($sSelect)) {
                $oSimList = $oArticle->getSimilarProducts();
                if ($oSimList && $oSimList->count()) {
                    $oView->addTplParam('simlist', $oSimList);
                    $iCnt = 0;
                    foreach ($oSimList as $oArt) {
                        $oView->addTplParam("simarticle$iCnt", $oArt);
                        $iCnt++;
                    }
                }
            }
        }
    }

    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (e.g. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFieldData" in next major
     */
    protected function _setFieldData($sFieldName, $sValue, $iDataType = Field::T_TEXT) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ('oxtemplate' === $sFieldName || 'oxplaintemplate' === $sFieldName) {
            $iDataType = Field::T_RAW;
        }

        return parent::_setFieldData($sFieldName, $sValue, $iDataType);
    }
}
