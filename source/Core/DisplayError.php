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

namespace OxidEsales\EshopCommunity\Core;

/**
 * simple class to add a error message to display
 */
class DisplayError implements \OxidEsales\Eshop\Core\Contract\IDisplayError
{
    /**
     * Error message
     *
     * @var string $_sMessage
     */
    protected $_sMessage;

    /** @var array */
    private $_aFormatParameters = [];

    /**
     * Formats message using vsprintf if property _aFormatParameters was set and returns translated message.
     *
     * @return string stored message
     */
    public function getOxMessage()
    {
        $translatedMessage = \OxidEsales\Eshop\Core\Registry::getLang()->translateString($this->_sMessage);
        if (!empty($this->_aFormatParameters)) {
            $translatedMessage = vsprintf($translatedMessage, $this->_aFormatParameters);
        }

        return $translatedMessage;
    }

    /**
     * Stored the message.
     *
     * @param string $message message
     */
    public function setMessage($message)
    {
        $this->_sMessage = $message;
    }

    /**
     * Stes format parameters for message.
     *
     * @param array $formatParameters
     */
    public function setFormatParameters($formatParameters)
    {
        $this->_aFormatParameters = $formatParameters;
    }

    /**
     * Returns errorrous class name (currently returns null)
     *
     * @return null
     */
    public function getErrorClassType()
    {
        return null;
    }

    /**
     * Returns value (currently returns empty string)
     *
     * @param string $name value ignored
     *
     * @return string
     */
    public function getValue($name)
    {
        return '';
    }
}
