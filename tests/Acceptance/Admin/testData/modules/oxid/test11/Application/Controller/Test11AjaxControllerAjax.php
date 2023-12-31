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

namespace OxidEsales\EshopCommunity\Tests\Acceptance\Admin\testData\modules\oxid\test11\Application\Controller;

class Test11AjaxControllerAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * @inheritdoc
     */
    public function processRequest($function = null)
    {
        $response = 'test_11_ajax_controller successfully called';
        \OxidEsales\Eshop\Core\Registry::getConfig()->saveShopConfVar('str', 'testModule11AjaxCalledSuccessfully', $response);

        $this->_outputResponse('test_11_ajax_controller successfully called');
    }

    public function getFeedback()
    {
        \OxidEsales\Eshop\Core\Registry::getConfig()->saveShopConfVar('str', 'testModule11AjaxCalledSuccessfully', '');
        $this->_output('test_11_ajax_controller getFeedback');
    }
}
