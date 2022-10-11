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

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

abstract class AbstractInsertNewBasketItemLogic
{

    /**
     * @param array  $params
     * @param object $templateEngine
     *
     * @return string
     */
    public function getNewBasketItemTemplate(array $params, $templateEngine): string
    {
        if (!$this->validateTemplateEngine($templateEngine)) {
            throw new \Exception('Please check if correct template engine is used.');
        }
        $renderedTemplate = '';
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();

        $types = ['0' => 'none', '1' => 'message', '2' => 'popup', '3' => 'basket'];
        $newBasketItemMessage = $config->getConfigParam('iNewBasketItemMessage');

        // If correct type of message is expected
        if ($newBasketItemMessage && $params['type'] && ($params['type'] != $types[$newBasketItemMessage])) {
            $correctMessageType = false;
        } else {
            $correctMessageType = true;
        }

        //name of template file where is stored message text
        $templateName = $params['tpl'] ? $params['tpl'] : 'inc_newbasketitem.snippet.html.twig';

        //always render for ajaxstyle popup
        $render = $params['ajax'] && ($newBasketItemMessage == 2);

        //fetching article data
        $newItem = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('_newitem');

        if ($newItem && $correctMessageType) {
            $this->loadArticleObject($newItem, $templateEngine);
            $render = true;
        }

        // returning generated message content
        if ($render && $correctMessageType) {
            $renderedTemplate = $this->renderTemplate($templateName, $templateEngine);
        }

        return $renderedTemplate;
    }

    /**
     * @param object $templateEngine
     *
     * @return mixed
     */
    abstract protected function validateTemplateEngine($templateEngine);

    /**
     * @param object $newItem
     * @param object $templateEngine
     *
     * @return mixed
     */
    abstract protected function loadArticleObject($newItem, $templateEngine);

    /**
     * @param string $templateName
     * @param object $templateEngine
     *
     * @return mixed
     */
    abstract protected function renderTemplate(string $templateName, $templateEngine);
}
