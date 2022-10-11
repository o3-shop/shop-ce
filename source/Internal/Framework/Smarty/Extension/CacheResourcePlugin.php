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

namespace OxidEsales\EshopCommunity\Internal\Framework\Smarty\Extension;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyContextInterface;

/**
 * Cache resource
 */
class CacheResourcePlugin
{
    /**
     * @var SmartyContextInterface
     */
    private static $context;

    /**
     * @param SmartyContextInterface $context
     */
    public function __construct(SmartyContextInterface $context)
    {
        self::$context = $context;
    }

    /**
     * Sets template content from cache. In demoshop enables security mode.
     *
     * @see http://www.smarty.net/docsv2/en/template.resources.tpl
     *
     * @param string $templateName   The name of template
     * @param string $templateSource The template source
     * @param object $smarty         The smarty object
     *
     * @return bool
     */
    public static function getTemplate($templateName, &$templateSource, $smarty)
    {
        if (isset($smarty->oxidcache) && isset($smarty->oxidcache->value)) {
            $templateSource = $smarty->oxidcache->value;
        }
        if (self::$context->getTemplateSecurityMode()) {
            $smarty->security = true;
        }

        return true;
    }

    /**
     * Sets time for smarty templates recompilation. If oxidtimecache is set,
     * smarty will cache templates for this period. Otherwise templates will always be compiled.
     *
     * @see http://www.smarty.net/docsv2/en/template.resources.tpl
     *
     * @param string $templateName      The name of template
     * @param string $templateTimestamp The template timestamp reference
     * @param object $smarty            The smarty object
     *
     * @return bool
     */
    public static function getTimestamp($templateName, &$templateTimestamp, $smarty)
    {
        $templateTimestamp = isset($smarty->oxidtimecache->value) ? $smarty->oxidtimecache->value : time();

        return true;
    }

    /**
     * Dummy function, required for smarty plugin registration.
     *
     * @see http://www.smarty.net/docsv2/en/template.resources.tpl
     *
     * @param string $templateName The name of template
     * @param object $smarty       The smarty object
     *
     * @return bool
     */
    public static function getSecure($templateName, $smarty)
    {
        return true;
    }

    /**
     * Dummy function, required for smarty plugin registration.
     *
     * @see http://www.smarty.net/docsv2/en/template.resources.tpl
     *
     * @param string $templateName The name of template
     * @param object $smarty       The smarty object
     */
    public static function getTrusted($templateName, $smarty)
    {
    }
}
