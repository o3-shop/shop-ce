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

use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Core\Registry;

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
     * @return void|object
     */
    public function getAction()
    {
        $actionId = $this->getViewParameter('action');
        if ($actionId && $this->getLoadActionsParam()) {
            $artList = oxNew(ArticleList::class);
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
     * @deprecated Use getLoadActionsParam() instead. This underscore-prefixed name is
     *             retained only for backward compatibility with module subclasses that
     *             already override it; new code, including new modules, MUST NOT call
     *             or override _getLoadActionsParam().
     */
    protected function _getLoadActionsParam() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_blLoadActions = Registry::getConfig()->getConfigParam('bl_perfLoadAktion');

        return $this->_blLoadActions;
    }

    /**
     * Returns if actions are ON
     *
     * @return string
     *
     * @internal If your override does not fully replace the behavior, call
     *           parent::getLoadActionsParam() (not the deprecated _getLoadActionsParam())
     *           so downstream overrides in the class chain are preserved. Template-method
     *           refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getLoadActionsParam()
    {
        return $this->_getLoadActionsParam();
    }

    /**
     * Returns action name
     *
     * @return void|string
     */
    public function getActionName()
    {
        $actionId = $this->getViewParameter('action');
        $action = oxNew(\OxidEsales\Eshop\Application\Model\Actions::class);
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
