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
use OxidEsales\Eshop\Core\Registry;

/**
 * Displays exception errors
 */
class ExceptionErrorController extends FrontendController
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
     * Sets exception errors to template
     */
    public function displayExceptionError()
    {
        $aViewData = $this->getViewData();

        //add all exceptions to display
        $aErrors = $this->_getErrors();

        if (is_array($aErrors) && count($aErrors)) {
            Registry::getUtilsView()->passAllErrorsToView($aViewData, $aErrors);
        }

        $this->addTplParam('Errors', $aViewData['Errors']);

        // resetting errors from session
        Registry::getSession()->setVariable('Errors', []);
    }

    /**
     * return page errors array
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getErrors" in next major
     */
    protected function _getErrors() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aErrors = Registry::getSession()->getVariable('Errors');

        if (null === $aErrors) {
            $aErrors = [];
        }

        return $aErrors;
    }
}
