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
 * Class for validating address
 *
 */
class RequiredFieldsValidator
{
    /**
     * Required fields array.
     *
     * @var array
     */
    private $_aRequiredFields = [];

    /**
     * Invalid fields array.
     *
     * @var array
     */
    private $_aInvalidFields = [];

    /**
     * Required Field validator.
     *
     * @var \OxidEsales\Eshop\Application\Model\RequiredFieldValidator
     */
    private $_oFieldValidator = [];

    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Application\Model\RequiredFieldValidator $oFieldValidator
     */
    public function __construct($oFieldValidator = null)
    {
        if (is_null($oFieldValidator)) {
            $oFieldValidator = oxNew(\OxidEsales\Eshop\Application\Model\RequiredFieldValidator::class);
        }
        $this->setFieldValidator($oFieldValidator);
    }

    /**
     * Returns required fields for address.
     *
     * @return array
     */
    public function getRequiredFields()
    {
        return $this->_aRequiredFields;
    }

    /**
     * Sets required fields array
     *
     * @param array $aFields Fields
     */
    public function setRequiredFields($aFields)
    {
        $this->_aRequiredFields = $aFields;
    }

    /**
     * Returns required fields for address.
     *
     * @return \OxidEsales\Eshop\Application\Model\RequiredFieldValidator
     */
    public function getFieldValidator()
    {
        return $this->_oFieldValidator;
    }

    /**
     * Sets required fields array
     *
     * @param \OxidEsales\Eshop\Application\Model\RequiredFieldValidator $oFieldValidator
     */
    public function setFieldValidator($oFieldValidator)
    {
        $this->_oFieldValidator = $oFieldValidator;
    }

    /**
     * Gets invalid fields.
     *
     * @return array
     */
    public function getInvalidFields()
    {
        return $this->_aInvalidFields;
    }

    /**
     * Checks if all required fields are filled.
     * Returns array of invalid fields or empty array if all fields are fine.
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $oObject Address fields with values.
     *
     * @return bool If any invalid field exist.
     */
    public function validateFields($oObject)
    {
        $aRequiredFields = $this->getRequiredFields();
        $oFieldValidator = $this->getFieldValidator();

        $aInvalidFields = [];
        foreach ($aRequiredFields as $sFieldName) {
            if (!$oFieldValidator->validateFieldValue($oObject->getFieldData($sFieldName))) {
                $aInvalidFields[] = $sFieldName;
            }
        }
        $this->_setInvalidFields($aInvalidFields);

        return empty($aInvalidFields);
    }

    /**
     * Add fields to invalid fields array.
     *
     * @param array $aFields Invalid field name.
     */
    private function _setInvalidFields($aFields) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_aInvalidFields = $aFields;
    }
}
