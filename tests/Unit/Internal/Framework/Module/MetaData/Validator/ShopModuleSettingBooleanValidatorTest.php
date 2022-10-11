<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData\Validator;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\SettingNotValidException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\ModuleSettingBooleanValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\ModuleSettingBooleanValidator
 */
class SettingBooleanValidatorTest extends TestCase
{
    public function validationPassWithDataProvider(): array
    {
        return [
            ['true'],
            ['TRUE'],
            ['false'],
            [1],
            ['1'],
            ['0'],
            [0],
        ];
    }

    /**
     * @dataProvider validationPassWithDataProvider
     *
     * @param $value
     *
     * @deprecated   since v6.4.0 (2019-06-10);This is not recommended values for use,
     *               only boolean values should be used.
     */
    public function testValidationPassWithBackwardsCompatibleValues($value)
    {
        $this->executeValidationForBoolSetting($value);
    }

    public function validationPassDataProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @param bool $value
     * @dataProvider validationPassDataProvider
     */
    public function testValidationPass(bool $value)
    {
        $this->executeValidationForBoolSetting($value);
    }

    public function validationFailsDataProvider()
    {
        return [
            ['any random value'],
            [''],
            [11],
        ];
    }

    /**
     * @param mixed $value
     * @dataProvider validationFailsDataProvider
     */
    public function testValidationFails($value)
    {
        $this->expectException(SettingNotValidException::class);
        $this->executeValidationForBoolSetting($value);
    }

    public function testWhenStringTypeProvided()
    {
        $settings =
            [
                MetaDataProvider::METADATA_ID => 'test_id',
                MetaDataProvider::METADATA_SETTINGS => [
                    [
                    'type' => 'str', 'value' => 'String value'
                    ],
                ]
            ];
        $validator = new ModuleSettingBooleanValidator();

        $validator->validate($settings);
    }

    public function testWhenNoTypeProvided()
    {
        $settings =
            [
                MetaDataProvider::METADATA_ID => 'test_id',
                MetaDataProvider::METADATA_SETTINGS => [
                    [
                        'value' => 'Any value'
                    ],
                ]
            ];
        $validator = new ModuleSettingBooleanValidator();

        $validator->validate($settings);
    }

    private function executeValidationForBoolSetting($value): void
    {
        $settings =
            [
                MetaDataProvider::METADATA_ID => 'test_id',
                MetaDataProvider::METADATA_SETTINGS => [
                    [
                        'type' => 'bool', 'value' => $value
                    ],
                ]
            ];

        $validator = new ModuleSettingBooleanValidator();

        $validator->validate($settings);
    }
}
