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

use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Media URL handler
 *
 */
class MediaUrl extends MultiLanguageModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxmediaurls';

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxmediaurls');
    }

    /**
     * Return HTML code depending on current URL
     *
     * @return string
     */
    public function getHtml()
    {
        $sUrl = $this->oxmediaurls__oxurl->value;
        //youtube link
        if (strpos($sUrl, 'youtube.com') || strpos($sUrl, 'youtu.be')) {
            return $this->_getYoutubeHtml();
        }

        //simple link
        return $this->getHtmlLink();
    }

    /**
     * Returns simple HTML link
     *
     * @param bool $blNewPage Whether to open link in new window (adds target=_blank to link)
     *
     * @return string
     */
    public function getHtmlLink($blNewPage = true)
    {
        $sForceBlank = $blNewPage ? ' target="_blank"' : '';
        $sDesc = $this->oxmediaurls__oxdesc->value;
        $sUrl = $this->getLink();

        $sHtmlLink = "<a href=\"$sUrl\"{$sForceBlank}>$sDesc</a>";

        return $sHtmlLink;
    }

    /**
     * Returns  link
     *
     * @return string
     */
    public function getLink()
    {
        if ($this->oxmediaurls__oxisuploaded->value) {
            $sUrl = Registry::getConfig()->isSsl() ? Registry::getConfig()->getSslShopUrl() : Registry::getConfig()->getShopUrl();
            $sUrl .= 'out/media/';
            $sUrl .= basename($this->oxmediaurls__oxurl->value);
        } else {
            $sUrl = $this->oxmediaurls__oxurl->value;
        }

        return $sUrl;
    }

    /**
     * Returns  object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->oxmediaurls__oxobjectid->value;
    }

    /**
     * Deletes record and unlinks the file
     *
     * @param string $sOXID Object ID(default null)
     *
     * @return bool
     */
    public function delete($sOXID = null)
    {
        $sFilePath = Registry::getConfig()->getConfigParam('sShopDir') . "/out/media/" .
                     basename($this->oxmediaurls__oxurl->value);

        if ($this->oxmediaurls__oxisuploaded->value) {
            if (file_exists($sFilePath)) {
                unlink($sFilePath);
            }
        }

        return parent::delete($sOXID);
    }

    /**
     * Transforms the link to YouTube object, and returns it.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getYoutubeHtml" in next major
     */
    protected function _getYoutubeHtml() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sUrl = $this->oxmediaurls__oxurl->value;
        $sDesc = $this->oxmediaurls__oxdesc->value;

        if (strpos($sUrl, 'youtube.com')) {
            $sYoutubeUrl = str_replace("www.youtube.com/watch?v=", "www.youtube.com/embed/", $sUrl);
            $sYoutubeUrl = preg_replace('/&amp;/', '?', $sYoutubeUrl, 1);
        }
        if (strpos($sUrl, 'youtu.be')) {
            $sYoutubeUrl = str_replace("youtu.be/", "www.youtube.com/embed/", $sUrl);
        }

        $sYoutubeTemplate = '%s<br><iframe width="425" height="344" src="%s" frameborder="0" allowfullscreen></iframe>';
        $sYoutubeHtml = sprintf($sYoutubeTemplate, $sDesc, $sYoutubeUrl, $sYoutubeUrl);

        return $sYoutubeHtml;
    }
}
