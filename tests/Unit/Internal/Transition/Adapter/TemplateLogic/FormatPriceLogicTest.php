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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\FormatPriceLogic;
use PHPUnit\Framework\TestCase;

class FormatPriceLogicTest extends TestCase
{

    /**
     * @var FormatPriceLogic
     */
    private $formatPriceLogic;

    protected function setUp(): void
    {
        $this->formatPriceLogic = new FormatPriceLogic();
        parent::setUp();
    }

    /**
     * @param array  $params
     * @param string $expected
     *
     * @dataProvider getFormatPriceProvider
     */
    public function testFormatPrice(array $params, string $expected): void
    {
        $price = $this->formatPriceLogic->formatPrice($params);
        $this->assertEquals($expected, $price);
    }

    public function getFormatPriceProvider(): array
    {
        return [
            [
                ['price' => 100],
                '100,00 €'
            ],
            [
                ['price' => null],
                ''
            ]
        ];
    }

    /**
     * @param mixed  $inputPrice
     * @param string $expected
     *
     * @dataProvider getCalculatePriceProvider
     */
    public function testCalculatePrice($inputPrice, string $expected): void
    {
        $params['price'] = $inputPrice;
        $calculatedOxPrice = $this->formatPriceLogic->formatPrice($params);
        $this->assertEquals($expected, $calculatedOxPrice);
    }

    /**
     * @return array
     */
    public function getCalculatePriceProvider(): array
    {
        $incorrectPriceObj = new \OxidEsales\Eshop\Core\Price();
        $incorrectPriceObj->setPrice(false);
        $correctPriceObj = new \OxidEsales\Eshop\Core\Price();
        $correctPriceObj->setPrice(120);

        return [
            [
                1, '1,00 €'
            ],
            [
                'incorrect', '0,00 €'
            ],
            [
                $incorrectPriceObj, '0,00 €'
            ],
            [
                $incorrectPriceObj, '0,00 €'
            ],
            [
                $correctPriceObj, '120,00 €'
            ]
        ];
    }

    /**
     * @param mixed  $currency
     * @param int    $price
     * @param string $expected
     *
     * @dataProvider getFormattedPriceProvider
     */
    public function testGetFormattedPrice($currency, int $price, string $expected): void
    {
        $params['currency'] = $currency;
        $params['price'] = $price;
        $formattedPrice = $this->formatPriceLogic->formatPrice($params);
        $this->assertEquals($expected, $formattedPrice);
    }

    /**
     * @return array
     */
    public function getFormattedPriceProvider(): array
    {
        $price = 10000;

        return [
            [
                '', $price, '10.000,00'
            ],
            [
                '', -100, ''
            ],
            [
                $this->getCurrencyWithSeparator(['dec' => '-']), $price, '10.000-00'
            ],
            [
                $this->getCurrencyWithSeparator(['thousand' => '-']), $price, '10-000,00'
            ],
            [
                $this->getCurrencyWithSeparator(['sign' => '$']), $price, '10.000,00 $'
            ],
            [
                $this->getCurrencyWithSeparator(['decimal' => 4]), $price, '10.000,0000'
            ],
            [
                $this->getCurrencyWithSeparator(['sign' => '$', 'side' => 'Front']), $price, '$10.000,00'
            ],
            [
                $this->getCurrencyWithSeparator(['sign' => '$', 'side' => 'incorrect']), $price, '10.000,00 $'
            ]
        ];
    }

    /**
     * @param array $currency_array
     *
     * @return \stdClass
     */
    private function getCurrencyWithSeparator(array $currency_array): \stdClass
    {
        $currency = new \stdClass();
        foreach ($currency_array as $key => $value) {
            $currency->$key = $value;
        }

        return $currency;
    }

}
