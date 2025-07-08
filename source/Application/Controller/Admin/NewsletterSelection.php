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
use OxidEsales\Eshop\Application\Model\Newsletter;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * @deprecated Functionality for Newsletter management will be removed.
 * Newsletter user group selection manager.
 * Adds/removes chosen user group to/from newsletter mailing.
 * Admin Menu: Customer Info -> Newsletter -> Selection.
 */
class NewsletterSelection extends AdminDetailsController
{
    /**
     * Amount of users assigned to active newsletter receiver group
     *
     * @var int
     */
    protected $_iUserCount = null;

    /**
     * Executes parent method parent::render(), creates oxlist object and
     * collects user groups information, passes its data to Smarty engine
     * and returns name of template file "newsletter_selection.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oNewsletter = oxNew(Newsletter::class);
            if ($oNewsletter->load($soxId)) {
                $this->_aViewData["edit"] = $oNewsletter;

                if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
                    $oNewsletterSelectionAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\NewsletterSelectionAjax::class);
                    $this->_aViewData['oxajax'] = $oNewsletterSelectionAjax->getColumns();

                    return "popups/newsletter_selection.tpl";
                }
            }
        }

        return "newsletter_selection.tpl";
    }

    /**
     * Returns count of users assigned to active newsletter receiver group
     *
     * @return int
     * @throws DatabaseConnectionException
     */
    public function getUserCount()
    {
        if ($this->_iUserCount === null) {
            $this->_iUserCount = 0;

            // load object
            $oNewsletter = oxNew(Newsletter::class);
            if ($oNewsletter->load($this->getEditObjectId())) {
                // get nr. of users in these groups
                // we do not use lists here as we don't need this overhead right now
                $oDB = DatabaseProvider::getDb();
                $blSep = false;
                $sSelectGroups = " ( oxobject2group.oxgroupsid in ( ";

                // remove already added groups
                foreach ($oNewsletter->getGroups() as $oInGroup) {
                    if ($blSep) {
                        $sSelectGroups .= ",";
                    }
                    $sSelectGroups .= $oDB->quote($oInGroup->oxgroups__oxid->value);
                    $blSep = true;
                }

                $sSelectGroups .= " ) ) ";

                // no group selected
                if (!$blSep) {
                    $sSelectGroups = " oxobject2group.oxobjectid is null ";
                }
                $sShopId = Registry::getConfig()->getShopID();
                $sQ = "select count(*) from ( select oxnewssubscribed.oxemail as _icnt from oxnewssubscribed left join
                   oxobject2group on oxobject2group.oxobjectid = oxnewssubscribed.oxuserid
                   where ( oxobject2group.oxshopid = :oxshopid
                   or oxobject2group.oxshopid is null ) and {$sSelectGroups} and
                   oxnewssubscribed.oxdboptin = 1 and ( not ( oxnewssubscribed.oxemailfailed = '1') )
                   and (not(oxnewssubscribed.oxemailfailed = '1')) and oxnewssubscribed.oxshopid = :oxshopid
                   group by oxnewssubscribed.oxemail ) as _tmp";

                // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
                $this->_iUserCount = DatabaseProvider::getMaster()->getOne($sQ, [
                    ':oxshopid' => $sShopId
                ]);
            }
        }

        return $this->_iUserCount;
    }

    /**
     * Saves newsletter selection changes.
     */
    public function save()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        $aParams['oxnewsletter__oxshopid'] = Registry::getConfig()->getShopId();

        $oNewsletter = oxNew(Newsletter::class);
        if ($soxId != "-1") {
            $oNewsletter->load($soxId);
        } else {
            $aParams['oxnewsletter__oxid'] = null;
        }

        $oNewsletter->assign($aParams);
        $oNewsletter->save();

        // set oxid if inserted
        $this->setEditObjectId($oNewsletter->getId());
    }
}
