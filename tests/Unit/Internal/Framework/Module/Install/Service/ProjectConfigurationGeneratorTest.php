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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Install;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ProjectConfigurationGenerator;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
class ProjectConfigurationGeneratorTest extends TestCase
{
    private $shops = [1, 2, 3];

    public function testGenerateDefaultConfiguration()
    {
        $projectConfigurationDao = $this->getMockBuilder(ProjectConfigurationDaoInterface::class)->getMock();
        $projectConfigurationDao
            ->expects($this->once())
            ->method('save')
            ->with($this->getExpectedDefaultProjectConfiguration($this->shops));

        $context = $this->getContext();

        $generator = new ProjectConfigurationGenerator($projectConfigurationDao, $context);

        $generator->generate();
    }

    private function getExpectedDefaultProjectConfiguration(array $shops): ProjectConfiguration
    {
        $projectConfiguration = new ProjectConfiguration();

        foreach ($shops as $shopId) {
            $projectConfiguration->addShopConfiguration($shopId, new ShopConfiguration());
        }

        return $projectConfiguration;
    }

    /**
     * @return ContextInterface | MockObject
     */
    private function getContext(): MockObject
    {
        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context->method('getAllShopIds')->willReturn($this->shops);

        return $context;
    }
}
