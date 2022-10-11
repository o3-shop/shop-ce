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

namespace OxidEsales\EshopCommunity\Application\Model;

use oxRegistry;

/**
 * Seo encoder base
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class SeoEncoderRecomm extends \OxidEsales\Eshop\Core\SeoEncoder
{
    /**
     * Returns SEO uri for tag.
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $oRecomm recommendation list object
     * @param int                                                    $iLang   language
     *
     * @return string
     */
    public function getRecommUri($oRecomm, $iLang = null)
    {
        if (!($sSeoUrl = $this->_loadFromDb('dynamic', $oRecomm->getId(), $iLang))) {
            $myConfig = $this->getConfig();

            // fetching part of base url
            $sSeoUrl = $this->_getStaticUri(
                $oRecomm->getBaseStdLink($iLang, false),
                $myConfig->getShopId(),
                $iLang
            )
            . $this->_prepareTitle($oRecomm->oxrecommlists__oxtitle->value, false, $iLang);

            // creating unique
            $sSeoUrl = $this->_processSeoUrl($sSeoUrl, $oRecomm->getId(), $iLang);

            // inserting
            $this->_saveToDb('dynamic', $oRecomm->getId(), $oRecomm->getBaseStdLink($iLang), $sSeoUrl, $iLang, $myConfig->getShopId());
        }

        return $sSeoUrl;
    }

    /**
     * Returns full url for passed tag
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $oRecomm recommendation list object
     * @param int                                                    $iLang   language
     *
     * @return string
     */
    public function getRecommUrl($oRecomm, $iLang = null)
    {
        if (!isset($iLang)) {
            $iLang = \OxidEsales\Eshop\Core\Registry::getLang()->getBaseLanguage();
        }

        return $this->_getFullUrl($this->getRecommUri($oRecomm, $iLang), $iLang);
    }

    /**
     * Returns tag SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $recomm     Recommendation list object.
     * @param int                                                    $pageNumber Number of the page which should be prepared.
     * @param int                                                    $languageId Language id.
     * @param bool                                                   $isFixed    Fixed url marker (default is null).
     *
     * @return string
     */
    public function getRecommPageUrl($recomm, $pageNumber, $languageId = null, $isFixed = false)
    {
        if (!isset($languageId)) {
            $languageId = \OxidEsales\Eshop\Core\Registry::getLang()->getBaseLanguage();
        }
        $stdUrl = $recomm->getBaseStdLink($languageId);
        $parameters = null;

        $stdUrl = $this->_trimUrl($stdUrl, $languageId);
        $seoUrl = $this->getRecommUri($recomm, $languageId);

        return $this->assembleFullPageUrl($recomm, 'dynamic', $stdUrl, $seoUrl, $pageNumber, $parameters, $languageId, $isFixed);
    }
}
