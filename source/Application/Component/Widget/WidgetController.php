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

namespace OxidEsales\EshopCommunity\Application\Component\Widget;

/**
 * Widget parent.
 * Gather functionality needed for all widgets but not for other views.
 */
class WidgetController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * Widget should rewrite and use only those which  it needs.
     *
     * @var array
     */
    protected $_aComponentNames = [];

    /**
     * If active load components
     * Widgets loads active view components
     *
     * @var array
     */
    protected $_blLoadComponents = false;

    /**
     * Sets self::$_aCollectedComponentNames to null, as views and widgets
     * controllers loads different components and calls parent::init()
     */
    public function init()
    {
        self::$_aCollectedComponentNames = null;

        if (!empty($this->_aComponentNames)) {
            foreach ($this->_aComponentNames as $sComponentName => $sCompCache) {
                $oActTopView = $this->getConfig()->getTopActiveView();
                if ($oActTopView) {
                    $this->_oaComponents[$sComponentName] = $oActTopView->getComponent($sComponentName);
                    if (!isset($this->_oaComponents[$sComponentName])) {
                        $this->_blLoadComponents = true;
                        break;
                    } else {
                        $this->_oaComponents[$sComponentName]->setParent($this);
                    }
                }
            }
        }

        parent::init();
    }

    /**
     * In widgets we do not need to parse seo and do any work related to that
     * Shop main control is responsible for that, and that has to be done once
     * @deprecated underscore prefix violates PSR12, will be renamed to "processRequest" in next major
     */
    protected function _processRequest() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }
}
