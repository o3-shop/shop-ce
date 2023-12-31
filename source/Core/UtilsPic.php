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

namespace OxidEsales\EshopCommunity\Core;

/**
 * Including pictures generator functions file
 */
require_once __DIR__ . "/utils/oxpicgenerator.php";

/**
 * Image manipulation class
 */
class UtilsPic extends \OxidEsales\Eshop\Core\Base
{
    /**
     * Image types 'enum'
     *
     * @var array
     */
    protected $_aImageTypes = ["GIF" => IMAGETYPE_GIF, "JPG" => IMAGETYPE_JPEG, "PNG" => IMAGETYPE_PNG, "JPEG" => IMAGETYPE_JPEG];

    /**
     * Resizes image to desired width and height, returns true on success.
     *
     * @param string $sSrc           Source of image file
     * @param string $sTarget        Target to write resized image file
     * @param mixed  $iDesiredWidth  Width of resized image
     * @param mixed  $iDesiredHeight Height of resized image
     *
     * @return bool
     */
    public function resizeImage($sSrc, $sTarget, $iDesiredWidth, $iDesiredHeight)
    {
        if (file_exists($sSrc) && ($aImageInfo = @getimagesize($sSrc))) {
            $myConfig = $this->getConfig();
            list($iWidth, $iHeight) = calcImageSize($iDesiredWidth, $iDesiredHeight, $aImageInfo[0], $aImageInfo[1]);

            return $this->_resize($aImageInfo, $sSrc, null, $sTarget, $iWidth, $iHeight, getGdVersion(), $myConfig->getConfigParam('blDisableTouch'), $myConfig->getConfigParam('sDefaultImageQuality'));
        }

        return false;
    }

    /**
     * deletes the given picutre and checks before if the picture is deletable
     *
     * @param string $sPicName        Name of picture file
     * @param string $sAbsDynImageDir the absolute image diectory, where to delete the given image ($myConfig->getPictureDir(false))
     * @param string $sTable          in which table
     * @param string $sField          table field value
     *
     * @return bool
     */
    public function safePictureDelete($sPicName, $sAbsDynImageDir, $sTable, $sField)
    {
        $blDelete = false;
        if ($this->_isPicDeletable($sPicName, $sTable, $sField)) {
            $blDelete = $this->_deletePicture($sPicName, $sAbsDynImageDir);
        }

        return $blDelete;
    }

    /**
     * Removes picture file from disk.
     *
     * @param string $sPicName        name of picture
     * @param string $sAbsDynImageDir the absolute image diectory, where to delete the given image ($myConfig->getPictureDir(false))
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "deletePicture" in next major
     */
    protected function _deletePicture($sPicName, $sAbsDynImageDir) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blDeleted = false;
        $myConfig = $this->getConfig();

        if (
            !$myConfig->isDemoShop() && (strpos($sPicName, 'nopic.jpg') === false ||
                                         strpos($sPicName, 'nopic_ico.jpg') === false)
        ) {
            $sFile = "$sAbsDynImageDir/$sPicName";

            if (file_exists($sFile) && is_file($sFile)) {
                $blDeleted = unlink($sFile);
            }

            if (!$myConfig->getConfigParam('sAltImageUrl')) {
                // deleting various size generated images
                $sGenPath = str_replace('/master/', '/generated/', $sAbsDynImageDir);
                $aFiles = glob("{$sGenPath}*/{$sPicName}");
                if (is_array($aFiles)) {
                    foreach ($aFiles as $sFile) {
                        $blDeleted = unlink($sFile);
                    }
                }
            }
        }

