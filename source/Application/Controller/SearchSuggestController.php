<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Model\Search;
use OxidEsales\Eshop\Core\Registry;

class SearchSuggestController extends FrontendController
{
    protected $_sThisTemplate = 'widget/header/searchsuggest.tpl';

    public const MAX_SUGGESTIONS = 8;

    public function render()
    {
        $oRequest = Registry::getRequest();
        $sSearchParam = trim((string) $oRequest->getRequestParameter('searchparam'));

        $aResult = [];
        if (mb_strlen($sSearchParam) >= 2) {
            $oSearchHandler = oxNew(Search::class);
            $aResult = $oSearchHandler->getSearchSuggestions($sSearchParam, self::MAX_SUGGESTIONS);
        }

        $this->_aViewData['suggestionsJson'] = json_encode($aResult, JSON_THROW_ON_ERROR);

        parent::render();

        return $this->_sThisTemplate;
    }
}
