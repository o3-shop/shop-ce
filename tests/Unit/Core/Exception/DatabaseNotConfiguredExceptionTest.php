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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Exception;

use OxidEsales\EshopCommunity\Core\Exception\DatabaseNotConfiguredException;
use PHPUnit\Framework\TestCase;

class DatabaseNotConfiguredExceptionTest extends TestCase
{
    public function testConstructorWithExplicitPrevious(): void
    {
        $previous = new \Exception('cause');
        $exception = new DatabaseNotConfiguredException('not configured', 555, $previous);

        $this->assertSame('not configured', $exception->getMessage());
        $this->assertSame(555, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorSyntheticPreviousWhenOmitted(): void
    {
        // The constructor synthesises a placeholder previous exception
        // so the parent (DatabaseException) chain is never null.
        $exception = new DatabaseNotConfiguredException('not configured', 555);

        $this->assertSame('not configured', $exception->getMessage());
        $this->assertNotNull($exception->getPrevious());
        $this->assertInstanceOf(\Exception::class, $exception->getPrevious());
    }
}
