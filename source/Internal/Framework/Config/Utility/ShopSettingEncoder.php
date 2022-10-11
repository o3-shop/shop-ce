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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Config\Utility;

use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Config\Exception\InvalidShopSettingValueException;

use function unserialize;
use function serialize;

class ShopSettingEncoder implements ShopSettingEncoderInterface
{
    /**
     * @param string $encodingType
     * @param mixed  $value
     * @return mixed
     */
    public function encode(string $encodingType, $value)
    {
        $this->validateSettingValue($value);

        switch ($encodingType) {
            case ShopSettingType::ARRAY:
            case ShopSettingType::ASSOCIATIVE_ARRAY:
                $encodedValue = serialize($value);
                break;
            case ShopSettingType::BOOLEAN:
                $encodedValue = $value === true ? '1' : '';
                break;
            default:
                $encodedValue = $value;
        }

        return $encodedValue;
    }

    /**
     * @param string $encodingType
     * @param mixed  $value
     * @return mixed
     */
    public function decode(string $encodingType, $value)
    {
        switch ($encodingType) {
            case ShopSettingType::ARRAY:
            case ShopSettingType::ASSOCIATIVE_ARRAY:
                $decodedValue = unserialize($value, ['allowed_classes' => false]);
                break;
            case ShopSettingType::BOOLEAN:
                $decodedValue = ($value === 'true' || $value === '1');
                break;
            default:
                $decodedValue = $value;
        }

        return $decodedValue;
    }

    /**
     * @param mixed $value
     * @throws InvalidShopSettingValueException
     */
    private function validateSettingValue($value)
    {
        if (is_object($value)) {
            throw new InvalidShopSettingValueException(
                'Shop setting value must not be an object.'
            );
        }
    }
}
