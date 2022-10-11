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

namespace OxidEsales\EshopCommunity\Core\Module;

/**
 * Provides a way to get content from module template block file.
 *
 * @deprecated 6.6.0
 * @internal Do not make a module extension for this class.
 */
class ModuleTemplateBlockContentReader
{
    /**
     * Read and return content for template block file.
     * Path to template block file is provided via $pathFormatter.
     * Throw exception if file does not exist or is not readable.
     *
     * @param \OxidEsales\Eshop\Core\Module\ModuleTemplateBlockPathFormatter $pathFormatter
     *
     * @throws \oxException
     *
     * @return string
     */
    public function getContent($pathFormatter)
    {
        if (!$pathFormatter instanceof \OxidEsales\Eshop\Core\Module\ModuleTemplateBlockPathFormatter) {
            $exceptionMessage = 'Provided object is not an instance of class %s or does not have method getPath().';
            throw oxNew('oxException', sprintf($exceptionMessage, \OxidEsales\Eshop\Core\Module\ModuleTemplateBlockPathFormatter::class));
        }

        $filePath = $pathFormatter->getPath();

        if (!file_exists($filePath)) {
            $exceptionMessage = "Template block file (%s) was not found for module '%s'.";
            throw oxNew('oxException', sprintf($exceptionMessage, $filePath, $pathFormatter->getModuleId()));
        }

        if (!is_readable($filePath)) {
            $exceptionMessage = "Template block file (%s) is not readable for module '%s'.";
            throw oxNew('oxException', sprintf($exceptionMessage, $filePath, $pathFormatter->getModuleId()));
        }

        return file_get_contents($filePath);
    }
}
