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

/**
 * Class oxBasketContentMarkGenerator which forms explanation marks.
 */
class BasketContentMarkGenerator
{
    /**
     * Default value for explanation mark.
     */
    const DEFAULT_EXPLANATION_MARK = '**';

    /**
     * Marks added to array by article type.
     *
     * @var array
     */
    private $_aMarks;

    /**
     * Basket that is used to get article type(downloadable, intangible etc..).
     *
     * @var \OxidEsales\Eshop\Application\Model\Basket
     */
    private $_oBasket;

    /**
     * Sets basket that is used to get article type(downloadable, intangible etc..).
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket
     */
    public function __construct(\OxidEsales\Eshop\Application\Model\Basket $oBasket)
    {
        $this->_oBasket = $oBasket;
    }

    /**
     * Returns explanation mark by given mark identification (skippedDiscount, downloadable, intangible).
     *
     * @param string $sMarkIdentification Mark identification.
     *
     * @return string
     */
    public function getMark($sMarkIdentification)
    {
        if (is_null($this->_aMarks)) {
            $sCurrentMark = self::DEFAULT_EXPLANATION_MARK;
            $aMarks = $this->_formMarks($sCurrentMark);
            $this->_aMarks = $aMarks;
        }

        return $this->_aMarks[$sMarkIdentification];
    }

    /**
     * Basket that is used to get article type(downloadable, intangible etc..).
     *
     * @return \OxidEsales\Eshop\Application\Model\Basket
     */
    private function _getBasket() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_oBasket;
    }

    /**
     * Forms marks for articles.
     *
     * @param string $sCurrentMark Current mark.
     *
     * @return array
     */
    private function _formMarks($sCurrentMark) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oBasket = $this->_getBasket();
        $aMarks = [];
        if ($oBasket->hasSkipedDiscount()) {
            $aMarks['skippedDiscount'] = $sCurrentMark;
            $sCurrentMark .= '*';
        }
        if ($oBasket->hasArticlesWithDownloadableAgreement()) {
            $aMarks['downloadable'] = $sCurrentMark;
            $sCurrentMark .= '*';
        }
        if ($oBasket->hasArticlesWithIntangibleAgreement()) {
            $aMarks['intangible'] = $sCurrentMark;
        }

        return $aMarks;
    }
}
