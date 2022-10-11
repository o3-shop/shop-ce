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

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\ModuleIdNotValidException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\ModuleIdValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\ModuleIdValidator
 */
class ModuleIdValidatorTest extends TestCase
{

    public function testValidateWhenValid(): void
    {
        $metaData = [
            MetaDataProvider::METADATA_ID => 'some_id'
        ];

        $validator = new ModuleIdValidator();
        $validator->validate($metaData);
    }

    public function validateInvalidIdProvidedDataProvider(): array
    {
        return [
            [''],
            [null],
        ];
    }

    /**
     * @param mixed $moduleId
     * @dataProvider validateInvalidIdProvidedDataProvider
     */
    public function testValidateWhenInvalidIdProvided($moduleId): void
    {
        $this->expectException(ModuleIdNotValidException::class);
        $metaData = [
            MetaDataProvider::METADATA_ID => $moduleId
        ];

        $validator = new ModuleIdValidator();
        $validator->validate($metaData);
    }

    public function testValidateWhenIdNotProvided(): void
    {
        $this->expectException(ModuleIdNotValidException::class);
        $metaData = [];

        $validator = new ModuleIdValidator();
        $validator->validate($metaData);
    }
}
