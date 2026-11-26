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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Node;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IfContentExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\IfContentNode;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

/**
 * Class IfContentNodeTest
 */
class IfContentNodeTest extends NodeTestCase
{
    /**
     * Test constructor
     */
    public function testConstructor(): void
    {
        $body = new TextNode('Lorem Ipsum', 1);
        $variable = new AssignNameExpression('foo', 1);
        $node = new IfContentNode($body, [], $variable, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($variable, $node->getNode('variable'));

        $expr = new ConstantExpression('oxsomething', 1);

        $node = new IfContentNode($body, ['ident' => $expr], $variable, 1);
        $this->assertEquals($expr, $node->getNode('ident'));
        $this->assertFalse($node->hasNode('oxid'));

        $node = new IfContentNode($body, ['oxid' => $expr], $variable, 1);
        $this->assertEquals($expr, $node->getNode('oxid'));
        $this->assertFalse($node->hasNode('ident'));
    }

    /**
     * @return array
     */
    public function getTests(): array
    {
        $ifContentExtensionClass = IfContentExtension::class;

        $tests = [];

        $body = new TextNode('Lorem Ipsum', 1);
        $variable = new AssignNameExpression('foo', 1);
        $expr = new ConstantExpression('oxsomething', 1);
        $node = new IfContentNode($body, ['ident' => $expr], $variable, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = \$this->extensions['$ifContentExtensionClass']->getContent("oxsomething", null);
if (\$context["foo"]) {
    \$ifContentBody = \Twig\Extension\CoreExtension::captureOutput((function () use (&\$context, \$macros, \$blocks) {
        yield "Lorem Ipsum";
        return; yield '';
    })());
    yield \$this->extensions['$ifContentExtensionClass']->renderContent(\$ifContentBody, "oxsomething");
}
unset(\$context["foo"]);
EOF
        ];

        $node = new IfContentNode($body, ['oxid' => $expr], $variable, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = \$this->extensions['$ifContentExtensionClass']->getContent(null, "oxsomething");
if (\$context["foo"]) {
    \$ifContentBody = \Twig\Extension\CoreExtension::captureOutput((function () use (&\$context, \$macros, \$blocks) {
        yield "Lorem Ipsum";
        return; yield '';
    })());
    yield \$this->extensions['$ifContentExtensionClass']->renderContent(\$ifContentBody, null);
}
unset(\$context["foo"]);
EOF
        ];

        return $tests;
    }
}
