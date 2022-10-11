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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Contact\Form\ContactFormBridgeInterface;

class ContactFormBridgeTest extends \PHPUnit\Framework\TestCase
{
    public function testFormGetter()
    {
        $container = $this->getContainer();
        $bridge = $container->get(ContactFormBridgeInterface::class);

        $this->assertInstanceOf(
            FormInterface::class,
            $bridge->getContactForm()
        );
    }

    public function testFormConfigurationGetter()
    {
        $container = $this->getContainer();
        $bridge = $container->get(ContactFormBridgeInterface::class);

        $this->assertInstanceOf(
            FormConfigurationInterface::class,
            $bridge->getContactFormConfiguration()
        );
    }

    public function testFormMessageGetter()
    {
        $container = $this->getContainer();
        $bridge = $container->get(ContactFormBridgeInterface::class);

        $form = $bridge->getContactForm();
        $form->handleRequest(['email' => 'marina.ginesta@bcn.cat']);

        $message = $bridge->getContactFormMessage($form);

        $this->assertStringContainsString(
            'marina.ginesta@bcn.cat',
            $message
        );
    }

    private function getContainer()
    {
        $factory = ContainerFactory::getInstance();

        return $factory->getContainer();
    }
}
