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

use OxidEsales\Eshop\Application\Controller\Admin\NewsletterSelection;
use OxidEsales\Eshop\Application\Model\Newsletter;
use OxidEsales\Eshop\Application\Model\Remark;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * @deprecated Functionality for Newsletter management will be removed.
 * Newsletter sending manager.
 * Performs sending of newsletter to selected user groups.
 */
class NewsletterSend extends NewsletterSelection
{
    /**
     * Mail sending errors array
     *
     * @var array
     */
    protected $_aMailErrors = [];

    /**
     * Executes parent method parent::render(), creates oxnewsletter object,
     * sends newsletter to users of chosen groups and returns name of template
     * file "newsletter_send.tpl"/"newsletter_done.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        AdminDetailsController::render();

        // calculating
        $iUserCount = $this->getUserCount();

        $iStart = (int) Registry::getRequest()->getRequestEscapedParameter('iStart');

        $oNewsletter = oxNew(Newsletter::class);
        $oNewsletter->load($this->getEditObjectId());
        $oNewsletterGroups = $oNewsletter->getGroups();

        // send emails....
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sQGroups = " ( oxobject2group.oxgroupsid in ( ";
        $blSep = false;
        foreach ($oNewsletterGroups as $sInGroup) {
            $sSearchKey = $sInGroup->oxgroups__oxid->value;
            if ($blSep) {
                $sQGroups .= ",";
            }
            $sQGroups .= $oDB->quote($sSearchKey);
            $blSep = true;
        }
        $sQGroups .= ") )";

        // no group selected
        if (!$blSep) {
            $sQGroups = " oxobject2group.oxobjectid is null ";
        }

        $myConfig = Registry::getConfig();

        $iSendCnt = 0;
        $iMaxCnt = (int) $myConfig->getConfigParam('iCntofMails');
        $sShopId = $myConfig->getShopId();

        $sQ = "select oxnewssubscribed.oxuserid, oxnewssubscribed.oxemail, oxnewssubscribed.oxsal,
           oxnewssubscribed.oxfname, oxnewssubscribed.oxlname, oxnewssubscribed.oxemailfailed
           from oxnewssubscribed left join oxobject2group on
           oxobject2group.oxobjectid = oxnewssubscribed.oxuserid where
           ( oxobject2group.oxshopid = :oxshopid or oxobject2group.oxshopid is null ) and
           $sQGroups and oxnewssubscribed.oxdboptin = 1 and oxnewssubscribed.oxshopid = :oxshopid
           group by oxnewssubscribed.oxemail";

        $oRs = $oDB->selectLimit($sQ, 100, $iStart, [
            ':oxshopid' => $sShopId
        ]);
        $blContinue = ($oRs != false && $oRs->count() > 0);

        if ($blContinue) {
            $blLoadAction = $myConfig->getConfigParam('bl_perfLoadAktion');
            while (!$oRs->EOF && $iSendCnt < $iMaxCnt) {
                if ($oRs->fields['oxemailfailed'] != "1") {
                    $sUserId = $oRs->fields['oxuserid'];
                    $iSendCnt++;

                    // must check if such user is in DB
                    // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
                    if (!DatabaseProvider::getMaster()->getOne("select oxid from oxuser where oxid = :oxid", [':oxid' => $sUserId])) {
                        $sUserId = null;
                    }

                    // #559
                    if (!isset($sUserId) || !$sUserId) {
                        // there is no user object so we fake one
                        $oUser = oxNew(User::class);
                        $oUser->oxuser__oxusername = new Field($oRs->fields['oxemail']);
                        $oUser->oxuser__oxsal = new Field($oRs->fields['oxsal']);
                        $oUser->oxuser__oxfname = new Field($oRs->fields['oxfname']);
                        $oUser->oxuser__oxlname = new Field($oRs->fields['oxlname']);
                        $oNewsletter->prepare($oUser, $blLoadAction);
                    } else {
                        $oNewsletter->prepare($sUserId, $blLoadAction);
                    }

                    if ($oNewsletter->send()) {
                        // add user history
                        $oRemark = oxNew(Remark::class);
                        $oRemark->oxremark__oxtext = new Field($oNewsletter->getPlainText());
                        $oRemark->oxremark__oxparentid = new Field($sUserId);
                        $oRemark->oxremark__oxshopid = new Field($sShopId);
                        $oRemark->oxremark__oxtype = new Field("n");
                        $oRemark->save();
                    } else {
                        $this->_aMailErrors[] = "problem sending to : " . $oRs->fields['oxemail'];
                    }
                }

                $oRs->fetchRow();
                $iStart++;
            }
        }

        $iSend = $iSendCnt + (ceil($iStart / $iMaxCnt) - 1) * $iMaxCnt;
        $iSend = min($iSend, $iUserCount);

        $this->_aViewData["iStart"] = $iStart;
        $this->_aViewData["iSend"] = $iSend;

        // end ?
        if ($blContinue) {
            return "newsletter_send.tpl";
        } else {
            $this->resetUserCount();

            return "newsletter_done.tpl";
        }
    }

    /**
     * Returns count of users assigned to active newsletter receiver group
     *
     * @return int
     * @throws DatabaseConnectionException
     */
    public function getUserCount()
    {
        $iCnt = Registry::getSession()->getVariable("iUserCount");
        if ($iCnt === null) {
            $iCnt = parent::getUserCount();
            Registry::getSession()->setVariable("iUserCount", $iCnt);
        }

        return $iCnt;
    }

    /**
     * Resets users count
     */
    public function resetUserCount()
    {
        Registry::getSession()->deleteVariable("iUserCount");
        $this->_iUserCount = null;
    }

    /**
     * Returns newsletter mailing errors
     *
     * @return array
     */
    public function getMailErrors()
    {
        return $this->_aMailErrors;
    }

    /**
     * Overrides parent method to pass referred id
     *
     * @param string $sNode referred id
     * @deprecated underscore prefix violates PSR12, will be renamed to "setupNavigation" in next major
     */
    protected function _setupNavigation($sNode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setupNavigation($sNode);
    }

    /**
     * Overrides parent method to pass referred id
     *
     * @param string $sNode referred id
     */
    protected function setupNavigation($sNode)
    {
        $sNode = 'newsletter_list';

        $myAdminNavig = $this->getNavigation();

        // active tab
        $iActTab = 3;

        // tabs
        $this->_aViewData['editnavi'] = $myAdminNavig->getTabs($sNode, $iActTab);

        // active tab
        $this->_aViewData['actlocation'] = $myAdminNavig->getActiveTab($sNode, $iActTab);

        // default tab
        $this->_aViewData['default_edit'] = $myAdminNavig->getActiveTab($sNode, $this->_iDefEdit);

        // passing active tab number
        $this->_aViewData['actedit'] = $iActTab;
    }

    /**
     * Does nothing, called in derived template
     */
    public function getListSorting()
    {
    }
}
