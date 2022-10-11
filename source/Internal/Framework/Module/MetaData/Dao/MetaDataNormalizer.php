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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao;

use function is_string;

class MetaDataNormalizer implements MetaDataNormalizerInterface
{
    /**
     * Normalize the array aModule in metadata.php
     *
     * @param array $data
     *
     * @return array
     */
    public function normalizeData(array $data): array
    {
        $normalizedMetaData = $data;
        foreach ($data as $key => $value) {
            $normalizedValue = $this->lowerCaseFileClassesNames($key, $value);
            $normalizedMetaData[$key] = $normalizedValue;
        }

        if (isset($normalizedMetaData[MetaDataProvider::METADATA_SETTINGS])) {
            $normalizedMetaData[MetaDataProvider::METADATA_SETTINGS] = $this->convertModuleSettingConstraintsToArray(
                $normalizedMetaData[MetaDataProvider::METADATA_SETTINGS]
            );
        }

        if (isset($normalizedMetaData[MetaDataProvider::METADATA_TITLE])) {
            $normalizedMetaData = $this->normalizeMultiLanguageField(
                $normalizedMetaData,
                MetaDataProvider::METADATA_TITLE
            );
        }

        if (isset($normalizedMetaData[MetaDataProvider::METADATA_DESCRIPTION])) {
            $normalizedMetaData = $this->normalizeMultiLanguageField(
                $normalizedMetaData,
                MetaDataProvider::METADATA_DESCRIPTION
            );
        }

        return $normalizedMetaData;
    }

    /**
     * @param array $metadataModuleSettings
     * @return array
     */
    private function convertModuleSettingConstraintsToArray(array $metadataModuleSettings): array
    {
        foreach ($metadataModuleSettings as $key => $setting) {
            if (isset($setting['constraints'])) {
                $metadataModuleSettings[$key]['constraints'] = explode('|', $setting['constraints']);
            }
        }

        return $metadataModuleSettings;
    }

    /**
     * @param array  $normalizedMetaData
     * @param string $fieldName
     * @return array
     */
    private function normalizeMultiLanguageField(array $normalizedMetaData, string $fieldName): array
    {
        $title = $normalizedMetaData[$fieldName];

        if (is_string($title)) {
            $defaultLanguage = $normalizedMetaData[MetaDataProvider::METADATA_LANG] ?? 'en';
            $normalizedTitle = [
                $defaultLanguage => $title,
            ];
            $normalizedMetaData[$fieldName] = $normalizedTitle;
        }

        return $normalizedMetaData;
    }

    /**
     * @deprecated 6.6 Will be removed completely
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    private function lowerCaseFileClassesNames($key, $value)
    {
        $normalizedValue = $value;
        if (is_array($value) && $key === MetaDataProvider::METADATA_FILES) {
            $normalizedValue = [];
            foreach ($value as $className => $path) {
                $normalizedValue[strtolower($className)] = $path;
            }
        }

        return $normalizedValue;
    }
}
