<?php
// (C) egate media GmbH 2017
//
// The source contained in this file is the property of egate media
// (Lindentorstr. 22, 87700 Memmingen, Germany, http://www.egate-media.com).
//
// This software is protected by copyright law - it is NOT freeware.
//
// Any unauthorized use of this software is prohibited and will be
// prosecuted by civil and criminal law.

namespace OxidEsales\EshopCommunity\Application\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Error;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class adminNaviRights
{
    private $_oDom;

    public function getNavi()
    {
        $this->_oDom = new DOMDocument();
        $this->_oDom->appendChild(new DOMElement('OX'));

        if (is_array($aFilesToLoad = $this->_getMenuFiles())) {
            foreach ($aFilesToLoad as $sDynPath) {
                $this->_loadFromFile($sDynPath);
            }
        }
        return $this->_oDom->documentElement->childNodes;
    }

    protected function _getMenuFiles()
    {
        $aFilesToLoad = [];

        $sFullAdminDir = rtrim(Registry::getConfig()->getConfigParam('sShopDir'), '/').'/Application/views/admin';
        $sMenuFile = "/menu.xml";

        // including std file
        if (file_exists($sFullAdminDir . $sMenuFile)) {
            $aFilesToLoad[] = $sFullAdminDir . $sMenuFile;
        }

        // including custom file
        if (file_exists("$sFullAdminDir/user.xml")) {
            $aFilesToLoad[] = "$sFullAdminDir/user.xml";
        }

        // including module files
        $sSourceDir = rtrim(Registry::getConfig()->getConfigParam('sShopDir'), '/').'/modules';

        $aFilesToLoad = array_merge($aFilesToLoad, $this->checkDirs($sSourceDir));

        if (file_exists("$sFullAdminDir/user.xml")) {
            $aFilesToLoad[] = "$sFullAdminDir/user.xml";
        }

        return $aFilesToLoad;
    }

    private function checkDirs($sBaseDir, $depth = 0)
    {
        $aFilesToLoad = array();

        $handle = opendir($sBaseDir);
        while (false !== ($sFile = readdir($handle))) {
            if ($sFile != '.' && $sFile != '..') {
                $sDir = "$sBaseDir/$sFile";
                if (is_dir($sDir) && file_exists("$sDir/menu.xml")) {
                    $aFilesToLoad[] = "$sDir/menu.xml";
                } elseif (is_dir($sDir) && $depth < 1) {
                    $aFilesToLoad = array_merge($aFilesToLoad, $this->checkDirs($sDir, $depth + 1));
                }
            }
        }
        return $aFilesToLoad;
    }

    private function _loadFromFile($sMenuFile)
    {
        $oDomFile = new DomDocument();
        $oDomFile->preserveWhiteSpace = false;
        if (@$oDomFile->load($sMenuFile)) {
            $this->_merge($oDomFile);
        }
    }

    private function _mergeNodes($oDomElemTo, $oDomElemFrom, $oXPathTo, $oDomDocTo, $sQueryStart)
    {
        foreach ($oDomElemFrom->childNodes as $oFromNode) {
            if ($oFromNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }

            $sFromAttrName = $oFromNode->getAttribute('id');
            $sFromNodeName = $oFromNode->tagName;

            /* find current item */
            $sQuery = "{$sQueryStart}/{$sFromNodeName}[@id='{$sFromAttrName}']";
            $oCurNode = $oXPathTo->query($sQuery);

            /* if not found - append */
            if ($oCurNode->length == 0) {
                $oDomElemTo->appendChild($oDomDocTo->importNode($oFromNode, true));
                continue;
            }
            $oCurNode = $oCurNode->item(0);

            // if found copy all attributes and check childnodes
            $this->_copyAttributes($oCurNode, $oFromNode);

            if ($oFromNode->childNodes->length) {
                $this->_mergeNodes($oCurNode, $oFromNode, $oXPathTo, $oDomDocTo, $sQuery);
            }
        }
    }

    private function _merge($oDomNew)
    {
        $oXPath = new DOMXPath($this->_oDom);
        $this->_mergeNodes($this->_oDom->documentElement, $oDomNew->documentElement, $oXPath, $this->_oDom, '/OX');
    }

