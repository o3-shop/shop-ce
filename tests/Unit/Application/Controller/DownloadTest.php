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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use \exception;
use \oxTestModules;

/**
 * Tests for Download class
 */
class DownloadTest extends \OxidTestCase
{


    /**
     * Test get article list.
     *
     * @return null
     */
    public function testRender()
    {
        $this->setRequestParameter('sorderfileid', "_testOrderFile");
        $oOrderFile = $this->getMock(\OxidEsales\Eshop\Application\Model\OrderFile::class, array('load', 'processOrderFile', 'getFileId'));
        $oOrderFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oOrderFile->expects($this->any())->method('processOrderFile')->will($this->returnValue('_fileId'));
        $oOrderFile->expects($this->any())->method('getFileId')->will($this->returnValue('_fileId'));
        oxTestModules::addModuleObject('oxOrderFile', $oOrderFile);

        $oFile = $this->getMock(\OxidEsales\Eshop\Application\Model\File::class, array('load', 'download', 'exist'));
        $oFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oFile->expects($this->any())->method('exist')->will($this->returnValue(true));
        $oFile->expects($this->any())->method('download');
        oxTestModules::addModuleObject('oxFile', $oFile);

        $oDownloads = oxNew('Download');

        $oDownloads->render();
    }

    /**
     * Test get article list.
     *
     * @return null
     */
    public function testRenderWrongLink()
    {
        oxTestModules::addFunction("oxUtils", "redirect", "{ throw new exception( 'testDownload', 123 );}");

        try {
            $oDownloads = oxNew('Download');
            $oDownloads->render();
        } catch (Exception $oEx) {
            $this->assertEquals(123, $oEx->getCode(), 'Error executing "testRenderWrongLink" test');

            return;
        }

        $this->fail('Redirect was not called');
    }

    /**
     * Test get article list.
     *
     * @return null
     */
    public function testRenderDownloadFailed()
    {
        oxTestModules::addFunction("oxUtils", "redirect", "{ throw new exception( 'testDownload', 123 );}");
        oxTestModules::addFunction("oxFile", "download", "{ throw new exception( 'testDownload', 123 );}");
        $this->setRequestParameter('sorderfileid', "_testOrderFile");
        $oOrderFile = $this->getMock(\OxidEsales\Eshop\Application\Model\OrderFile::class, array('load', 'processOrderFile'));
        $oOrderFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oOrderFile->expects($this->any())->method('processOrderFile')->will($this->returnValue('_fileId'));
        oxTestModules::addModuleObject('oxOrderFile', $oOrderFile);

        $oFile = $this->getMock(\OxidEsales\Eshop\Application\Model\File::class, array('load', 'download'));
        $oFile->expects($this->any())->method('load')->will($this->returnValue(true));
        oxTestModules::addModuleObject('oxFile', $oFile);
        try {
            $oDownloads = oxNew('Download');
            ;
            $oDownloads->render();
        } catch (Exception $oEx) {
            $this->assertEquals(123, $oEx->getCode(), 'Error executing "testRenderWrongLink" test');

            return;
        }

        $this->fail('Redirect was not called');
    }

    /**
     * Test get article list.
     *
     * @return null
     */
    public function testRenderFileDoesnotExists()
    {
        oxTestModules::addFunction("oxUtils", "redirect", "{ throw new exception( 'testDownload', 123 );}");
        $this->setRequestParameter('sorderfileid', "_testOrderFile");
        $oOrderFile = $this->getMock(\OxidEsales\Eshop\Application\Model\OrderFile::class, array('load', 'processOrderFile'));
        $oOrderFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oOrderFile->expects($this->any())->method('processOrderFile')->will($this->returnValue('_fileId'));
        oxTestModules::addModuleObject('oxOrderFile', $oOrderFile);

        try {
            $oDownloads = oxNew('Download');
            $oDownloads->render();
        } catch (Exception $oEx) {
            $this->assertEquals(123, $oEx->getCode(), 'Error executing "testRenderWrongLink" test');

            return;
        }

        $this->fail('Redirect was not called');
    }

    /**
     * Test get article list.
     *
     * @return null
     */
    public function testRenderFailedDownloadingFile()
    {
        oxTestModules::addFunction("oxUtils", "redirect", "{ throw new exception( 'testDownload', 123 );}");
        $this->setRequestParameter('sorderfileid', "_testOrderFile");

        $oOrderFile = $this->getMock(\OxidEsales\Eshop\Application\Model\OrderFile::class, array('load', 'processOrderFile', 'getFileId'));
        $oOrderFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oOrderFile->expects($this->any())->method('processOrderFile')->will($this->returnValue('_fileId'));
        $oOrderFile->expects($this->any())->method('getFileId')->will($this->returnValue('_fileId'));
        oxTestModules::addModuleObject('oxOrderFile', $oOrderFile);

        $oFile = $this->getMock(\OxidEsales\Eshop\Application\Model\File::class, array('load', 'download', 'exist'));
        $oFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oFile->expects($this->any())->method('exist')->will($this->returnValue(true));
        $oFile->expects($this->any())->method('download')->will($this->throwException(oxNew('oxException')));
        oxTestModules::addModuleObject('oxFile', $oFile);

        $oException = oxNew('oxExceptionToDisplay');
        $oException->setMessage("ERROR_MESSAGE_FILE_DOWNLOAD_FAILED");
        $oUtilsView = $this->getMock(\OxidEsales\Eshop\Core\UtilsView::class, array('addErrorToDisplay'));
        $oUtilsView->expects($this->once())->method('addErrorToDisplay')->with($oException, false);
        oxTestModules::addModuleObject('oxUtilsView', $oUtilsView);

        try {
            $oDownloads = oxNew('Download');
            $oDownloads->render();
        } catch (Exception $oEx) {
            $this->assertEquals(123, $oEx->getCode(), 'Error executing "ERROR_MESSAGE_FILE_DOWNLOAD_FAILED" test');

            return;
        }

        $this->fail('Redirect was not called');
    }

    /**
     * Test get article list.
     *
     * @return null
     */
    public function testRenderDownloadExpired()
    {
        oxTestModules::addFunction("oxUtils", "redirect", "{ return;}");

        $this->setRequestParameter('sorderfileid', "_testOrderFile");

        $oOrderFile = $this->getMock(\OxidEsales\Eshop\Application\Model\OrderFile::class, array('load', 'processOrderFile', 'getFileId'));
        $oOrderFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oOrderFile->expects($this->any())->method('getFileId')->will($this->returnValue('_fileId'));
        $oOrderFile->expects($this->any())->method('processOrderFile')->will($this->returnValue(false));
        oxTestModules::addModuleObject('oxOrderFile', $oOrderFile);

        $oFile = $this->getMock(\OxidEsales\Eshop\Application\Model\File::class, array('load', 'download', 'exist'));
        $oFile->expects($this->any())->method('load')->will($this->returnValue(true));
        $oFile->expects($this->any())->method('exist')->will($this->returnValue(true));
        $oFile->expects($this->any())->method('download');
        oxTestModules::addModuleObject('oxFile', $oFile);

        $oException = oxNew('oxExceptionToDisplay');
        $oException->setMessage("ERROR_MESSAGE_FILE_DOESNOT_EXIST");
        $oUtilsView = $this->getMock(\OxidEsales\Eshop\Core\UtilsView::class, array('addErrorToDisplay'));
        $oUtilsView->expects($this->once())->method('addErrorToDisplay')->with($oException, false);
        oxTestModules::addModuleObject('oxUtilsView', $oUtilsView);

        $oDownloads = oxNew('Download');
        $oDownloads->render();
    }
}
