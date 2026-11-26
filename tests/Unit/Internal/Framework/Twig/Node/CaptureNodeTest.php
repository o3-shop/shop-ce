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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Node\CaptureNode;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class CaptureNodeTest extends NodeTestCase
{
    private $variableName = 'foo';
    private $line = 1;
    private $tag = 'capture';
    private $body;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->body = new TextNode('Lorem Ipsum', 1);
    }

    public function getTests()
    {
        return array_merge($this->getTestForCaptureWithAttributeName(), $this->getTestForCaptureWithAttributeAssign(), $this->getTestForCaptureWithAttributeAppend());
    }

    private function getTestForCaptureWithAttributeName()
    {
        $tests = [];
        $nodeForCaptureName = new CaptureNode('name', $this->variableName, $this->body, $this->line, $this->tag);

        $tests[] = [$nodeForCaptureName, <<<EOF
// line 1
\$captureContent = \\Twig\\Extension\\CoreExtension::captureOutput((function () use (&\$context, \$macros, \$blocks) {
    yield "Lorem Ipsum";
    return; yield '';
})());
\$context['twig']['capture']["foo"] = \$captureContent;
unset(\$captureContent);
EOF
        ];

        return $tests;
    }

    private function getTestForCaptureWithAttributeAssign()
    {
        $nodeForCaptureAssign = new CaptureNode('assign', $this->variableName, $this->body, $this->line, $this->tag);
        $tests[] = [$nodeForCaptureAssign, <<<EOF
// line 1
\$captureContent = \\Twig\\Extension\\CoreExtension::captureOutput((function () use (&\$context, \$macros, \$blocks) {
    yield "Lorem Ipsum";
    return; yield '';
})());
if ("foo" != '') {
\$context["foo"] = \$captureContent;
}
unset(\$captureContent);
EOF
        ];

        return $tests;
    }

    private function getTestForCaptureWithAttributeAppend()
    {
        $nodeForCaptureAssign = new CaptureNode('append', $this->variableName, $this->body, $this->line, $this->tag);
        $tests[] = [$nodeForCaptureAssign, <<<EOF
// line 1
\$captureContent = \\Twig\\Extension\\CoreExtension::captureOutput((function () use (&\$context, \$macros, \$blocks) {
    yield "Lorem Ipsum";
    return; yield '';
})());
if ("foo" != '' && isset(\$captureContent)) {
if (!isset(\$context["foo"])) {
\$context["foo"] = [];
}
if (!is_array(\$context["foo"])) {
\$context["foo"] = [\$context["foo"]];
}
\$context["foo"][] = \$captureContent;
}
unset(\$captureContent);
EOF
        ];

        return $tests;
    }
}
