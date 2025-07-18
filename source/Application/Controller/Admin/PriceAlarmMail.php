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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article main pricealarm manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Customer Info -> pricealarm -> Main.
 */
class PriceAlarmMail extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxpricealarm object
     * and passes its data to Smarty engine. Returns name of template file
     * "pricealarm_main.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException|ObjectException
     */
    public function render()
    {
        $config = Registry::getConfig();

        parent::render();

        $shopId = $config->getShopId();
        //articles price in subshop and baseshop can be different
        $this->_aViewData['iAllCnt'] = 0;
        $query = "
            SELECT oxprice, oxartid
            FROM oxpricealarm
            WHERE oxsended = '000-00-00 00:00:00' AND oxshopid = :oxshopid";
        $result = DatabaseProvider::getDb()->select($query, [
            ':oxshopid' => $shopId
        ]);
        if ($result && $result->count() > 0) {
            $simpleCache = [];
            while (!$result->EOF) {
                $price = $result->fields[0];
                $articleId = $result->fields[1];
                if (isset($simpleCache[$articleId])) {
                    if ($simpleCache[$articleId] <= $price) {
                        $this->_aViewData['iAllCnt'] += 1;
                    }
                } else {
                    $article = oxNew(Article::class);
                    if ($article->load($articleId)) {
                        $articlePrice = $simpleCache[$articleId] = $article->getPrice()->getBruttoPrice();
                        if ($articlePrice <= $price) {
                            $this->_aViewData['iAllCnt'] += 1;
                        }
                    }
                }
                $result->fetchRow();
            }
        }

        return "pricealarm_mail.tpl";
    }
}
