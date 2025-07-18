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

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\ProductRatingBridgeInterface;

/**
 * Article rate manager.
 * Performs loading, updating, inserting of article rates.
 *
 */
class Rating extends BaseModel
{
    /**
     * Shop control variable
     *
     * @var string
     */
    protected $_blDisableShopCheck = true;

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxrating';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxratings');
    }

    /**
     * Checks if user can rate product.
     *
     * @param string $sUserId user id
     * @param string $sType object type
     * @param string $sObjectId object id
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function allowRating($sUserId, $sType, $sObjectId)
    {
        $oDb = DatabaseProvider::getDb();
        $myConfig = Registry::getConfig();

        if ($iRatingLogsTimeout = $myConfig->getConfigParam('iRatingLogsTimeout')) {
            $sExpDate = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime() - $iRatingLogsTimeout * 24 * 60 * 60);
            $oDb->execute("delete from oxratings where oxtimestamp < :expDate", [
                ':expDate' => $sExpDate
            ]);
        }
        $sSelect = "select oxid from oxratings 
            where oxuserid = :oxuserid 
                and oxtype = :oxtype 
                and oxobjectid = :oxobjectid";
        $params = [
            ':oxuserid' => $sUserId,
            ':oxtype' => $sType,
            ':oxobjectid' => $sObjectId
        ];

        if ($oDb->getOne($sSelect, $params)) {
            return false;
        }

        return true;
    }


    /**
     * calculates and return objects rating
     *
     * @param string $sObjectId object id
     * @param string $sType object type
     * @param null $aIncludedObjectsIds array of ids
     *
     * @return float
     * @throws DatabaseConnectionException
     */
    public function getRatingAverage($sObjectId, $sType, $aIncludedObjectsIds = null)
    {
        $sQuerySnippet = " AND `oxobjectid` = :oxobjectid";
        if (is_array($aIncludedObjectsIds) && count($aIncludedObjectsIds) > 0) {
            $sQuerySnippet = " AND ( `oxobjectid` = :oxobjectid OR `oxobjectid` in ('" . implode("', '", $aIncludedObjectsIds) . "') )";
        }

        $sSelect = "
            SELECT
                AVG(`oxrating`)
            FROM `oxreviews`
            WHERE `oxrating` > 0
                 AND `oxtype` = :oxtype"
                   . $sQuerySnippet . "
            LIMIT 1";

        $params = [
            ':oxobjectid' => $sObjectId,
            ':oxtype' => $sType
        ];

        $database = DatabaseProvider::getMaster();
        if ($fRating = $database->getOne($sSelect, $params)) {
            $fRating = round($fRating, 1);
        }

        return $fRating;
    }

    /**
     * calculates and return objects rating count
     *
     * @param string $sObjectId object id
     * @param string $sType object type
     * @param null $aIncludedObjectsIds array of ids
     *
     * @return false|string
     * @throws DatabaseConnectionException
     */
    public function getRatingCount($sObjectId, $sType, $aIncludedObjectsIds = null)
    {
        $sQuerySnippet = " AND `oxobjectid` = :oxobjectid";
        if (is_array($aIncludedObjectsIds) && count($aIncludedObjectsIds) > 0) {
            $sQuerySnippet = " AND ( `oxobjectid` = :oxobjectid OR `oxobjectid` in ('" . implode("', '", $aIncludedObjectsIds) . "') )";
        }

        $sSelect = "
            SELECT
                COUNT(*)
            FROM `oxreviews`
            WHERE `oxrating` > 0
                AND `oxtype` = :oxtype"
                   . $sQuerySnippet . "
            LIMIT 1";

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = DatabaseProvider::getMaster();
        $iCount = $masterDb->getOne($sSelect, [
            ':oxobjectid' => $sObjectId,
            ':oxtype' => $sType
        ]);

        return $iCount;
    }

    /**
     * Returns review object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->oxratings__oxtype->value;
    }

    /**
     * Returns review object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->oxratings__oxobjectid->value;
    }

    /**
     * Delete this object from the database, returns true if entry was deleted.
     *
     * @param string $oxid Object ID(default null)
     *
     * @return bool
     */
    public function delete($oxid = null)
    {
        $isProductRating = $this->isProductObjectType();

        $isDeleted = parent::delete($oxid);

        if ($isProductRating) {
            $this->updateProductRating();
        }

        return $isDeleted;
    }


    /**
     * Returns true if Rating belongs to Product.
     *
     * @return bool
     */
    private function isProductObjectType()
    {
        return $this->getObjectType() === 'oxarticle';
    }

    /**
     * Updates Product rating.
     */
    private function updateProductRating()
    {
        $this
            ->getContainer()
            ->get(ProductRatingBridgeInterface::class)
            ->updateProductRating($this->getObjectId());
    }
}
