<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Model\Search;
use OxidEsales\Eshop\Core\Header;
use OxidEsales\Eshop\Core\Registry;

class SearchSuggestController extends FrontendController
{
    public const DEFAULT_SUGGESTIONS = 10;

    public function render()
    {
        $oConfig = Registry::getConfig();

        Registry::get(Header::class)->setHeader('Content-Type: application/json; charset=UTF-8');
        if (!$oConfig->getConfigParam('blSearchSuggest')) {
            Registry::getUtils()->showMessageAndExit('[]');
        }

        $oRequest = Registry::getRequest();
        $sSearchParam = trim((string) $oRequest->getRequestParameter('searchparam'));

        $aResult = [];
        if (mb_strlen($sSearchParam) >= 2) {
            $iLimit = (int) $oConfig->getConfigParam('iSearchSuggestCount');
            if ($iLimit < 1) {
                $iLimit = self::DEFAULT_SUGGESTIONS;
            }
            $iLimit = min($iLimit, 50);

            $oSearchHandler = oxNew(Search::class);
            $aResult = $oSearchHandler->getSearchSuggestions($sSearchParam, $iLimit);
        }

        Registry::get(Header::class)->setHeader('Content-Type: application/json; charset=UTF-8');
        Registry::getUtils()->showMessageAndExit(json_encode($aResult, JSON_THROW_ON_ERROR));
    }
}
