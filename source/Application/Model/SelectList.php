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

use oxRegistry;
use oxDb;

/**
 * Select list manager
 *
 */
class SelectList extends \OxidEsales\Eshop\Core\Model\MultiLanguageModel implements \OxidEsales\Eshop\Core\Contract\ISelectList
{
    /**
     * Select list fields array
     *
     * @var array
     */
    protected $_aFieldList = null;

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxselectlist';

    /**
     * Selections array
     *
     * @var array()
     */
    protected $_aList = null;

    /**
     * Product VAT
     *
     * @var float
     */
    protected $_dVat = null;

    /**
     * Active selection object
     *
     * @var oxSelection
     */
    protected $_oActiveSelection = null;

    /**
     * Calls parent constructor and initializes selection list
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxselectlist');
    }

    /**
     * Returns select list value list.
     *
     * @param double $dVat VAT value
     *
     * @return array
     */
    public function getFieldList($dVat = null)
    {
        if ($this->_aFieldList == null && $this->oxselectlist__oxvaldesc->value) {
            $this->_aFieldList = \OxidEsales\Eshop\Core\Registry::getUtils()->assignValuesFromText($this->oxselectlist__oxvaldesc->value, $dVat);
            foreach ($this->_aFieldList as $sKey => $oField) {
                $this->_aFieldList[$sKey]->name = getStr()->strip_tags($this->_aFieldList[$sKey]->name);
            }
        }

        return $this->_aFieldList;
    }

    /**
     * Removes selectlists from articles.
     *
     * @param string $sOXID object ID (default null)
     *
     * @return bool
     */
    public function delete($sOXID = null)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }
        if (!$sOXID) {
            return false;
        }

        // remove selectlists from articles also
        if ($blRemove = parent::delete($sOXID)) {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oDb->execute("delete from oxobject2selectlist where oxselnid = :oxselnid", [
                ':oxselnid' => $sOXID
            ]);
        }

        return $blRemove;
    }

    /**
     * VAT setter
     *
     * @param float $dVat product VAT
     */
    public function setVat($dVat)
    {
        $this->_dVat = $dVat;
    }

    /**
     * Returns VAT set by oxSelectList::setVat()
     *
     * @return float
     */
    public function getVat()
    {
        return $this->_dVat;
    }

    /**
     * Returns variant selection list label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->oxselectlist__oxtitle->value;
    }

    /**
     * Returns array of oxSelection's
     *
     * @return array
     */
    public function getSelections()
    {
        if ($this->_aList === null && $this->oxselectlist__oxvaldesc->value) {
            $this->_aList = false;
            $aList = \OxidEsales\Eshop\Core\Registry::getUtils()->assignValuesFromText($this->oxselectlist__oxvaldesc->getRawValue(), $this->getVat());
            foreach ($aList as $sKey => $oField) {
                if ($oField->name) {
                    $this->_aList[$sKey] = oxNew(\OxidEsales\Eshop\Application\Model\Selection::class, getStr()->strip_tags($oField->name), $sKey, false, $this->_aList === false ? true : false);
                }
            }
        }

        return $this->_aList;
    }

    /**
     * Returns active selection object
     *
     * @return oxSelection
     */
    public function getActiveSelection()
    {
        if ($this->_oActiveSelection === null) {
            if (($aSelections = $this->getSelections())) {
                // first is allways active
                $this->_oActiveSelection = reset($aSelections);
            }
        }

        return $this->_oActiveSelection;
    }

    /**
     * Activates given by index selection
     *
     * @param int $iIdx selection index
     */
    public function setActiveSelectionByIndex($iIdx)
    {
        if (($aSelections = $this->getSelections())) {
            $iSelIdx = 0;
            foreach ($aSelections as $oSelection) {
                $oSelection->setActiveState($iSelIdx == $iIdx);
                if ($iSelIdx == $iIdx) {
                    $this->_oActiveSelection = $oSelection;
                }
                $iSelIdx++;
            }
        }
    }
}
