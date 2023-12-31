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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Validator;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapter;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator\EventsValidator;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\TestData\TestModule\ModuleEvents;
use PHPUnit\Framework\TestCase;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Event;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSettingNotValidException;

class EventsModuleSettingValidatorTest extends TestCase
{
    public function testValidate()
    {
        $validator = $this->createValidator();

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addEvent(new Event('onActivate', ModuleEvents::class . '::onActivate'));
        $moduleConfiguration->addEvent(new Event('onDeactivate', ModuleEvents::class . '::onDeactivate'));

        $validator->validate($moduleConfiguration, 1);
    }

    public function testValidateThrowsExceptionIfEventsDefinedAreNotCallable()
    {
        $validator = $this->createValidator();

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addEvent(new Event('onActivate', 'SomeNamespace\\class::noCallableMethod'));
        $moduleConfiguration->addEvent(new Event('onDeactivate', 'SomeNamespace\\class::noCallableMethod'));

        $this->expectException(ModuleSettingNotValidException::class);
        $validator->validate($moduleConfiguration, 1);
    }

    /**
     * This is needed only for the modules which has non namespaced classes.
     * This test MUST be removed when support for non namespaced modules will be dropped (metadata v1.*).
     */
    public function testDoNotValidateForNonNamespacedClasses()
    {
        $validator = $this->createValidator();

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addEvent(new Event('onActivate', 'class::noCallableMethod'));
        $moduleConfiguration->addEvent(new Event('onDeactivate', 'class::noCallableMethod'));

        $validator->validate($moduleConfiguration, 1);
    }

    /**
     * @dataProvider invalidEventsProvider
     *
     * @param Event $invalidEvent
     *
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSettingNotValidException
     */
    public function testValidateDoesNotValidateSyntax($invalidEvent)
    {
        $validator = $this->createValidator();

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addEvent($invalidEvent);

        $validator->validate($moduleConfiguration, 1);
    }

    public function invalidEventsProvider(): array
    {
        return [
            [new Event('invalidEvent', 'noCallableMethod')],
            [new Event('', '')]
        ];
    }

    /**
     * @return EventsValidator
     */
    private function createValidator(): EventsValidator
    {
        return new EventsValidator(new ShopAdapter());
    }
}
