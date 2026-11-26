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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigEngine;
use PHPUnit\Framework\TestCase;

/**
 * Renders templates through the compiled container's Twig engine, so every adopted extension, node and token
 * parser is exercised on Twig 3 (generator based templates) and not only compiled in isolation.
 */
class TwigExtensionsRenderTest extends TestCase
{
    /**
     * @dataProvider renderProvider
     */
    public function testAdoptedExtensionsRenderOnTwig3(string $template, string $expected): void
    {
        $this->assertSame($expected, $this->getEngine()->renderFragment($template, 'ox:' . md5($template)));
    }

    public function testDeprecatedIssetFunctionStillWorks(): void
    {
        \set_error_handler(static function (): bool {
            return true;
        }, E_USER_DEPRECATED);

        try {
            $this->assertSame('yn', $this->getEngine()->renderFragment("{{ isset('a') ? 'y' : 'n' }}{{ isset() ? 'y' : 'n' }}", 'ox:isset'));
        } finally {
            \restore_error_handler();
        }
    }

    public function renderProvider(): array
    {
        return [
            'capture assign keeps the rendered body' => [
                '{% capture assign = "x" %}A{{ 1 + 1 }}{% endcapture %}[{{ x }}]',
                '[A2]',
            ],
            'capture name stores into the twig capture global' => [
                '{% capture name = "x" %}B{% endcapture %}[{{ twig.capture.x }}]',
                '[B]',
            ],
            'capture append collects every capture' => [
                '{% capture append = "l" %}1{% endcapture %}{% capture append = "l" %}2{% endcapture %}{{ l|join(",") }}',
                '1,2',
            ],
            'captured body does not leak into the output' => [
                'a{% capture assign = "x" %}hidden{% endcapture %}b',
                'ab',
            ],
            'hasrights renders its body' => [
                '{% hasrights {"id": "1"} %}in{% endhasrights %}',
                'in',
            ],
            'math function' => ['{{ sqrt(16) }}', '4'],
            'smarty_cycle alternates' => [
                "{{ smarty_cycle(['a', 'b']) }}{{ smarty_cycle(['a', 'b']) }}{{ smarty_cycle(['a', 'b']) }}",
                'aba',
            ],
            'mailto returns markup that is not escaped' => [
                '{{ mailto("a@example.com") }}',
                '<a href="mailto:a@example.com" >a@example.com</a>',
            ],
            'string loader extension' => [
                "{{ include(template_from_string('X{{ 1 + 2 }}')) }}",
                'X3',
            ],
            'include_dynamic ignores a missing template' => [
                "a{% include_dynamic 'does/not/exist.html.twig' ignore missing %}b",
                'ab',
            ],
            'insert_tracker renders nothing' => ['{{ insert_tracker() }}', ''],
            'text filters stay escaped' => [
                "{{ '<b>x</b>'|truncate(80) }}",
                '&lt;b&gt;x&lt;/b&gt;',
            ],
        ];
    }

    private function getEngine(): TwigEngine
    {
        return ContainerFactory::getInstance()->getContainer()->get('oxid_esales.templating.twig_engine');
    }
}
