<?php
/**
 * This file is part of O3-Shop Community Edition.
 *
 * O3-Shop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * O3-Shop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2017
 * @version       O3-Shop CE
 */

class test_12_ajax_controller extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    public function render()
    {
        parent::render();

        if (oxRegistry::getConfig()->getRequestParameter("aoc")) {
            $ajax = oxNew("test_12_ajax_controller_ajax");
            $this->_aViewData['oxajax_result'] = oxRegistry::getConfig()->getConfigParam('testModule12AjaxCalledSuccessfully');
            $this->_aViewData['oxajax'] = $ajax->getFeedback();

            return "test_12_popup.tpl";
        }

        return "test_12_ajax_controller.tpl";
    }
}
