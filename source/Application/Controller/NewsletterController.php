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

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\MailValidator;
use OxidEsales\Eshop\Core\Registry;

/**
 * Newsletter opt-in/out.
 * Arranges newsletter opt-in form, have some methods to confirm
 * user opt-in or remove user from newsletter list. O3-Shop ->
 * (Newsletter).
 */
class NewsletterController extends FrontendController
{
    /**
     * Action articlelist
     *
     * @var object
     */
    protected $_oActionArticles = null;

    /**
     * Top start article
     *
     * @var object
     */
    protected $_oTopArticle = null;

    /**
     * Home country id
     *
     * @var string
     */
    protected $_sHomeCountryId = null;

    /**
     * Newsletter status.
     *
     * @var integer
     */
    protected $_iNewsletterStatus = null;

    /**
     * User newsletter registration data.
     *
     * @var array
     */
    protected $_aRegParams = null;

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/info/newsletter.tpl';

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * Only loads newsletter subscriber data.
     *
     * Template variables:
     * <b>aRegParams</b>
     */
    public function fill()
    {
        // loads submited values
        $this->_aRegParams = Registry::getRequest()->getRequestEscapedParameter('editval');
    }

    /**
     * Checks for newsletter subscriber data, if OK - creates new user as
     * subscriber or assigns existing user to newsletter group and sends
     * confirmation email.
     *
     * Template variables:
     * <b>success</b>, <b>error</b>, <b>aRegParams</b>
     *
     * @return void
     */
    public function send()
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        // loads submited values
        $this->_aRegParams = $aParams;

        if (!$aParams['oxuser__oxusername']) {
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_COMPLETE_FIELDS_CORRECTLY');

            return;
        } elseif (!oxNew(MailValidator::class)->isValidEmail($aParams['oxuser__oxusername'])) {
            // #1052C - eMail validation added
            Registry::getUtilsView()->addErrorToDisplay('MESSAGE_INVALID_EMAIL');

            return;
        }

        $blSubscribe = Registry::getRequest()->getRequestEscapedParameter('subscribeStatus');

        $oUser = oxNew(User::class);
        $oUser->oxuser__oxusername = new Field($aParams['oxuser__oxusername'], Field::T_RAW);

        // if such user does not exist
        if (!$oUser->exists()) {
            // and subscribe is off - error, on - create
            if (!$blSubscribe) {
                Registry::getUtilsView()->addErrorToDisplay('NEWSLETTER_EMAIL_NOT_EXIST');

                return;
            } else {
                $oUser->oxuser__oxactive = new Field(1, Field::T_RAW);
                $oUser->oxuser__oxrights = new Field('user', Field::T_RAW);
                $oUser->oxuser__oxshopid = new Field(Registry::getConfig()->getShopId(), Field::T_RAW);
                $oUser->oxuser__oxfname = new Field($aParams['oxuser__oxfname'], Field::T_RAW);
                $oUser->oxuser__oxlname = new Field($aParams['oxuser__oxlname'], Field::T_RAW);
                $oUser->oxuser__oxsal = new Field($aParams['oxuser__oxsal'], Field::T_RAW);
                $oUser->oxuser__oxcountryid = new Field($aParams['oxuser__oxcountryid'], Field::T_RAW);
                $blUserLoaded = $oUser->save();
            }
        } else {
            $blUserLoaded = $oUser->load($oUser->getId());
        }


