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

/**
 * Displays exception errors
 */
class ExceptionErrorController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'message/exception.tpl';

    /** @var array Remove loading of components on exception handling. */
    protected $_aComponentNames = [];

    /**
     * Sets exception errros to template
     */
    public function displayExceptionError()
    {
        $aViewData = $this->getViewData();

        //add all exceptions to display
        $aErrors = $this->_getErrors();

        if (is_array($aErrors) && count($aErrors)) {
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->passAllErrorsToView($aViewData, $aErrors);
        }

        $this->addTplParam('Errors', $aViewData['Errors']);

        // resetting errors from session
        \OxidEsales\Eshop\Core\Registry::getSession()->setVariable('Errors', []);
    }

    /**
     * return page errors array
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getErrors" in next major
     */
    protected function _getErrors() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aErrors = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('Errors');

        if (null === $aErrors) {
            $aErrors = [];
        }

        return $aErrors;
    }
}
