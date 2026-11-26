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

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\IfContentTokenParser;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IfContentLogic;
use Twig\Extension\AbstractExtension;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFunction;

/**
 * Class IfContentExtension
 */
class IfContentExtension extends AbstractExtension
{
    /**
     * @var IfContentLogic
     */
    private $ifContentLogic;

    /**
     * IfContentExtension constructor.
     *
     * @param IfContentLogic $ifContentLogic
     */
    public function __construct(IfContentLogic $ifContentLogic)
    {
        $this->ifContentLogic = $ifContentLogic;
    }

    /**
     * @return TokenParserInterface[]
     */
    public function getTokenParsers(): array
    {
        return [new IfContentTokenParser()];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('include_content', [$this, 'includeContent'], ['needs_context' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Twig counterpart of Smarty's [{oxcontent ident=… field=… oxid=…}].
     */
    public function includeContent(array $context, ?string $ident = null, string $field = 'oxcontent', ?string $oxid = null): string
    {
        return $this->ifContentLogic->includeContent($context, $ident, $field, $oxid);
    }

    /**
     * The content for {% ifcontent %}, its stored fields already parsed by Smarty. The template body is never parsed.
     *
     * @param string $sIdent
     * @param string $sOxid
     *
     * @return mixed
     */
    public function getContent(?string $sIdent = null, string $sOxid = null)
    {
        return $this->ifContentLogic->getParsedContent($sIdent, $sOxid);
    }
}