        // if user was added/loaded successfully and subscribe is on - subscribing to newsletter
        if ($blSubscribe && $blUserLoaded) {
            //removing user from subscribe list before adding
            $oUser->setNewsSubscription(false, false);

            $blOrderOptInEmail = Registry::getConfig()->getConfigParam('blOrderOptInEmail');
            if ($oUser->setNewsSubscription(true, $blOrderOptInEmail)) {
                // done, confirmation required?
                if ($blOrderOptInEmail) {
                    $this->_iNewsletterStatus = 1;
                } else {
                    $this->_iNewsletterStatus = 2;
                }
            } else {
                Registry::getUtilsView()->addErrorToDisplay('MESSAGE_NOT_ABLE_TO_SEND_EMAIL');
            }
        } elseif (!$blSubscribe && $blUserLoaded) {
            // unsubscribing user
            $oUser->setNewsSubscription(false, false);
            $this->_iNewsletterStatus = 3;
        }
    }

    /**
     * Loads user and Adds him to newsletter group.
     *
     * Template variables:
     * <b>success</b>
     */
    public function addme()
    {
        // user exists ?
        $oUser = oxNew(User::class);
        if ($oUser->load(Registry::getRequest()->getRequestEscapedParameter('uid'))) {
            $sConfirmCode = md5($oUser->oxuser__oxusername->value . $oUser->oxuser__oxpasssalt->value);
            // is confirmed code ok?
            if (Registry::getRequest()->getRequestEscapedParameter('confirm') == $sConfirmCode) {
                $oUser->getNewsSubscription()->setOptInStatus(1);
                $oUser->addToGroup('oxidnewsletter');
                $this->_iNewsletterStatus = 2;
            }
        }
    }

    /**
     * Loads user and removes him from newsletter group.
     */
    public function removeme()
    {
        // existing user ?
        $oUser = oxNew(User::class);
        if ($oUser->load(Registry::getRequest()->getRequestEscapedParameter('uid'))) {
            $oUser->getNewsSubscription()->setOptInStatus(0);

            // removing from group ...
            $oUser->removeFromGroup('oxidnewsletter');

            $this->_iNewsletterStatus = 3;
        }
    }

    /**
     * symlink to function removeme bug fix #0002894
     */
    public function rmvm()
    {
        $this->removeme();
    }

    /**
     * Template variable getter. Returns action articlelist
     *
     * @return object
     */
    public function getTopStartActionArticles()
    {
        if ($this->_oActionArticles === null) {
            $this->_oActionArticles = false;
            if (Registry::getConfig()->getConfigParam('bl_perfLoadAktion')) {
                $oArtList = oxNew(ArticleList::class);
                $oArtList->loadActionArticles('OXTOPSTART');
                if ($oArtList->count()) {
                    $this->_oTopArticle = $oArtList->current();
                    $this->_oActionArticles = $oArtList;
                }
            }
        }

        return $this->_oActionArticles;
    }

    /**
     * Template variable getter. Returns top start article
     *
     * @return object
     */
    public function getTopStartArticle()
    {
        if ($this->_oTopArticle === null) {
            $this->_oTopArticle = false;
            if ($this->getTopStartActionArticles()) {
                return $this->_oTopArticle;
            }
        }

        return $this->_oTopArticle;
    }

    /**
     * Template variable getter. Returns country id
     *
     * @return string
     */
    public function getHomeCountryId()
    {
        if ($this->_sHomeCountryId === null) {
            $this->_sHomeCountryId = false;
            $aHomeCountry = Registry::getConfig()->getConfigParam('aHomeCountry');
            if (is_array($aHomeCountry)) {
                $this->_sHomeCountryId = current($aHomeCountry);
            }
        }

        return $this->_sHomeCountryId;
    }

    /**
     * Template variable getter. Returns newsletter subscription status
     *
     * @return integer
     */
    public function getNewsletterStatus()
    {
        return $this->_iNewsletterStatus;
    }

    /**
     * Template variable getter. Returns user newsletter registration data
     *
     * @return array
     */
    public function getRegParams()
    {
        return $this->_aRegParams;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];
        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString('STAY_INFORMED', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();

        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Page title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->getNewsletterStatus() == 4 || !$this->getNewsletterStatus()) {
            $sConstant = 'STAY_INFORMED';
        } elseif ($this->getNewsletterStatus() == 1) {
            $sConstant = 'MESSAGE_THANKYOU_FOR_SUBSCRIBING_NEWSLETTERS';
        } elseif ($this->getNewsletterStatus() == 2) {
            $sConstant = 'MESSAGE_NEWSLETTER_CONGRATULATIONS';
        } elseif ($this->getNewsletterStatus() == 3) {
            $sConstant = 'SUCCESS';
        }

        return Registry::getLang()->translateString($sConstant, Registry::getLang()->getBaseLanguage(), false);
    }
}
