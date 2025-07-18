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

use OxidEsales\Eshop\Application\Model\Discount;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\InputException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use stdClass;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;

/**
 * Admin article main discount manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Shop Settings -> Discounts -> Main.
 */
class DiscountMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates article category tree, passes
     * data to Smarty engine and returns name of template file "discount_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $sOxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($sOxId) && $sOxId != "-1") {
            // load object
            $oDiscount = oxNew(Discount::class);
            $oDiscount->loadInLang($this->_iEditLang, $sOxId);

            $oOtherLang = $oDiscount->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oDiscount->loadInLang(key($oOtherLang), $sOxId);
            }

            $this->_aViewData["edit"] = $oDiscount;

            //disabling derived items
            if ($oDiscount->isDerived()) {
                $this->_aViewData["readonly"] = true;
            }

            // remove already created languages
            $aLang = array_diff(Registry::getLang()->getLanguageNames(), $oOtherLang);

            if (count($aLang)) {
                $this->_aViewData["posslang"] = $aLang;
            }

            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }
        }

        if (($iAoc = Registry::getRequest()->getRequestEscapedParameter('aoc'))) {
            if ($iAoc == "1") {
                $oDiscountMainAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\DiscountMainAjax::class);
                $this->_aViewData['oxajax'] = $oDiscountMainAjax->getColumns();

                return "popups/discount_main.tpl";
            } elseif ($iAoc == "2") {
                // generating category tree for artikel choose select list
                $this->createCategoryTree("artcattree");

                $oDiscountItemAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\DiscountItemAjax::class);
                $this->_aViewData['oxajax'] = $oDiscountItemAjax->getColumns();

                return "popups/discount_item.tpl";
            }
        }

        return "discount_main.tpl";
    }

    /**
     * Returns item discount product title
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getItemDiscountProductTitle()
    {
        $sTitle = false;
        $sOxId = $this->getEditObjectId();
        if (isset($sOxId) && $sOxId != "-1") {
            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName("oxarticles", $this->_iEditLang);
            // Reading from slave is ok here (see ESDEV-3804 and ESDEV-3822).
            $database = DatabaseProvider::getDb();
            $sQ = "select concat( $sViewName.oxartnum, ' ', $sViewName.oxtitle ) from oxdiscount
                   left join $sViewName on $sViewName.oxid=oxdiscount.oxitmartid
                   where oxdiscount.oxitmartid != '' and oxdiscount.oxid = :oxid";
            $sTitle = $database->getOne($sQ, [
                ':oxid' => $sOxId
            ]);
        }

        return $sTitle ? $sTitle : " -- ";
    }

    /**
     * Saves changed selected discount parameters.
     *
     * @return void
     */
    public function save()
    {
        parent::save();

        $sOxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oDiscount = oxNew(Discount::class);
        if ($sOxId != "-1") {
            $oDiscount->load($sOxId);
        } else {
            $aParams['oxdiscount__oxid'] = null;
        }

        // checkbox handling
        if (!isset($aParams['oxdiscount__oxactive'])) {
            $aParams['oxdiscount__oxactive'] = 0;
        }

        //disabling derived items
        if ($oDiscount->isDerived()) {
            return;
        }

        //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        $oDiscount->setLanguage(0);
        $oDiscount->assign($aParams);
        $oDiscount->setLanguage($this->_iEditLang);
        $oDiscount = Registry::getUtilsFile()->processFiles($oDiscount);
        try {
            $oDiscount->save();
        } catch (InputException $exception) {
            $newException = oxNew(ExceptionToDisplay::class);
            $newException->setMessage($exception->getMessage());
            $this->addTplParam('discount_title', $aParams['oxdiscount__oxtitle']);

            if (false !== strpos($exception->getMessage(), 'DISCOUNT_ERROR_OXSORT')) {
                $messageArgument = Registry::getLang()->translateString('DISCOUNT_MAIN_SORT', Registry::getLang()->getTplLanguage(), true);
                $newException->setMessageArgs($messageArgument);
            }

            Registry::getUtilsView()->addErrorToDisplay($newException);

            return;
        }

        // set oxid if inserted
        $this->setEditObjectId($oDiscount->getId());
    }

    /**
     * Saves changed selected discount parameters in different language.
     *
     * @return void
     */
    public function saveinnlang()
    {
        parent::save();

        $sOxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oAttr = oxNew(Discount::class);
        if ($sOxId != "-1") {
            $oAttr->load($sOxId);
        } else {
            $aParams['oxdiscount__oxid'] = null;
        }
        // checkbox handling
        if (!isset($aParams['oxdiscount__oxactive'])) {
            $aParams['oxdiscount__oxactive'] = 0;
        }

        //disabling derived items
        if ($oAttr->isDerived()) {
            return;
        }

        //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        $oAttr->setLanguage(0);
        $oAttr->assign($aParams);
        $oAttr->setLanguage($this->_iEditLang);
        $oAttr = Registry::getUtilsFile()->processFiles($oAttr);
        $oAttr->save();

        // set oxid if inserted
        $this->setEditObjectId($oAttr->getId());
    }

    /**
     * Increment the maximum value of oxsort found in the database by certain amount and return it.
     *
     * @return int The incremented oxsort.
     * @throws DatabaseConnectionException
     */
    public function getNextOxsort()
    {
        $shopId = Registry::getConfig()->getShopId();
        return oxNew(Discount::class)->getNextOxsort($shopId);
    }
}
