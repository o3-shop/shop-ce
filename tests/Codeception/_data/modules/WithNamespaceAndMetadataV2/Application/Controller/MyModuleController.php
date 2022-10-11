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

namespace OxidEsales\EshopCommunity\Tests\Acceptance\Admin\testData\modules\Vendor1\WithNamespaceAndMetadataV2\Application\Controller;

use OxidEsales\Eshop\Core\Registry;

/**
 * Class MyModuleController
 *
 * @package OxidEsales\EshopCommunity\Tests\Acceptance\Admin\testData\modules\Vendor1\WithNamespaceAndMetadataV2\Application\Controller
 */
class MyModuleController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Current view template
     *
     * @var string
     */
    protected $_sThisTemplate = 'vendor1_controller_routing.tpl';

    /**
     * Message from request
     */
    protected $message = '';

    /**
     * Rendering method.
     *
     * @return mixed
     */
    public function render()
    {
        $template = parent::render();

        return $template;
    }

    /**
     * Display message.
     */
    public function displayMessage()
    {
        $this->_aViewData['the_module_message'] =  $this->getMessage();
        $this->render();
    }

    /**
     * Template variable getter. Returns entered message
     *
     * @return object
     */
    public function getMessage()
    {
        $this->message = (string) Registry::getConfig()->getRequestParameter('mymodule_message') . ' ' . \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();

        return $this->message;
    }
}
