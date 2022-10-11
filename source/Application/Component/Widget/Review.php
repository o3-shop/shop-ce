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

namespace OxidEsales\EshopCommunity\Application\Component\Widget;

/**
 * Product reviews widget
 */
class Review extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * User component used in template.
     *
     * @var array
     */
    protected $_aComponentNames = ['oxcmp_user' => 1];

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/reviews/reviews.tpl';

    /**
     * Executes parent::render().
     * Returns name of template file to render.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * Template variable getter. Returns review type
     *
     * @return string
     */
    public function getReviewType()
    {
        return strtolower($this->getViewParameter('type'));
    }

    /**
     * Template variable getter. Returns article id
     *
     * @return string
     */
    public function getArticleId()
    {
        return $this->getViewParameter('aid');
    }

    /**
     * Template variable getter. Returns article nid
     *
     * @return string
     */
    public function getArticleNId()
    {
        return $this->getViewParameter('anid');
    }

    /**
     * Template variable getter. Returns recommlist id
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return string
     */
    public function getRecommListId()
    {
        return $this->getViewParameter('recommid');
    }

    /**
     * Template variable getter. Returns whether user can rate
     *
     * @return string
     */
    public function canRate()
    {
        return $this->getViewParameter('canrate');
    }

    /**
     * Template variable getter. Returns review user id
     *
     * @return string
     */
    public function getReviewUserHash()
    {
        return $this->getViewParameter('reviewuserhash');
    }

    /**
     * Template variable getter. Returns active object's reviews from parent class
     *
     * @return array
     */
    public function getReviews()
    {
        $oReview = $this->getConfig()->getTopActiveView();

        return $oReview->getReviews();
    }
}
