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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\DataMapper;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfiguration\TemplatesDataMapper;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use PHPUnit\Framework\TestCase;

final class TemplatesDataMapperTest extends TestCase
{
    public function testMappingWithThemedTemplates(): void
    {
        $initialData = [
            'templates' => [
                '01.tpl' => '01,ext.tpl',
                'some-theme' => [
                    '01.tpl' => '01.theme.ext.tpl',
                    '02.tpl' => '02.theme.ext.tpl',
                ],
                '02.tpl' => '02.ext.tpl',
            ],
        ];
        $templatesDataMapper = new TemplatesDataMapper();

        $moduleConfigurationObject = $templatesDataMapper->fromData(new ModuleConfiguration(), $initialData);
        $moduleConfigurationArray = $templatesDataMapper->toData($moduleConfigurationObject);

        $this->assertSame($initialData, $moduleConfigurationArray);
    }
}
