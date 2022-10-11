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

/**
 * Variant selection container class
 *
 */
class Selection
{
    /**
     * Selection name
     *
     * @var string
     */
    protected $_sName = null;

    /**
     * Selection value
     *
     * @var string
     */
    protected $_sValue = null;

    /**
     * Selection state: active
     *
     * @var bool
     */
    protected $_blActive = null;

    /**
     * Selection state: disabled
     *
     * @var bool
     */
    protected $_blDisabled = null;

    /**
     * Initializes oxSelection object
     *
     * @param string $sName      selection name
     * @param string $sValue     selection value
     * @param string $blDisabled selection state - disabled/enabled
     * @param string $blActive   selection state - active/inactive
     */
    public function __construct($sName, $sValue, $blDisabled, $blActive)
    {
        $this->_sName = $sName;
        $this->_sValue = $sValue;
        $this->_blDisabled = $blDisabled;
        $this->_blActive = $blActive;
    }

    /**
     * Returns selection value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->_sValue;
    }

    /**
     * Returns selection name
     *
     * @return string
     */
    public function getName()
    {
        return getStr()->htmlspecialchars($this->_sName);
    }

    /**
     * Returns TRUE if current selection is active (chosen)
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->_blActive;
    }

    /**
     * Returns TRUE if current selection is disabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->_blDisabled;
    }

    /**
     * Sets selection active/inactive
     *
     * @param bool $blActive selection state TRUE/FALSE
     */
    public function setActiveState($blActive)
    {
        $this->_blActive = $blActive;
    }

    /**
     * Sets selection disabled/enables
     *
     * @param bool $blDisabled selection state TRUE/FALSE
     */
    public function setDisabled($blDisabled)
    {
        $this->_blDisabled = $blDisabled;
    }

    /**
     * Returns selection link (currently returns "#")
     *
     * @return string
     */
    public function getLink()
    {
        return "#";
    }
}
