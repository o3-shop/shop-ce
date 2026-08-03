<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Model\Search;
use OxidEsales\Eshop\Core\Header;
use OxidEsales\Eshop\Core\Registry;

class SearchSuggestController extends FrontendController
{
    public const DEFAULT_SUGGESTIONS = 10;
    public const MIN_SEARCH_LENGTH = 2;
    public const MAX_SUGGESTIONS = 50;

    public function render()
    {
        $oConfig = Registry::getConfig();

        Registry::get(Header::class)->setHeader('Content-Type: application/json; charset=UTF-8');
        if (!$oConfig->getConfigParam('blSearchSuggest')) {
            Registry::getUtils()->showMessageAndExit('[]');
            return;
        }

        $oRequest = Registry::getRequest();
        $sSearchParam = trim((string) $oRequest->getRequestParameter('searchparam'));

        $aResult = [];
        if (mb_strlen($sSearchParam) >= self::MIN_SEARCH_LENGTH) {
            $iLimit = (int) $oConfig->getConfigParam('iSearchSuggestCount');
            if ($iLimit < 1) {
                $iLimit = self::DEFAULT_SUGGESTIONS;
            }
            $iLimit = min($iLimit, self::MAX_SUGGESTIONS);

            $oSearchHandler = oxNew(Search::class);
            $aResult = $oSearchHandler->getSearchSuggestions($sSearchParam, $iLimit);
        }

        Registry::getUtils()->showMessageAndExit(json_encode($aResult, JSON_THROW_ON_ERROR));
    }
}
