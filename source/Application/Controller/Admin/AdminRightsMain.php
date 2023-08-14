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

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Application\Model\RightsRoles;
use OxidEsales\EshopCommunity\Application\Model\adminNaviRights;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Unit\Core\oxNoJsValidatorTest;
use oxSysRequirements;

/**
 * Collects System information.
 * Admin Menu: Service -> System Requirements -> Main.
 */
class AdminRightsMain extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Loads article Mercators info, passes it to Smarty engine and
     * returns name of template file "Mercator_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->addTplParam('adminNaviRights', oxNew(adminNaviRights::class));

        return "userrights_main.tpl";
    }

    public function getMallAdminUsers()
    {
        $currentUserId = $this->getUser()->getId();
        $selectedId = Registry::getRequest()->getRequestEscapedParameter('user');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create();

        $queryBuilder->select('oxid', 'oxusername', 'oxfname', 'oxlname', 'IF (oxid = \''.$selectedId.'\', 1, 0) as selected')
            ->from((oxNew(User::class))->getViewName())
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'oxrights',
                        $queryBuilder->createNamedParameter('malladmin')
                    ),
                    $queryBuilder->expr()->neq(
                        'oxid',
                        $queryBuilder->createNamedParameter($currentUserId)
                    )
                )
            );

        $users = $queryBuilder->execute()->fetchAllAssociative();

        return $users;
    }

    public function showSelectableMenuItems()
    {
        return (bool) Registry::getRequest()->getRequestEscapedParameter('user');
    }

    public function save()
    {
        $userId = Registry::getRequest()->getRequestEscapedParameter('user');

        $item = oxNew(RightsRoles::class);
        $item->clearItemsFromUser($userId);

        foreach (Registry::getRequest()->getRequestEscapedParameter('emadminnavirightsnavi') as $itemId) {
            $item = oxNew(RightsRoles::class);
            $item->saveItemForUser($itemId, $userId);
        }

        parent::save();
    }

    /**
     * Returns module state
     *
     * @param int $iModuleState state integer value
     *
     * @return string
     */
    public function getModuleClass($iModuleState)
    {
        switch ($iModuleState) {
            case 2:
                $sClass = 'pass';
                break;
            case 1:
                $sClass = 'pmin';
                break;
            case -1:
                $sClass = 'null';
                break;
            default:
                $sClass = 'fail';
                break;
        }
        return $sClass;
    }

    /**
     * Returns hint URL
     *
     * @param string $sIdent Module ident
     *
     * @return string
     */
    public function getReqInfoUrl($sIdent)
    {
        $oSysReq = oxNew(\OxidEsales\Eshop\Core\SystemRequirements::class);

        return $oSysReq->getReqInfoUrl($sIdent);
    }

    /**
     * return missing template blocks
     *
     * @see \OxidEsales\Eshop\Core\SystemRequirements::getMissingTemplateBlocks
     *
     * @return array
     */
    public function getMissingTemplateBlocks()
    {
        $oSysReq = oxNew(\OxidEsales\Eshop\Core\SystemRequirements::class);

        return $oSysReq->getMissingTemplateBlocks();
    }
}
