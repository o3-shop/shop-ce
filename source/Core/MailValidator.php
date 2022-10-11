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
 * Class MailValidator
 *
 * @deprecated on 6.5.1 at 2020-02-20. Use OxidEsales\EshopCommunity\Internal\Domain\Email\EmailValidationService instead.
 */
class MailValidator
{
    /**
     * @var string
     */
    private $_sMailValidationRule = null;

    /**
     * Get mail validation rule.
     *
     * @return string
     */
    public function getMailValidationRule()
    {
        if (is_null($this->_sMailValidationRule)) {
            $this->_sMailValidationRule = "/^([\w+\-.])+\@([\w\-.])+\.([A-Za-z]{2,64})$/i";
        }

        return $this->_sMailValidationRule;
    }

    /**
     * Override mail validation rule.
     *
     * @param string $sMailValidationRule mail validation rule
     */
    public function setMailValidationRule($sMailValidationRule)
    {
        $this->_sMailValidationRule = $sMailValidationRule;
    }

    /**
     * Set mail validation rule from config.
     * Would use default rule if not defined in config.
     */
    public function __construct()
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $sEmailValidationRule = $oConfig->getConfigParam('sEmailValidationRule');
        if (!empty($sEmailValidationRule)) {
            $this->_sMailValidationRule = $sEmailValidationRule;
        }
    }

    /**
     * User email validation function. Returns true if email is OK otherwise - false;
     * Syntax validation is performed only.
     *
     * @param string $sEmail user email
     *
     * @return bool
     */
    public function isValidEmail($sEmail)
    {
        $sEmailRule = $this->getMailValidationRule();

        return (getStr()->preg_match($sEmailRule, $sEmail) != 0);
    }
}
