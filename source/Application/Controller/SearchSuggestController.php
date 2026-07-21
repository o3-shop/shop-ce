<?php

declare(strict_types=1);

namespace OxidEsales\Eshop\Application\Controller;

use OxidEsales\Eshop\Application\Model\Search;
use OxidEsales\Eshop\Core\Registry;

class SearchSuggestController extends FrontendController
{
    public const MAX_SUGGESTIONS = 8;

    public function render(): string
    {
        $oRequest = Registry::getRequest();
        $sSearchParam = trim((string) $oRequest->getRequestParameter('searchparam'));

        $aResult = [];
        if (mb_strlen($sSearchParam) >= 2) {
            $oSearchHandler = oxNew(Search::class);
            $aResult = $oSearchHandler->getSearchSuggestions($sSearchParam, self::MAX_SUGGESTIONS);
        }

        $this->_aViewData['suggestions'] = $aResult;

        return parent::render();
    }
}
