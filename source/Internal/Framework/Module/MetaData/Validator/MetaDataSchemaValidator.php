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

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\UnsupportedMetaDataKeyException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\UnsupportedMetaDataValueTypeException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataSchemataProviderInterface;

class MetaDataSchemaValidator implements MetaDataSchemaValidatorInterface
{
    /**
     * @var MetaDataSchemataProviderInterface
     */
    private $metaDataSchemataProvider;

    /**
     * @var array
     */
    private static $sectionsExcludedFromItemValidation = [
        MetaDataProvider::METADATA_EXTEND,
        MetaDataProvider::METADATA_CONTROLLERS,
        MetaDataProvider::METADATA_TEMPLATES,
        MetaDataProvider::METADATA_EVENTS,
        MetaDataProvider::METADATA_SMARTY_PLUGIN_DIRECTORIES,
    ];

    /**
     * @var string
     */
    private $currentValidationMetaDataVersion;

    /**
     * @var string
     */
    private $metaDataFilePath;

    /**
     * MetaDataValidator constructor.
     *
     * @param MetaDataSchemataProviderInterface $metaDataSchemataProvider
     */
    public function __construct(MetaDataSchemataProviderInterface $metaDataSchemataProvider)
    {
        $this->metaDataSchemataProvider = $metaDataSchemataProvider;
    }

    /**
     * Validate that a given metadata meets the specifications of a given metadata version
     *
     * @param string $metaDataFilePath
     * @param string $metaDataVersion
     * @param array  $metaData
     *
     * @throws UnsupportedMetaDataValueTypeException
     * @throws UnsupportedMetaDataKeyException
     */
    public function validate(string $metaDataFilePath, string $metaDataVersion, array $metaData)
    {
        $this->currentValidationMetaDataVersion = $metaDataVersion;
        $this->metaDataFilePath = $metaDataFilePath;

        $supportedMetaDataKeys = $this->metaDataSchemataProvider->getFlippedMetaDataSchemaForVersion(
            $this->currentValidationMetaDataVersion
        );
        foreach ($metaData as $metaDataKey => $value) {
            if (is_scalar($value)) {
                $this->validateMetaDataKey($supportedMetaDataKeys, (string) $metaDataKey);
            } elseif (true === \is_array($value)) {
                $this->validateMetaDataSection($supportedMetaDataKeys, $metaDataKey, $value);
            } else {
                throw new UnsupportedMetaDataValueTypeException(
                    'The value type "' . \gettype($value) .
                    '" is not supported in metadata version ' . $this->currentValidationMetaDataVersion
                );
            }
        }
    }

    /**
     * @param array $supportedMetaDataKeys
     * @param string $metaDataKey
     *
     * @throws UnsupportedMetaDataKeyException
     */
    private function validateMetaDataKey(array $supportedMetaDataKeys, string $metaDataKey): void
    {
        if (false === array_key_exists($metaDataKey, $supportedMetaDataKeys)) {
            throw new UnsupportedMetaDataKeyException(
                'The metadata key "' . $metaDataKey . '" is not supported in metadata version "'
                . $this->currentValidationMetaDataVersion . '".'
            );
        }
    }

    /**
     * Validate well defined section items
     *
     * @param array  $supportedMetaDataKeys
     * @param string $sectionName
     * @param array  $sectionData
     *
     * @throws UnsupportedMetaDataKeyException
     */
    private function validateMetaDataSectionItems(array $supportedMetaDataKeys, string $sectionName, array $sectionData)
    {
        foreach ($sectionData as $sectionItem) {
            if (\is_array($sectionItem)) {
                $metaDataKeys = array_keys($sectionItem);
                foreach ($metaDataKeys as $metaDataKey) {
                    $this->validateMetaDataKey($supportedMetaDataKeys[$sectionName], $metaDataKey);
                }
            }
        }
    }

    /**
     * Validate a section of metadata like 'blocks' or 'settings', which are multidimensional arrays of well
     * defined items. There are sections (e.g. extend or templates, ), which are arrays or multidimensional arrays
     * of not well defined items. In these cases the items cannot be validated.
     *
     * @param array  $supportedMetaDataKeys
     * @param string $sectionName
     * @param array  $sectionData
     * @throws UnsupportedMetaDataKeyException
     */
    private function validateMetaDataSection(
        array $supportedMetaDataKeys,
        string $sectionName,
        array $sectionData
    ): void {
        $this->validateMetaDataKey($supportedMetaDataKeys, $sectionName);
        if (\in_array($sectionName, static::$sectionsExcludedFromItemValidation, true)) {
            return;
        }
        $this->validateMetaDataSectionItems($supportedMetaDataKeys, $sectionName, $sectionData);
    }
}
