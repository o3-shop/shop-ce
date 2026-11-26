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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IncludeExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\IncludeDynamicNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Test\NodeTestCase;

/**
 * Class IncludeDynamicNodeTest
 */
class IncludeDynamicNodeTest extends NodeTestCase
{
    public function testConstructor(): void
    {
        $expr = new ConstantExpression('foo.twig', 1);
        $node = new IncludeDynamicNode($expr, null, false, false, 1);

        $this->assertFalse($node->hasNode('variables'));
        $this->assertEquals($expr, $node->getNode('expr'));
        $this->assertFalse($node->getAttribute('only'));

        $vars = new ArrayExpression([new ConstantExpression('foo', 1), new ConstantExpression(true, 1)], 1);
        $node = new IncludeDynamicNode($expr, $vars, true, false, 1);
        $this->assertEquals($vars, $node->getNode('variables'));
        $this->assertTrue($node->getAttribute('only'));
    }

    public function getTests(): array
    {
        $includeExtensionClass = IncludeExtension::class;

        $tests = [];

        $expr = new ConstantExpression('foo.twig', 1);
        $node = new IncludeDynamicNode($expr, null, false, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
if (!empty(\$context["_render4cache"])) {
    yield \$this->extensions['$includeExtensionClass']->renderForCache([]);
} else {
    yield from \$this->loadTemplate("foo.twig", null, 1)->unwrap()->yield(\$context);
}
EOF
        ];

        $expr = new ConditionalExpression(
            new ConstantExpression(true, 1),
            new ConstantExpression('foo', 1),
            new ConstantExpression('foo', 1),
            0
        );
        $node = new IncludeDynamicNode($expr, null, false, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
if (!empty(\$context["_render4cache"])) {
    yield \$this->extensions['$includeExtensionClass']->renderForCache([]);
} else {
    yield from \$this->loadTemplate(((true) ? ("foo") : ("foo")), null, 1)->unwrap()->yield(\$context);
}
EOF
        ];

        $expr = new ConstantExpression('foo.twig', 1);
        $vars = new ArrayExpression([new ConstantExpression('foo', 1), new ConstantExpression(true, 1)], 1);
        $node = new IncludeDynamicNode($expr, $vars, false, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$parameters = ["foo" => true];
if (!empty(\$context["_render4cache"])) {
    yield \$this->extensions['$includeExtensionClass']->renderForCache(\$parameters);
} else {
    \$parameters = \$this->extensions['$includeExtensionClass']->includeDynamicPrefix(\$parameters);
    yield from \$this->loadTemplate("foo.twig", null, 1)->unwrap()->yield(array_merge(\$context, \$parameters));
}
EOF
        ];

        $node = new IncludeDynamicNode($expr, $vars, true, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$parameters = ["foo" => true];
if (!empty(\$context["_render4cache"])) {
    yield \$this->extensions['$includeExtensionClass']->renderForCache(\$parameters);
} else {
    \$parameters = \$this->extensions['$includeExtensionClass']->includeDynamicPrefix(\$parameters);
    yield from \$this->loadTemplate("foo.twig", null, 1)->unwrap()->yield(\$parameters);
}
EOF
        ];

        $node = new IncludeDynamicNode($expr, $vars, true, true, 1);
        $tests[] = [$node, <<<EOF
// line 1
try {
    \$parameters = ["foo" => true];
    if (!empty(\$context["_render4cache"])) {
        yield \$this->extensions['$includeExtensionClass']->renderForCache(\$parameters);
    } else {
        \$parameters = \$this->extensions['$includeExtensionClass']->includeDynamicPrefix(\$parameters);
        yield from \$this->loadTemplate("foo.twig", null, 1)->unwrap()->yield(\$parameters);
    }
} catch (\Twig\Error\LoaderError \$e) {
    // ignore missing template
}
EOF
        ];

        return $tests;
    }
}
