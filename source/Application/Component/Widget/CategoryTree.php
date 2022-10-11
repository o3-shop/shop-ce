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

namespace  OxidEsales\EshopCommunity\Application\Component\Widget;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoaderInterface;

/**
 * Category tree widget.
 * Forms category tree.
 */
class CategoryTree extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * Cartegory component used in template.
     *
     * @var array
     */
    protected $_aComponentNames = ['oxcmp_categories' => 1];

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/sidebar/categorytree.tpl';

    /**
     * Executes parent::render(), assigns template name and returns it
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        if ($sTpl = $this->getViewParameter("sWidgetType")) {
            $sTemplateName = 'widget/' . basename($sTpl) . '/categorylist.tpl';
            /** @var TemplateLoaderInterface $templateLoader */
            $templateLoader = $this->getContainer()->get('oxid_esales.templating.template.loader');
            if ($templateLoader->exists($sTemplateName)) {
                $this->_sThisTemplate = $sTemplateName;
            }
        }

        return $this->_sThisTemplate;
    }

    /**
     * Returns the deep level of category tree
     *
     * @return null
     */
    public function getDeepLevel()
    {
        return $this->getViewParameter("deepLevel");
    }

    /**
     * Content category getter.
     *
     * @return bool|string
     */
    public function getContentCategory()
    {
        $request = Registry::get(Request::class);
        return $request->getRequestParameter('oxcid', false);
    }
}
