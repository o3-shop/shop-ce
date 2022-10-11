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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfiguration;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfigurationDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Template;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ThemedTemplate;

class TemplatesDataMapper implements ModuleConfigurationDataMapperInterface
{
    public const MAPPING_KEY = 'templates';

    public function toData(ModuleConfiguration $configuration): array
    {
        $data = [];

        if ($configuration->hasTemplates()) {
            $data[self::MAPPING_KEY] = $this->getTemplates($configuration);
        }

        return $data;
    }

    public function fromData(ModuleConfiguration $moduleConfiguration, array $data): ModuleConfiguration
    {
        if (isset($data[self::MAPPING_KEY])) {
            $this->setTemplates($moduleConfiguration, $data[self::MAPPING_KEY]);
        }

        return $moduleConfiguration;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param array $template
     */
    private function setTemplates(ModuleConfiguration $moduleConfiguration, array $template): void
    {
        foreach ($template as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $themedTemplateKey => $themedTemplatePath) {
                    $moduleConfiguration->addTemplate(
                        new ThemedTemplate($themedTemplateKey, $themedTemplatePath, $key)
                    );
                }
            } else {
                $moduleConfiguration->addTemplate(
                    new Template($key, $value)
                );
            }
        }
    }

    /**
     * @param ModuleConfiguration $configuration
     *
     * @return array
     */
    private function getTemplates(ModuleConfiguration $configuration): array
    {
        $templates = [];
        foreach ($configuration->getTemplates() as $template) {
            if ($template instanceof ThemedTemplate) {
                $templates[$template->getTemplateTheme()][$template->getTemplateKey()] = $template->getTemplatePath();
            } else {
                $templates[$template->getTemplateKey()] = $template->getTemplatePath();
            }
        }
        return $templates;
    }
}
