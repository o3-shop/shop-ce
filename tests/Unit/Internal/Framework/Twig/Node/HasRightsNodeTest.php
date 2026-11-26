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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\HasRightsNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

/**
 * Class HasRightsNodeTest
 */
class HasRightsNodeTest extends NodeTestCase
{
    public function test__construct()
    {
        $parameters = [];
        $parameters[] = new ConstantExpression('name', 1);
        $parameters[] = new ConstantExpression('value', 1);

        $body = new TextNode('Lorem Ipsum', 1);
        $parametersNode = new ArrayExpression($parameters, 1);
        $node = new HasRightsNode($body, $parametersNode, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($parametersNode, $node->getNode('parameters'));
    }

    /**
     * @return array
     */
    public function getTests(): array
    {
        return array_merge($this->getNonNestedBlocksTests(), $this->getNestedBlocksTests());
    }

    /**
     * @return array
     */
    private function getNonNestedBlocksTests(): array
    {
        $tests = [];

        $parameters = [];
        $parameters[] = new ConstantExpression('name', 1);
        $parameters[] = new ConstantExpression('value', 1);

        $body = new TextNode('Lorem Ipsum', 1);
        $node = new HasRightsNode($body, new ArrayExpression($parameters, 1), 1);

        $tests[] = [$node, <<<EOF
// line 1
yield "Lorem Ipsum";
EOF
        ];

        return $tests;
    }

    /**
     * @return array
     */
    private function getNestedBlocksTests(): array
    {
        $tests = [];

        $outerParameters = [];
        $outerParameters[] = new ConstantExpression('type', 1);
        $outerParameters[] = new ConstantExpression('outer', 1);

        $innerParameters = [];
        $innerParameters[] = new ConstantExpression('type', 3);
        $innerParameters[] = new ConstantExpression('inner', 3);

        $topBody = new TextNode('Top', 2);
        $innerBody = new TextNode('Inner', 4);
        $bottomBody = new TextNode('Bottom', 6);

        $innerHasRightsNode = new HasRightsNode($innerBody, new ArrayExpression($innerParameters, 3), 3);

        $outerBody = new Node([$topBody, $innerHasRightsNode, $bottomBody]);

        $node = new HasRightsNode($outerBody, new ArrayExpression($outerParameters, 1), 1);

        $tests[] = [$node, <<<EOF
// line 1
// line 2
yield "Top";
// line 3
// line 4
yield "Inner";
// line 6
yield "Bottom";
EOF
        ];

        return $tests;
    }
}
