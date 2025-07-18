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
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserReviewAndRatingBridgeInterface;

/**
 * Article review manager.
 * Performs loading, updating, inserting of article review.
 *
 */
class Review extends BaseModel
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
    protected $_sClassName = 'oxreview';

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxreviews');
    }

    /**
     * Calls parent::assign and assigns review writer data
     *
     * @param array $dbRecord database record
     *
     * @return bool|null
     * @throws DatabaseConnectionException
     */
    public function assign($dbRecord)
    {
        $blRet = parent::assign($dbRecord);

        if (isset($this->oxreviews__oxuserid) && $this->oxreviews__oxuserid->value) {
            $oDb = DatabaseProvider::getDb();
            $params = [
                ':oxid' => $this->oxreviews__oxuserid->value
            ];

            $firstName = $oDb->getOne("SELECT oxfname FROM oxuser 
                WHERE oxid = :oxid", $params);

            $this->oxuser__oxfname = new Field($firstName);
        }

        return $blRet;
    }

    /**
     * Loads object review information. Returns true on success.
     *
     * @param string $oxId ID of object to load
     *
     * @return bool
     */
    public function load($oxId)
    {
        if ($blRet = parent::load($oxId)) {
            // convert date's to international format
            $this->oxreviews__oxcreate->setValue(
                Registry::getUtilsDate()->formatDBDate($this->oxreviews__oxcreate->value));
        }

        return $blRet;
    }

    /**
     * Inserts object data fields in DB. Returns true on success.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // set oxcreate
        $this->oxreviews__oxcreate = new Field(date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime()));

        return parent::_insert();
    }

    /**
     * get oxList of reviews for given object ids and type
     *
     * @param string $sType type of given IDs
     * @param mixed $aIds given object IDs to load, can be an array or just one id, given as string
     * @param boolean $blLoadEmpty true if it wants to load empty text reviews
     * @param null $iLoadInLang language to select for loading
     *
     * @return ListModel
     * @throws DatabaseConnectionException
     */
    public function loadList($sType, $aIds, $blLoadEmpty = false, $iLoadInLang = null)
    {
        $oRevs = oxNew(ListModel::class);
        $oRevs->init('oxreview');

        $params = [
            ':oxtype' => $sType,
            ':oxlang' => is_null($iLoadInLang) ? (int) Registry::getLang()->getBaseLanguage() : (int) $iLoadInLang
        ];

        if (is_array($aIds) && count($aIds)) {
            $sObjectIdWhere = "oxreviews.oxobjectid in ( " . implode(", ", DatabaseProvider::getDb()->quoteArray($aIds)) . " )";
        } elseif (is_string($aIds) && $aIds) {
            $sObjectIdWhere = "oxreviews.oxobjectid = :oxobjectid";
            $params[':oxobjectid'] = $aIds;
        } else {
            return $oRevs;
        }

        $sSelect = "select oxreviews.* from oxreviews where oxreviews.oxtype = :oxtype and $sObjectIdWhere and oxreviews.oxlang = :oxlang";

        if (!$blLoadEmpty) {
            $sSelect .= ' and oxreviews.oxtext != "" ';
        }

        if (Registry::getConfig()->getConfigParam('blGBModerate')) {
            $sSelect .= ' and ( oxreviews.oxactive = "1" ';

            if ($oUser = $this->getUser()) {
                $sSelect .= 'or  oxreviews.oxuserid = :oxuserid ';
                $params[':oxuserid'] = $oUser->getId();
            }

            $sSelect .= ')';
        }

        $sSelect .= ' order by oxreviews.oxcreate desc ';

        $oRevs->selectString($sSelect, $params);

        // change date
        foreach ($oRevs as $oItem) {
            $oItem->oxreviews__oxcreate->convertToFormattedDbDate();
            $oItem->oxreviews__oxtext->convertToPseudoHtml();
        }

        return $oRevs;
    }

    /**
     * Returns review object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return is_object($this->oxreviews__oxtype) ? $this->oxreviews__oxtype->value : $this->oxreviews__oxtype;
    }

    /**
     * Returns review object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return is_object($this->oxreviews__oxobjectid) ? $this->oxreviews__oxobjectid->value : $this->oxreviews__oxobjectid;
    }

    /**
     * Returns ReviewAndRating list by User id.
     *
     * @param string $userId
     *
     * @return array
     */
    public function getReviewAndRatingListByUserId($userId)
    {
        return $this
            ->getContainer()
            ->get(UserReviewAndRatingBridgeInterface::class)
            ->getReviewAndRatingList($userId);
    }
}
