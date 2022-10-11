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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\SettingNotValidException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;

class ModuleSettingBooleanValidator implements MetaDataValidatorInterface
{
    private const ALLOWED_VALUES = [
        0,
        1,
        '0',
        '1',
        'true',
        'false',
        true,
        false,
    ];

    /**
     * @param array $metaData
     *
     * @throws SettingNotValidException
     */
    public function validate(array $metaData): void
    {
        if (isset($metaData[MetaDataProvider::METADATA_SETTINGS])) {
            $settings = $metaData[MetaDataProvider::METADATA_SETTINGS];
            foreach ($settings as $setting) {
                $this->validateSetting($metaData, $setting);
            }
        }
    }

    /**
     * @param array $metaData
     * @param array $setting
     * @throws SettingNotValidException
     */
    private function validateSetting(array $metaData, array $setting): void
    {
        if (isset($setting['type']) && $setting['type'] === 'bool') {
            $value = is_string($setting['value']) ? strtolower($setting['value']) : $setting['value'];
            if (!in_array($value, self::ALLOWED_VALUES, true)) {
                throw new SettingNotValidException(
                    'Invalid boolean value- "' . $setting['value'] . '" was used for module setting. '
                    . 'Please update setting value in module "' . $metaData[MetaDataProvider::METADATA_ID] . '".'
                );
            }
        }
    }
}
