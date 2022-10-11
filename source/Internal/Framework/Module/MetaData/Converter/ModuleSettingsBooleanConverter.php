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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Converter;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;

class ModuleSettingsBooleanConverter implements MetaDataConverterInterface
{
    private const CONVERSION_MAP = [
        'true' => true,
        '1' => true,
        'false' => false,
        '0' => false,
    ];

    public function convert(array $metaData): array
    {
        $convertedMetaData = $metaData;
        if (isset($metaData[MetaDataProvider::METADATA_SETTINGS])) {
            $settings = $metaData[MetaDataProvider::METADATA_SETTINGS];
            foreach ($settings as $key => $setting) {
                $convertedMetaData[MetaDataProvider::METADATA_SETTINGS][$key] = $this->updateValue($setting);
            }
        }

        return $convertedMetaData;
    }

    /**
     * @param $setting
     * @return mixed
     */
    private function updateValue($setting)
    {
        if (isset($setting['type']) && $setting['type'] === 'bool') {
            $value = is_string($setting['value']) ? strtolower($setting['value']) : $setting['value'];
            $setting['value'] = self::CONVERSION_MAP[$value] ?? $setting['value'];
        }
        return $setting;
    }
}
