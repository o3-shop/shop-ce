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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Application\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Core\Model\ListModel;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class RightsRolesElementsList extends ListModel
{
    protected $_sObjectsInListName = RightsRolesElement::class;

    /**
     * @param string $roleId
     * @return $this
     */
    public function getElementsByRole(string $roleId)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('*')
            ->from($this->getBaseObject()->getViewName())
            ->where(
                $queryBuilder->expr()->eq(
                    'roleid',
                    $queryBuilder->createNamedParameter($roleId)
                )
            );

        $this->selectString($queryBuilder->getSQL(), $queryBuilder->getParameters());

        return $this;
    }

    public function getElementsIdsByRole(string $roleId)
    {
        $this->getElementsByRole($roleId);

        return array_map(
        /** @var $item RightsRolesElement */
            function ($item) {
                return $item->getFieldData('elementid');
            },
            $this->getArray()
        );
    }

    public function setNaviSettings(array $aNaviSetting, $roleId)
    {
        $delete = $this->getQueryBuilder();
        $delete->delete($this->getBaseObject()->getCoreTableName())
            ->where(
                $delete->expr()->eq(
                    'roleid',
                    $delete->createNamedParameter($roleId)
                )
            );
        $delete->execute();

        foreach ($aNaviSetting as $naviSetting) {
            $element = oxNew($this->_sObjectsInListName);
            $element->assign([
                'elementid' => $naviSetting,
                'roleid'    => $roleId
            ]);
            $element->save();
        }
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create();
    }
}