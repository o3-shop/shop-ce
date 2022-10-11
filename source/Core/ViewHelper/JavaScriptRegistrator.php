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

namespace OxidEsales\EshopCommunity\Core\ViewHelper;

/**
 * Class for preparing JavaScript.
 */
class JavaScriptRegistrator
{
    const SNIPPETS_PARAMETER_NAME = 'scripts';
    const FILES_PARAMETER_NAME = 'includes';

    /**
     * Register JavaScript code snippet for rendering.
     *
     * @param string $script
     * @param bool   $isDynamic
     */
    public function addSnippet($script, $isDynamic = false)
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $suffix = $isDynamic ? '_dynamic' : '';
        $scriptsParameterName = static::SNIPPETS_PARAMETER_NAME . $suffix;
        $scripts = (array) $config->getGlobalParameter($scriptsParameterName);
        $script = trim($script);
        if (!in_array($script, $scripts)) {
            $scripts[] = $script;
        }
        $config->setGlobalParameter($scriptsParameterName, $scripts);
    }

    /**
     * Register JavaScript file (local or remote) for rendering.
     *
     * @param string $file
     * @param int    $priority
     * @param bool   $isDynamic
     */
    public function addFile($file, $priority, $isDynamic = false)
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $suffix = $isDynamic ? '_dynamic' : '';
        $filesParameterName = static::FILES_PARAMETER_NAME . $suffix;
        $includes = (array) $config->getGlobalParameter($filesParameterName);

        if (!preg_match('#^https?://#', $file)) {
            $file = $this->formLocalFileUrl($file);
        }

        if ($file) {
            $includes[$priority][] = $file;
            $includes[$priority] = array_unique($includes[$priority]);
            $config->setGlobalParameter($filesParameterName, $includes);
        }
    }

    /**
     * Separate query part, appends query part if needed, append file modification timestamp.
     *
     * @param string $file
     *
     * @return string
     */
    protected function formLocalFileUrl($file)
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $parts = explode('?', $file);
        $url = $config->getResourceUrl($parts[0], $config->isAdmin());
        if (isset($parts[1])) {
            $parameters = $parts[1];
        } else {
            $path = $config->getResourcePath($file, $config->isAdmin());
            $parameters = filemtime($path);
        }

        if (empty($url) && $config->getConfigParam('iDebug') != 0) {
            $error = "{oxscript} resource not found: " . getStr()->htmlspecialchars($file);
            trigger_error($error, E_USER_WARNING);
        }

        return $url ? "$url?$parameters" : '';
    }
}
