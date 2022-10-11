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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use \oxBasketContentMarkGenerator;

class BasketcontentmarkgeneratorTest extends \OxidTestCase
{
    public function providerGetExplanationMarks()
    {
        $aResultDownloadable = array(
            'skippedDiscount' => null,
            'downloadable'    => '**',
            'intangible'      => null
        );

        $aResultIntangible = array(
            'skippedDiscount' => null,
            'downloadable'    => null,
            'intangible'      => '**',
        );

        $aResultSkippedDiscount = array(
            'skippedDiscount' => '**',
            'downloadable'    => null,
            'intangible'      => null,
        );

        $aResultDownloadableAndIntangible = array(
            'skippedDiscount' => null,
            'downloadable'    => '**',
            'intangible'      => '***'
        );

        $aResultDownloadableIntangibleAndSkippedDiscount = array(
            'skippedDiscount' => '**',
            'downloadable'    => '***',
            'intangible'      => '****'
        );

        $ResultEmptyArray = array(
            'skippedDiscount'   => null,
            'downloadable'      => null,
            'intangible'        => null,
            'thisDoesNotExists' => null
        );

        return array(
            array(false, true, false, $aResultDownloadable),
            array(true, false, false, $aResultIntangible),
            array(false, false, true, $aResultSkippedDiscount),
            array(true, true, false, $aResultDownloadableAndIntangible),
            array(true, true, true, $aResultDownloadableIntangibleAndSkippedDiscount),
            array(false, false, false, $ResultEmptyArray),
        );
    }

    /**
     * @param $blIsIntangible
     * @param $blIsDownloadable
     * @param $blHasSkippedDiscounts
     * @param $aResult
     *
     * @dataProvider providerGetExplanationMarks
     */
    public function testGetExplanationMarks($blIsIntangible, $blIsDownloadable, $blHasSkippedDiscounts, $aResult)
    {
        /** @var oxBasket $oBasket */
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, array('hasArticlesWithIntangibleAgreement', 'hasArticlesWithDownloadableAgreement', 'hasSkipedDiscount'));
        $oBasket->expects($this->any())->method('hasArticlesWithIntangibleAgreement')->will($this->returnValue($blIsIntangible));
        $oBasket->expects($this->any())->method('hasArticlesWithDownloadableAgreement')->will($this->returnValue($blIsDownloadable));
        $oBasket->expects($this->any())->method('hasSkipedDiscount')->will($this->returnValue($blHasSkippedDiscounts));

        $oExplanationMarks = new oxBasketContentMarkGenerator($oBasket);

        foreach ($aResult as $sMarkName => $sMark) {
            $this->assertSame($sMark, $oExplanationMarks->getMark($sMarkName));
        }
    }
}
