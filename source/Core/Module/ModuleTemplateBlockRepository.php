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
 * @internal Do not make a module extension for this class.
 */
class ModuleTemplateBlockRepository
{
    /**
     * Return how many blocks of provided module overrides any template for active shop.
     *
     * @param array  $modulesId list of modules to check if their template blocks overrides some shop block.
     * @param string $shopId    shop id to check if some module block overrides some template blocks in this Shop.
     *
     * @return string count of blocks for Shop=$shopId from modules=$modulesId.
     */
    public function getBlocksCount($modulesId, $shopId)
    {
        $db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $modulesIdQuery = implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($modulesId));
        $sql = "select COUNT(*)
                            from oxtplblocks
                            where oxactive = :oxactive
                                and oxshopid = :oxshopid
                                and oxmodule in ( " . $modulesIdQuery . " )";

        return $db->getOne($sql, [
            ':oxactive' => '1',
            ':oxshopid' => $shopId
        ]);
    }

    /**
     * Get modules template blocks information filtered by provided parameters.
     *
     * @param string $shopTemplateName shop template file name.
     * @param array  $activeModulesId  list of modules to get information about.
     * @param string $shopId           in which Shop modules must be active.
     * @param array  $themesId         list of themes to get information about.
     *
     * @return array
     */
    public function getBlocks($shopTemplateName, $activeModulesId, $shopId, $themesId = [])
    {
        $modulesId = implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($activeModulesId));

        $activeThemesIdQuery = $this->formActiveThemesIdQuery($themesId);
        $sql = "select *
                    from oxtplblocks
                    where oxactive=1
                        and oxshopid= :oxshopid
                        and oxtemplate= :oxtemplate
                        and oxmodule in ( " . $modulesId . " )
                        and oxtheme in (" . $activeThemesIdQuery . ")
                        order by oxpos asc, oxtheme asc, oxid asc";
        $db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);

        return $db->getAll($sql, [
            ':oxshopid' => $shopId,
            ':oxtemplate' => $shopTemplateName
        ]);
    }

    /**
     * To form sql query part for active themes.
     *
     * @param array $activeThemeIds
     *
     * @return string
     */
    private function formActiveThemesIdQuery($activeThemeIds = [])
    {
        $defaultThemeIndicator = '';
        array_unshift($activeThemeIds, $defaultThemeIndicator);

        return implode(', ', \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($activeThemeIds));
    }
}
