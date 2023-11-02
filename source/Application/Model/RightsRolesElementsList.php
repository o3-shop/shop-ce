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
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class RightsRolesElementsList extends ListModel
{
    protected $_sObjectsInListName = RightsRolesElement::class;

    /**
     * @param string $objectId
     *
     * @return $this
     */
    public function getElementsByObjectId(string $objectId)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('*')
            ->from($this->getBaseObject()->getViewName())
            ->where(
                $queryBuilder->expr()->eq(
                    'objectid',
                    $queryBuilder->createNamedParameter( $objectId)
                )
            );

        $this->selectString($queryBuilder->getSQL(), $queryBuilder->getParameters());

        return $this;
    }

    /**
     * @param string $userId
     * @return array
     */
    public function getElementsByUserId(string $userId): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('DISTINCT(re.elementid) as elementid', 'MAX(re.TYPE) as type')
            ->from(
                (oxNew(\OxidEsales\Eshop\Application\Model\RightsRoles::class)->getViewName()),
                'rr'
            )
            ->leftJoin(
                'rr',
                'o3object2role',
                'o2r',
                $queryBuilder->expr()->eq('rr.oxid', 'o2r.roleid')
            )
            ->leftJoin(
                'rr',
                $this->getBaseObject()->getViewName(),
                're',
                $queryBuilder->expr()->eq('rr.oxid', 're.objectid')
            )
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'rr.active',
                        $queryBuilder->createNamedParameter(1)
                    ),
                    $queryBuilder->expr()->eq(
                        'o2r.objectid',
                        $queryBuilder->createNamedParameter($userId)
                    )
                )
            )
            ->groupBy('re.elementid');

        return
            array_combine(
                array_filter(
                    array_map(
                        function (array $qbItem) {
                            return $qbItem['elementid'];
                        },
                        $queryBuilder->execute()->fetchAllAssociative()
                    ),
                    [$this, 'filterEmptyButZero']
                ),
                array_filter(
                    array_map(
                        function (array $qbItem) {
                            return (int) $qbItem['type'];
                        },
                        $queryBuilder->execute()->fetchAllAssociative()
                    ),
                    [$this, 'filterEmptyButZero']
                )
            );
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRestrictedViewElements(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('DISTINCT(re.elementid) as elementid, MAX(re.type) as type')
            ->from(
                $this->getBaseObject()->getViewName(),
                're'
            )
            ->where(
                $queryBuilder->expr()->eq(
                    're.objectid',
                    $queryBuilder->createNamedParameter(Registry::getSession()->getUser()->getId())
                )
            )
            ->groupBy('re.elementid');

        return  array_combine(
            array_filter(
                array_map(
                    function (array $qbItem) {
                        return $qbItem['elementid'];
                    },
                    $queryBuilder->execute()->fetchAllAssociative()
                ),
                [$this, 'filterEmptyButZero']
            ),
            array_filter(
                array_map(
                    function (array $qbItem) {
                        return (int)  $qbItem['type'];
                    },
                    $queryBuilder->execute()->fetchAllAssociative()
                ),
                [$this, 'filterEmptyButZero']
            )
        );
    }

    public function getElementsIdsByObjectId(string $objectId)
    {
        $this->getElementsByObjectId($objectId);

        return array_combine(
            array_filter(
                array_map(
                /** @var $item RightsRolesElement */
                    function ($item) {
                        return $item->getFieldData('elementid');
                    },
                    $this->getArray()
                ),
                [$this, 'filterEmptyButZero']
            ),
            array_filter(
                array_map(
                /** @var $item RightsRolesElement */
                    function ($item) {
                        return $item->getFieldData('type');
                    },
                    $this->getArray()
                ),
                [$this, 'filterEmptyButZero']
            )
        );
    }

    public function setNaviSettings(array $aNaviSetting, $objectId)
    {
        $delete = $this->getQueryBuilder();
        $delete->delete($this->getBaseObject()->getCoreTableName())
            ->where(
                $delete->expr()->eq(
                    'objectid',
                    $delete->createNamedParameter( $objectId)
                )
            );
        $delete->execute();

        foreach ($aNaviSetting as $naviSetting => $rightType) {
            $element = oxNew($this->_sObjectsInListName);
            $element->assign([
                'elementid' => $naviSetting,
                'objectid'  => $objectId,
                'type'      => $rightType
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

    protected function filterEmptyButZero($var)
    {
        return ($var !== NULL && $var !== FALSE && $var !== '');
    }
}