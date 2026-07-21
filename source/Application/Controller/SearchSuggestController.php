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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\Eshop\Application\Controller;

use OxidEsales\Eshop\Application\Model\Search;
use OxidEsales\Eshop\Core\Registry;

/**
 * Search suggest controller - provides AJAX search suggestions for the search dropdown.
 */
class SearchSuggestController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Max number of suggestions to return.
     */
    const MAX_SUGGESTIONS = 8;

    /**
     * Handles AJAX search suggestion requests.
     * Returns JSON array of matching articles.
     */
    public function render()
    {
        $oRequest = Registry::getRequest();
        $sSearchParam = trim((string) $oRequest->getRequestParameter('searchparam'));

        $aResult = [];

        if (mb_strlen($sSearchParam) >= 2) {
            /** @var Search $oSearchHandler */
            $oSearchHandler = oxNew(Search::class);
            $aResult = $oSearchHandler->getSearchSuggestions($sSearchParam, self::MAX_SUGGESTIONS);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($aResult);
        exit;
    }
}
