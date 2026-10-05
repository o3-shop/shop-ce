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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\SmartWordwrapLogic;
use PHPUnit\Framework\TestCase;

class SmartWordwrapLogicTest extends TestCase
{
    public function testReturnsStringUnchangedWhenShorterThanLength(): void
    {
        $logic = new SmartWordwrapLogic();
        $this->assertSame('short', $logic->wrapWords('short', 20, "\n", 0, 0, '...'));
    }

    public function testWrapsAtWordBoundariesWhenWithinTolerance(): void
    {
        $logic = new SmartWordwrapLogic();
        $result = $logic->wrapWords(
            'one two three four five six seven',
            10,
            "\n",
            0,
            0,
            '...'
        );
        // Should split into multiple rows on whitespace.
        $this->assertGreaterThan(1, count(explode("\n", $result)));
    }

    public function testHardWrapsWhenSingleWordExceedsLengthAndTolerance(): void
    {
        $logic = new SmartWordwrapLogic();
        // The single token exceeds length+tolerance and contains no '-' for soft-wrap.
        $result = $logic->wrapWords(str_repeat('x', 30), 5, '|', 0, 0, '...');
        $rows = explode('|', $result);
        // Each row must be no wider than the limit.
        foreach ($rows as $row) {
            $this->assertLessThanOrEqual(5, strlen($row));
        }
    }

    public function testSoftWrapPrefersHyphensInsideLongTokens(): void
    {
        $logic = new SmartWordwrapLogic();
        // A token containing a hyphen → wrapper should split at the hyphen
        // before falling back to a hard cut.
        $result = $logic->wrapWords('aaaaa-bbbbb', 6, '|', 0, 1, '...');
        $this->assertStringContainsString('aaaaa-', $result);
    }

    public function testCutRowsTruncatesAndAppendsSuffix(): void
    {
        $logic = new SmartWordwrapLogic();
        // 4 words at length 5 → 4 rows; cutRows=2 → only 2 rows kept.
        $result = $logic->wrapWords('one two three four', 5, '|', 2, 0, '...');
        $this->assertStringEndsWith('...', $result);
        $this->assertSame(2, count(explode('|', $result)));
    }

    public function testCutRowsKeepsLineWithinLengthBudget(): void
    {
        $logic = new SmartWordwrapLogic();
        $etc = '...';
        $length = 10;
        $result = $logic->wrapWords(
            'aaaaa bbbbb ccccc ddddd eeeee fffff',
            $length,
            '|',
            1,
            0,
            $etc
        );
        $rows = explode('|', $result);
        $this->assertCount(1, $rows);
        // Final row including suffix must respect length+tolerance budget.
        $this->assertLessThanOrEqual($length + strlen($etc), strlen($rows[0]));
    }
}
