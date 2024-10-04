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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin Contents manager.
 * Collects Content base information (Description), there is ability to filter
 * them by Description or delete them.
 * Admin Menu: Customer-Information -> Content.
 */
class ContentList extends AdminListController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxcontent';

    /**
     * Type of list.
     *
     * @var string
     */
    protected $_sListType = 'oxcontentlist';

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = "content_list.tpl";

    /**
     * Executes parent method parent::render() and returns current class template
     * name.
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        $sFolder = Registry::getRequest()->getRequestEscapedParameter('folder');
        $sFolder = $sFolder ? $sFolder : -1;

        $this->_aViewData["folder"] = $sFolder;
        $this->_aViewData["afolder"] = Registry::getConfig()->getConfigParam('aCMSfolder');

        return $this->_sThisTemplate;
    }

    /**
     * Adding folder check and empty folder field check.
     *
     * @param array $whereQuery SQL condition array
     * @param string $fullQuery SQL query string
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareWhereQuery" in next major
     */
    protected function _prepareWhereQuery($whereQuery, $fullQuery) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->prepareWhereQuery($whereQuery, $fullQuery);
    }

    /**
     * Adding folder check and empty folder field check.
     *
     * @param array $whereQuery SQL condition array
     * @param string $fullQuery SQL query string
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareWhereQuery($whereQuery, $fullQuery)
    {
        $sQ = parent::prepareWhereQuery($whereQuery, $fullQuery);
        $sFolder = Registry::getRequest()->getRequestEscapedParameter('folder');
        $sViewName = getviewName("oxcontents");

        // searching for empty oxfolder fields
        if ($sFolder == 'CMSFOLDER_NONE' || $sFolder == 'CMSFOLDER_NONE_RR') {
            $sQ .= " and {$sViewName}.oxfolder = '' ";
        } elseif ($sFolder && $sFolder != '-1') {
            $sFolder = DatabaseProvider::getDb()->quote($sFolder);
            $sQ .= " and {$sViewName}.oxfolder = {$sFolder}";
        }

        return $sQ;
    }    
}
