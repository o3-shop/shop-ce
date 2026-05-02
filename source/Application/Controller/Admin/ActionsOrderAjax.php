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

use OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages article select lists sorting
 */
class ActionsOrderAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [
            ['oxtitle', 'oxselectlist', 1, 1, 0],
            ['oxsort', 'oxobject2selectlist', 1, 0, 0],
            ['oxident', 'oxselectlist', 0, 0, 0],
            ['oxvaldesc', 'oxselectlist', 0, 0, 0],
            ['oxid', 'oxobject2selectlist', 0, 0, 1],
        ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated Transitional during #107. Modules SHOULD override _getQuery()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getQuery() to the canonical override
      *             target and retires _getQuery(); until then, _getQuery() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSelTable = $this->getViewName('oxselectlist');
        $sArtId = Registry::getRequest()->getRequestEscapedParameter('oxid');

        return " from $sSelTable left join oxobject2selectlist on oxobject2selectlist.oxselnid = $sSelTable.oxid " .
                 'where oxobjectid = ' . DatabaseProvider::getDb()->quote($sArtId) . '  ';
    }

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getQuery(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getQuery() the canonical override target.
     */
    protected function getQuery()
    {
        return $this->_getQuery();
    }

    /**
     * Returns SQL query addon for sorting
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getSorting()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getSorting() to the canonical override
      *             target and retires _getSorting(); until then, _getSorting() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getSorting() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'order by oxobject2selectlist.oxsort ';
    }

    /**
     * Returns SQL query addon for sorting
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getSorting(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getSorting() the canonical override target.
     */
    protected function getSorting()
    {
        return $this->_getSorting();
    }

    /**
     * Applies sorting for selection lists
     */
    public function setSorting()
    {
        $sSelId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSelect = 'select * from oxobject2selectlist where oxobjectid = :oxobjectid order by oxsort';

        $oList = oxNew(ListModel::class);
        $oList->init('oxbase', 'oxobject2selectlist');
        $oList->selectString($sSelect, [
            ':oxobjectid' => $sSelId,
        ]);

        // fixing indexes
        $iSelCnt = 0;
        $aIdx2Id = [];
        foreach ($oList as $sKey => $oSel) {
            if ($oSel->oxobject2selectlist__oxsort->value != $iSelCnt) {
                $oSel->oxobject2selectlist__oxsort->setValue($iSelCnt);

                // saving new index
                $oSel->save();
            }
            $aIdx2Id[$iSelCnt] = $sKey;
            $iSelCnt++;
        }

        //
        if (($iKey = array_search(Registry::getRequest()->getRequestEscapedParameter('sortoxid'), $aIdx2Id)) !== false) {
            $iDir = (Registry::getRequest()->getRequestEscapedParameter('direction') == 'up') ? ($iKey - 1) : ($iKey + 1);
            if (isset($aIdx2Id[$iDir])) {
                // exchanging indexes
                $oDir1 = $oList->offsetGet($aIdx2Id[$iDir]);
                $oDir2 = $oList->offsetGet($aIdx2Id[$iKey]);

                $iCopy = $oDir1->oxobject2selectlist__oxsort->value;
                $oDir1->oxobject2selectlist__oxsort->setValue($oDir2->oxobject2selectlist__oxsort->value);
                $oDir2->oxobject2selectlist__oxsort->setValue($iCopy);

                $oDir1->save();
                $oDir2->save();
            }
        }

        $sQAdd = $this->getQuery();

        $sQ = 'select ' . $this->_getQueryCols() . $sQAdd;
        $sCountQ = 'select count( * ) ' . $sQAdd;

        $this->_outputResponse($this->getData($sCountQ, $sQ));
    }
}
