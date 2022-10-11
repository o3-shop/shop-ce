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

use Twig\Environment;

class InsertNewBasketItemLogicTwig extends AbstractInsertNewBasketItemLogic
{
    /**
     * @param Environment $templateEngine
     *
     * @return bool
     */
    protected function validateTemplateEngine($templateEngine): bool
    {
        return $templateEngine instanceof Environment;
    }

    /**
     * @param object      $newItem
     * @param Environment $templateEngine
     */
    protected function loadArticleObject($newItem, $templateEngine)
    {
        // loading article object here because on some system passing article by session causes problems
        $newItem->oArticle = oxNew('oxarticle');
        $newItem->oArticle->Load($newItem->sId);

        // passing variable to template with unique name
        $templateEngine->addGlobal('_newitem', $newItem);

        // deleting article object data
        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('_newitem');
    }

    /**
     * @param string      $templateName
     * @param Environment $templateEngine
     *
     * @return string
     */
    protected function renderTemplate(string $templateName, $templateEngine): string
    {
        $template = $templateEngine->load($templateName);
        $renderedTemplate = $template->render();

        return $renderedTemplate;
    }
}