        return $blDeleted;
    }


    /**
     * Checks if current picture file is used in more than one table entry, returns
     * true if one, false if more than one.
     *
     * @param string $sPicName Name of picture file
     * @param string $sTable   in which table
     * @param string $sField   table field value
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isPicDeletable" in next major
     */
    protected function _isPicDeletable($sPicName, $sTable, $sField) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$sPicName || strpos($sPicName, 'nopic.jpg') !== false || strpos($sPicName, 'nopic_ico.jpg') !== false) {
            return false;
        }

        $iCountUsed = $this->fetchIsImageDeletable($sPicName, $sTable, $sField);

        return $iCountUsed > 1 ? false : true;
    }

    /**
     * Fetch the information, if the given image is deletable from the database.
     *
     * @param string $sPicName Name of image file.
     * @param string $sTable   The table in which we search for the image.
     * @param string $sField   The value of the table field.
     *
     * @return mixed
     */
    protected function fetchIsImageDeletable($sPicName, $sTable, $sField)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = \OxidEsales\Eshop\Core\DatabaseProvider::getMaster();

        $query = "SELECT count(*) FROM $sTable WHERE $sField = :picturename group by $sField ";

        return $masterDb->getOne($query, [
            ':picturename' => (string) $sPicName
        ]);
    }

    /**
     * Deletes picture if new is uploaded or changed
     *
     * @param object $oObject         in whitch obejct search for old values
     * @param string $sPicTable       pictures table
     * @param string $sPicField       where picture are stored
     * @param string $sPicType        how does it call in $_FILE array
     * @param string $sPicDir         directory of pic
     * @param array  $aParams         new input text array
     * @param string $sAbsDynImageDir the absolute image diectory, where to delete the given image ($myConfig->getPictureDir(false))
     *
     * @return null
     */
    public function overwritePic($oObject, $sPicTable, $sPicField, $sPicType, $sPicDir, $aParams, $sAbsDynImageDir)
    {
        $sPic = $sPicTable . '__' . $sPicField;
        if (
            isset($oObject->{$sPic}) &&
            ($_FILES['myfile']['size'][$sPicType . '@' . $sPic] > 0 || $aParams[$sPic] != $oObject->{$sPic}->value)
        ) {
            $sImgDir = $sAbsDynImageDir . \OxidEsales\Eshop\Core\Registry::getUtilsFile()->getImageDirByType($sPicType);
            return $this->safePictureDelete($oObject->{$sPic}->value, $sImgDir, $sPicTable, $sPicField);
        }

        return false;
    }

    /**
     * Resizes and saves GIF image. This method was separated due to GIF transparency problems.
     *
     * @param string $sSrc            image file
     * @param string $sTarget         destination file
     * @param int    $iNewWidth       new width
     * @param int    $iNewHeight      new height
     * @param int    $iOriginalWidth  original width
     * @param int    $iOriginalHeigth original height
     * @param int    $iGDVer          GD packet version @deprecated
     * @param bool   $blDisableTouch  false if "touch()" should be called
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "resizeGif" in next major
     */
    protected function _resizeGif($sSrc, $sTarget, $iNewWidth, $iNewHeight, $iOriginalWidth, $iOriginalHeigth, $iGDVer, $blDisableTouch) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return resizeGif($sSrc, $sTarget, $iNewWidth, $iNewHeight, $iOriginalWidth, $iOriginalHeigth, $iGDVer, $blDisableTouch);
    }

    /**
     * type dependant image resizing
     *
     * @param array  $aImageInfo        Contains information on image's type / width / height
     * @param string $sSrc              source image
     * @param string $hDestinationImage Destination Image
     * @param string $sTarget           Resized Image target
     * @param int    $iNewWidth         Resized Image's width
     * @param int    $iNewHeight        Resized Image's height
     * @param mixed  $iGdVer            used GDVersion, if null or false returns false @deprecated
     * @param bool   $blDisableTouch    false if "touch()" should be called for gif resizing
     * @param string $iDefQuality       quality for "imagejpeg" function
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "resize" in next major
     */
    protected function _resize($aImageInfo, $sSrc, $hDestinationImage, $sTarget, $iNewWidth, $iNewHeight, $iGdVer, $blDisableTouch, $iDefQuality) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        startProfile("PICTURE_RESIZE");

        $blSuccess = false;
        switch ($aImageInfo[2]) { //Image type
            case ($this->_aImageTypes["GIF"]):
                //php does not process gifs until 7th July 2004 (see lzh licensing)
                if (function_exists("imagegif")) {
                    $blSuccess = resizeGif($sSrc, $sTarget, $iNewWidth, $iNewHeight, $aImageInfo[0], $aImageInfo[1], $iGdVer);
                }
                break;
            case ($this->_aImageTypes["JPEG"]):
            case ($this->_aImageTypes["JPG"]):
                $blSuccess = resizeJpeg($sSrc, $sTarget, $iNewWidth, $iNewHeight, $aImageInfo, $iGdVer, $hDestinationImage, $iDefQuality);
                break;
            case ($this->_aImageTypes["PNG"]):
                $blSuccess = resizePng($sSrc, $sTarget, $iNewWidth, $iNewHeight, $aImageInfo, $iGdVer, $hDestinationImage);
                break;
        }

        if ($blSuccess && !$blDisableTouch) {
            @touch($sTarget);
        }

        stopProfile("PICTURE_RESIZE");

        return $blSuccess;
    }

    /**
     * create and copy the resized image
     *
     * @param string $sDestinationImage file + path of destination
     * @param string $sSourceImage      file + path of source
     * @param int    $iNewWidth         new width of the image
     * @param int    $iNewHeight        new height of the image
     * @param array  $aImageInfo        additional info
     * @param string $sTarget           target file path
     * @param int    $iGdVer            used gd version @deprecated
     * @param bool   $blDisableTouch    wether Touch() should be called or not
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyAlteredImage" in next major
     */
    protected function _copyAlteredImage($sDestinationImage, $sSourceImage, $iNewWidth, $iNewHeight, $aImageInfo, $sTarget, $iGdVer, $blDisableTouch) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blSuccess = copyAlteredImage($sDestinationImage, $sSourceImage, $iNewWidth, $iNewHeight, $aImageInfo, $sTarget, $iGdVer);
        if (!$blDisableTouch && $blSuccess) {
            @touch($sTarget);
        }

        return $blSuccess;
    }
}
