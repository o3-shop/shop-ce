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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentWithOrphanSettingEvent;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ContextStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\PathUtil\Path;

class ShopEnvironmentMisconfigurationEventSubscriberTest extends TestCase
{
    use ContainerTrait;

    private $testLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareLogger();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestLog();
        parent::tearDown();
    }

    public function testLogIsCreatedOnEventDispatch(): void
    {
        $this->get(EventDispatcherInterface::class)
            ->dispatch(
                ShopEnvironmentWithOrphanSettingEvent::NAME,
                new ShopEnvironmentWithOrphanSettingEvent(
                    123,
                    'some-module',
                    'some-setting'
                )
            );

        $this->assertFileExists($this->testLog);
    }

    private function prepareLogger(): void
    {
        /** @var ContextStub $context */
        $context = $this->get(ContextInterface::class);
        $logDirectory = Path::getDirectory($context->getLogFilePath());
        $testLogFile = uniqid('test.log.', true);
        $this->testLog = Path::join($logDirectory, $testLogFile);
        $context->setLogFilePath($this->testLog);
        $context->setLogLevel(LogLevel::WARNING);
    }

    private function cleanupTestLog(): void
    {
        if (\file_exists($this->testLog)) {
            \unlink($this->testLog);
        }
    }
}
