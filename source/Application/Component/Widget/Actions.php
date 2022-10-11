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
 * Actions widget.
 * Access actions in tpl.
 */
class Actions extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/product/action.tpl';

    /**
     * Are actions on
     *
     * @var bool
     */
    protected $_blLoadActions = null;

    /**
     * Returns article list with action articles
     *
     * @return object
     */
    public function getAction()
    {
        $actionId = $this->getViewParameter('action');
        if ($actionId && $this->_getLoadActionsParam()) {
            $artList = oxNew(\OxidEsales\Eshop\Application\Model\ArticleList::class);
            $artList->loadActionArticles($actionId);
            if ($artList->count()) {
                return $artList;
            }
        }
    }

    /**
     * Returns if actions are ON
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLoadActionsParam" in next major
     */
    protected function _getLoadActionsParam() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_blLoadActions = $this->getConfig()->getConfigParam('bl_perfLoadAktion');

        return $this->_blLoadActions;
    }

    /**
     * Returns action name
     *
     * @return string
     */
    public function getActionName()
    {
        $actionId = $this->getViewParameter('action');
        $action   = oxNew(\OxidEsales\Eshop\Application\Model\Actions::class);
        if ($action->load($actionId)) {
            return $action->oxactions__oxtitle->value;
        }
    }

    /**
     * Returns products list type
     *
     * @return string
     */
    public function getListType()
    {
        return $this->getViewParameter('listtype');
    }
}