    protected function getSelectedElementsByUser(string $userId)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create();

        $queryBuilder->select('navigationid')
            ->from('emadminnavi')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'shopid',
                        $queryBuilder->createNamedParameter(Registry::getConfig()->getShopId())
                    ),
                    $queryBuilder->expr()->eq(
                        'userid',
                        $queryBuilder->createNamedParameter($userId)
                    )
                )
            );

        return array_map(
            function ($item) {
                return $item['navigationid'];
            }, $queryBuilder->execute()->fetchAllAssociative());
    }

    public function getNaviElements()
    {
        $selectedItems = $this->getSelectedElementsByUser(Registry::getRequest()->getRequestEscapedParameter('user'));
        return $this->getNaviElementsList($this->getNavi(), $selectedItems);
    }

    protected function getNaviElementsList($naviElements, $selectedItems, $parentSelected = false, $layer = 0)
    {
        $elements = [];
        $layer++;

        /** @var DOMElement $naviElement */
        foreach ($naviElements as $naviElement) {
            if ($naviElement->nodeType == XML_ELEMENT_NODE) {
                $element = [
                    'id'        => $naviElement->getAttribute('id'),
                    'name'      => $this->getElementName($naviElement),
                    'checked'   => in_array($naviElement->getAttribute('id'), $selectedItems) || $parentSelected,
                    'tagName'   => $naviElement->tagName,
                    'layer'     => $layer
                ];
                $elements[] = $element;

                if ($naviElement->childNodes->length) {
                    $elements = array_merge(
                        $elements,
                        $this->getNaviElementsList(
                            $naviElement->childNodes,
                            $selectedItems,
                            in_array($naviElement->getAttribute('id'), $selectedItems),
                            $layer
                        )
                    );
                }
            }
        }

        return $elements;
    }

    protected function getElementName(DOMElement $naviElement)
    {
        switch ($naviElement->tagName) {
            case 'BTN':
                $add = '';
                $name = $this->getButtonNames($naviElement->getAttribute('id'));
                break;
            default:
                $add = '';
                $name = $naviElement->getAttribute('id');
        }

        return $add. $name;
    }

    private function _copyAttributes($oDomElemTo, $oDomElemFrom)
    {
        foreach ($oDomElemFrom->attributes as $oAttr) {
            $oDomElemTo->setAttribute($oAttr->nodeName, $oAttr->nodeValue);
        }
    }

    public function getButtonNames($sId)
    {
        $translations = [
            'user_new'  => "TOOLTIPS_NEWUSER",
            'user_newremark'    => "TOOLTIPS_NEWREMARK",
            'user_newaddress'   => "TOOLTIPS_NEWADDRESS",
            'payment_new'       => "TOOLTIPS_NEWPAYMENT",
            'newsletter_new'    => "TOOLTIPS_NEWNEWSLETTER",
            'shop_new'          => "TOOLTIPS_NEWSHOP",
            'usergroup_new'     => "TOOLTIPS_NEWUSERGROUP",
            'category_new'      => "TOOLTIPS_NEWCATEGORY",
            'category_refresh'  => "TOOLTIPS_NEWCATTREE",
            'category_resetnrofarticles'    => "TOOLTIPS_RESETNROFARTICLESINCAT",
            'article_new'       => "TOOLTIPS_NEWARTICLE",
            'article_preview'   => "TOOLTIPS_ARTICLEREVIEW",
            'attribute_new'     => "TOOLTIPS_NEWITEMS",
            'statistic_new'     => "TOOLTIPS_NEWSTATISTIC",
            'selectlist_new'    => "TOOLTIPS_NEWSELECTLIST",
            'discount_new'      => "TOOLTIPS_NEWDISCOUNT",
            'delivery_new'      => "TOOLTIPS_NEWDELIVERY",
            'deliveryset_new'   => "TOOLTIPS_NEWDELIVERYSET",
            'vat_new'           => "TOOLTIPS_NEWMWST",
            'news_new'          => "TOOLTIPS_NEWNEWS",
            'links_new'         => "TOOLTIPS_NEWLINK",
            'voucher_new'       => "TOOLTIPS_NEWVOUCHER",
            'order_newremark'   => "TOOLTIPS_NEWREMARK",
            'country_new'       => "TOOLTIPS_NEWCOUNTRY",
            'language_new'      => "TOOLTIPS_NEWLANGUAGE",
            'vendor_new'        => "TOOLTIPS_NEWVENDOR",
            'vendor_resetnrofarticles'  => "TOOLTIPS_RESETNROFARTICLESINVND",
            'manufacturer_new'  => "TOOLTIPS_NEWMANUFACTURER",
            'manufacturer_resetnrofarticles'    => "TOOLTIPS_RESETNROFARTICLESINMAN",
            'wrapping_new'      => "TOOLTIPS_NEWWRAPPING",
            'content_new'       => "TOOLTIPS_NEWCONTENT",
            'actions_new'       => "TOOLTIPS_NEWPROMOTION"
        ];

        return $translations[$sId] ?: $sId;
    }

    public function getUser()
    {
        $aSettings = array();
        $sSql = "SELECT oxuser.OXID, oxuser.oxusername FROM oxuser WHERE oxuser.oxrights = 'malladmin'";
        $rs = mysqli_query($this->config->dbid, $sSql);
        while ($aUser = mysqli_fetch_array($rs)) {
            $aSettings[$aUser['OXID']] = $aUser['oxusername'];
        }
        return $aSettings;
    }

    public function getNaviSettings($sUser)
    {
        $aSettings = array();
        $sSql = "SELECT emvalue FROM emadminnavi WHERE emuser = '$sUser'";
        $rs = mysqli_query($this->config->dbid, $sSql);
        $iCount = 0;
        while ($aNavi = mysqli_fetch_array($rs)) {
            $aSettings[$iCount] = $aNavi['emvalue'];
            $iCount++;
        }
        return $aSettings;
    }

    public function setNaviSettings($aNaviSetting, $sUser)
    {
        $sSql = "DELETE FROM emadminnavi WHERE emuser = '$sUser'";
        mysqli_query($this->config->dbid, $sSql);

        for ($x = 0; $x < count($aNaviSetting); $x++) {
            $sVarName = "Navi" . $x . $sUser;
            $sVarValue = $aNaviSetting[$x];

            $sSql = "REPLACE INTO emadminnavi (emname, emvalue, emuser) VALUES ('$sVarName', '$sVarValue', '$sUser')";
            mysqli_query($this->config->dbid, $sSql);
        }
    }

    public function defaultValues()
    {
        $aSettings = array();

        return $aSettings;
    }

    /**
     * @param string $sSourceDir
     * @param mixed $aTmpLang
     *
     * @return array
     */
    protected function searchTranslationIn(string $sSourceDir, mixed $aTmpLang, $level = 0): array
    {
        $handle = opendir($sSourceDir);
        while (false !== ($sFile = readdir($handle))) {
            if ($sFile != '.' && $sFile != '..') {
                $sDir = "$sSourceDir/$sFile";
                if (is_dir($sDir)) {

                    // level <= 1 because vendor folder and possible Application folder
                    if (false === in_array($sFile, ['views', 'Application']) && $level <= 1) {
                        $aTmpLang = $this->searchTranslationIn($sDir, $aTmpLang, $level + 1);
                    }

                    $aModuleLang = [];

                    $sDir = $sDir . "/views/admin/de";
                    exec('find ' . $sDir . ' -name "*_lang.php"', $aModuleLang);

                    foreach ($aModuleLang as $iKey => $sModuleLang) {
                        $sContent = file_get_contents($sModuleLang);

                        // open array command, ionCube mark or Zend mark
                        if (preg_match('/\$aLang\s*=\s*(\[|array\()|ionCube\s+Loader|@Zend/m', $sContent)) {
                            try {
                                include_once($sModuleLang);
                            } catch (Error $e) {
                            } finally {
                                $aTmpLang = array_merge($aTmpLang, $aLang);
                            }
                        }
                    }
                }
            }
        }

        return $aTmpLang;
    }
}