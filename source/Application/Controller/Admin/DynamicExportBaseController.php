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
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use stdClass;

/**
 * Error constants
 */
DEFINE("ERR_SUCCESS", -2);
DEFINE("ERR_GENERAL", -1);
DEFINE("ERR_FILEIO", 1);

/**
 * DynExportBase framework class encapsulating a method for defining implementation class.
 * Performs export function according to user chosen categories.
 *
 * @subpackage dyn
 */
class DynamicExportBaseController extends AdminDetailsController
{
    /**
     * Export class name
     *
     * @var string
     */
    public $sClassDo = "";

    /**
     * Export ui class name
     *
     * @var string
     */
    public $sClassMain = "";

    /**
     * Export output folder
     *
     * @var string
     */
    public $sExportPath = "export/";

    /**
     * Export file extension
     *
     * @var string
     */
    public $sExportFileType = "txt";

    /**
     * Export file name
     *
     * @var string
     */
    public $sExportFileName = "dynexport";

    /**
     * Export file resource
     *
     * @var resource
     */
    public $fpFile = null;

    /**
     * Default number of records to export per tick
     * Used if not set in config
     *
     * @var int
     */
    public $iExportPerTick = 30;

    /**
     * Number of records to export per tick
     *
     * @var int
     */
    protected $_iExportPerTick = null;

    /**
     * Full export file path
     *
     * @var string
     */
    protected $_sFilePath = null;

    /**
     * Export result set
     *
     * @var array
     */
    protected $_aExportResultset = [];

    /**
     * View template name
     *
     * @var string
     */
    protected $_sThisTemplate = "dynexportbase.tpl";

    /**
     * Category data cache
     *
     * @var array
     */
    protected $_aCatLvlCache = null;

    /**
     * Calls parent constructor and initializes $this->_sFilePath parameter
     */
    public function __construct()
    {
        parent::__construct();

        // set generic frame template
        $this->_sFilePath = Registry::getConfig()->getConfigParam('sShopDir') . "/" . $this->sExportPath . $this->sExportFileName . "." . $this->sExportFileType;
    }

    /**
     * Calls parent rendering methods, sends implementation class names to template
     * and returns default template name
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        // assign all member variables to template
        $aClassVars = get_object_vars($this);
        foreach ($aClassVars as $name => $value) {
            $this->_aViewData[$name] = $value;
        }

        $this->_aViewData['sOutputFile'] = $this->_sFilePath;
        $this->_aViewData['sDownloadFile'] = Registry::getConfig()->getConfigParam('sShopURL') . $this->sExportPath . $this->sExportFileName . "." . $this->sExportFileType;

        return $this->_sThisTemplate;
    }

    /**
     * Prepares and fill all data which all the dyn exports needs
     */
    public function createMainExportView()
    {
        // parent category-tree
        $this->_aViewData["cattree"] = oxNew(\OxidEsales\Eshop\Application\Model\CategoryList::class);
        $this->_aViewData["cattree"]->loadList();

        $oLangObj = oxNew(Language::class);
        $aLangs = $oLangObj->getLanguageArray();
        foreach ($aLangs as $id => $language) {
            $language->selected = ($id == $this->_iEditLang);
            $this->_aViewData["aLangs"][$id] = clone $language;
        }
    }

    /**
     * Prepares Export
     */
    public function start()
    {
        // delete file, if it's already there
        $this->fpFile = @fopen($this->_sFilePath, "w");
        if (!isset($this->fpFile) || !$this->fpFile) {
            // we do have an error !
            $this->stop(ERR_FILEIO);
        } else {
            $this->_aViewData['refresh'] = 0;
            $this->_aViewData['iStart'] = 0;
            fclose($this->fpFile);

            // prepare it
            $iEnd = $this->prepareExport();
            Registry::getSession()->setVariable("iEnd", $iEnd);
            $this->_aViewData['iEnd'] = $iEnd;
        }
    }

    /**
     * Stops Export
     *
     * @param integer $iError error number
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function stop($iError = 0)
    {
        if ($iError) {
            $this->_aViewData['iError'] = $iError;
        }

        // delete temporary heap table
        DatabaseProvider::getDb()->execute("drop TABLE if exists " . $this->getHeapTableName());
    }

    /**
     * virtual function must be overloaded
     *
     * @param integer $iCnt counter
     *
     * @return bool
     */
    public function nextTick($iCnt)
    {
        return false;
    }

    /**
     * writes one line into open export file
     *
     * @param string $sLine exported line
     */
    public function write($sLine)
    {
        $sLine = $this->removeSID($sLine);
        $sLine = str_replace(["\r\n", "\n"], "", $sLine);
        fwrite($this->fpFile, $sLine . "\r\n");
    }

    /**
     * Does Export
     */
    public function run()
    {
        $blContinue = true;
        $iExportedItems = 0;

        $this->fpFile = @fopen($this->_sFilePath, "a");
        if (!isset($this->fpFile) || !$this->fpFile) {
            // we do have an error !
            $this->stop(ERR_FILEIO);
        } else {
            // file is open
            $iStart = Registry::getRequest()->getRequestEscapedParameter('iStart');
            // load from session
            $this->_aExportResultset = Registry::getRequest()->getRequestEscapedParameter('aExportResultset');
            $iExportPerTick = $this->getExportPerTick();
            for ($i = $iStart; $i < $iStart + $iExportPerTick; $i++) {
                if (($iExportedItems = $this->nextTick($i)) === false) {
                    // end reached
                    $this->stop(ERR_SUCCESS);
                    $blContinue = false;
                    break;
                }
            }
            if ($blContinue) {
                // make ticker continue
                $this->_aViewData['refresh'] = 0;
                $this->_aViewData['iStart'] = $i;
                $this->_aViewData['iExpItems'] = $iExportedItems;
            }
            fclose($this->fpFile);
        }
    }

    /**
     * Returns how many articles should be exported per tick
     *
     * @return int
     */
    public function getExportPerTick()
    {
        if ($this->_iExportPerTick === null) {
            $this->_iExportPerTick = (int) Registry::getConfig()->getConfigParam("iExportNrofLines");
            if (!$this->_iExportPerTick) {
                $this->_iExportPerTick = $this->iExportPerTick;
            }
        }

        return $this->_iExportPerTick;
    }

    /**
     * Sets how many articles should be exported per tick
     *
     * @param int $iCount articles count per tick
     */
    public function setExportPerTick($iCount)
    {
        $this->_iExportPerTick = $iCount;
    }

    /**
     * Removes Session ID from $sInput
     *
     * @param string $sInput Input to process
     *
     * @return null
     */
    public function removeSid($sInput)
    {
        $sSid = Registry::getSession()->getId();

        // remove sid from link
        $sOutput = str_replace("sid={$sSid}/", "", $sInput);
        $sOutput = str_replace("sid/{$sSid}/", "", $sOutput);
        $sOutput = str_replace("sid={$sSid}&amp;", "", $sOutput);
        $sOutput = str_replace("sid={$sSid}&", "", $sOutput);
        $sOutput = str_replace("sid={$sSid}", "", $sOutput);

        return $sOutput;
    }

    /**
     * Removes tags, shortens a string to $iMaxSize adding "..."
     *
     * @param string  $sInput          input to process
     * @param integer $iMaxSize        maximum output size
     * @param bool    $blRemoveNewline if true - \n and \r will be replaced by " "
     *
     * @return string
     */
    public function shrink($sInput, $iMaxSize, $blRemoveNewline = true)
    {
        if ($blRemoveNewline) {
            $sInput = str_replace("\r\n", " ", $sInput);
            $sInput = str_replace("\n", " ", $sInput);
        }

        $sInput = str_replace("\t", "    ", $sInput);

        // remove html entities, remove html tags
        $sInput = $this->unHTMLEntities(strip_tags($sInput));

        $oStr = Str::getStr();
        if ($oStr->strlen($sInput) > $iMaxSize - 3) {
            $sInput = $oStr->substr($sInput, 0, $iMaxSize - 5) . "...";
        }

        return $sInput;
    }

    /**
     * Loads all article parent categories and returns titles separated by "/"
     *
     * @param object $oArticle Article object
     * @param string $sSeparator separator (default "/")
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCategoryString($oArticle, $sSeparator = "/")
    {
        $sCatStr = '';

        $sLang = Registry::getLang()->getBaseLanguage();
        $oDB = DatabaseProvider::getDb();

        $sCatView = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories', $sLang);
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category', $sLang);

        //selecting category
        $sQ = "select $sCatView.oxleft, $sCatView.oxright, $sCatView.oxrootid from $sO2CView as oxobject2category left join $sCatView on $sCatView.oxid = oxobject2category.oxcatnid ";
        $sQ .= "where oxobject2category.oxobjectid = :oxobjectid and $sCatView.oxactive = 1 order by oxobject2category.oxtime ";

        $oRs = $oDB->select($sQ, [
            ':oxobjectid' => $oArticle->getId()
        ]);
        if ($oRs && $oRs->count() > 0) {
            $sLeft = $oRs->fields[0];
            $sRight = $oRs->fields[1];
            $sRootId = $oRs->fields[2];

            //selecting all parent category titles
            $sQ = "select oxtitle from $sCatView where oxright >= :oxright and oxleft <= :oxleft and oxrootid = :oxrootid order by oxleft ";

            $oRs = $oDB->select($sQ, [
                ':oxright' => $sRight,
                ':oxleft' => $sLeft,
                ':oxrootid' => $sRootId
            ]);
            if ($oRs && $oRs->count() > 0) {
                while (!$oRs->EOF) {
                    if ($sCatStr) {
                        $sCatStr .= $sSeparator;
                    }
                    $sCatStr .= $oRs->fields[0];
                    $oRs->fetchRow();
                }
            }
        }

        return $sCatStr;
    }

    /**
     * Loads article default category
     *
     * @param Article $oArticle Article object
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getDefaultCategoryString($oArticle)
    {
        $sLang = Registry::getLang()->getBaseLanguage();
        $oDB = DatabaseProvider::getDb();

        $sCatView = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories', $sLang);
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category', $sLang);

        //selecting category
        $sQ = "select $sCatView.oxtitle from $sO2CView as oxobject2category left join $sCatView on $sCatView.oxid = oxobject2category.oxcatnid ";
        $sQ .= "where oxobject2category.oxobjectid = :oxobjectid and $sCatView.oxactive = 1 order by oxobject2category.oxtime ";

        return $oDB->getOne($sQ, [
            ':oxobjectid' => $oArticle->getId()
        ]);
    }

    /**
     * Converts field for CSV
     *
     * @param string $sInput input to process
     *
     * @return string
     */
    public function prepareCSV($sInput)
    {
        $sInput = Registry::getUtilsString()->prepareCSVField($sInput);

        return str_replace(["&nbsp;", "&euro;", "|"], [" ", "", ""], $sInput);
    }

    /**
     * Changes special chars to be XML compatible
     *
     * @param string $sInput string which have to be changed
     *
     * @return string
     */
    public function prepareXML($sInput)
    {
        $sOutput = str_replace("&", "&amp;", $sInput);
        $sOutput = str_replace("\"", "&quot;", $sOutput);
        $sOutput = str_replace(">", "&gt;", $sOutput);
        $sOutput = str_replace("<", "&lt;", $sOutput);
        $sOutput = str_replace("'", "&apos;", $sOutput);

        return $sOutput;
    }

    /**
     * Searches for deepest path to a category this article is assigned to
     *
     * @param Article $oArticle article object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getDeepestCategoryPath($oArticle)
    {
        return $this->findDeepestCatPath($oArticle);
    }

    /**
     * create export resultset
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function prepareExport()
    {
        $oDB = DatabaseProvider::getDb();
        $sHeapTable = $this->getHeapTableName();

        // #1070 Saulius 2005.11.28
        // check mySQL version
        $oRs = $oDB->select("SHOW VARIABLES LIKE 'version'");
        $sTableCharset = $this->generateTableCharSet($oRs->fields[1]);

        // create heap table
        if (!($this->createHeapTable($sHeapTable, $sTableCharset))) {
            // error
            Registry::getUtils()->showMessageAndExit("Could not create HEAP Table {$sHeapTable}\n<br>");
        }

        $sCatAdd = $this->getCatAdd(Registry::getRequest()->getRequestEscapedParameter('acat'));
        if (!$this->insertArticles($sHeapTable, $sCatAdd)) {
            Registry::getUtils()->showMessageAndExit("Could not insert Articles in Table {$sHeapTable}\n<br>");
        }

        $this->removeParentArticles($sHeapTable);
        $this->setSessionParams();

        // get total cnt
        return $oDB->getOne("select count(*) from {$sHeapTable}");
    }

    /**
     * gets one oxid for exporting
     *
     * @param integer $iCnt counter
     * @param bool $blContinue false is used to stop exporting
     *
     * @return Article
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getOneArticle($iCnt, &$blContinue)
    {
        $myConfig = Registry::getConfig();

        //[Alfonsas 2006-05-31] setting specific parameter
        //to be checked in oxarticle.php init() method
        $myConfig->setConfigParam('blExport', true);
        $blContinue = false;

        if (($oArticle = $this->initArticle($this->getHeapTableName(), $iCnt, $blContinue))) {
            $blContinue = true;
            $oArticle = $this->setCampaignDetailLink($oArticle);
        }

        //[Alfonsas 2006-05-31] unsetting specific parameter
        //to be checked in oxarticle.php init() method
        $myConfig->setConfigParam('blExport', false);

        return $oArticle;
    }

    /**
     * Make sure that string is never empty.
     *
     * @param string $sInput   string that will be replaced
     * @param string $sReplace string that will replace
     *
     * @return string
     */
    public function assureContent($sInput, $sReplace = null)
    {
        $oStr = Str::getStr();
        if (!$oStr->strlen($sInput)) {
            if (!isset($sReplace) || !$oStr->strlen($sReplace)) {
                $sReplace = "-";
            }
            $sInput = $sReplace;
        }

        return $sInput;
    }

    /**
     * Replace HTML Entities
     * Replacement for html_entity_decode which is only available from PHP 4.3.0 onj
     *
     * @param string $sInput string to replace
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "unHtmlEntities" in next major
     */
    protected function _unHtmlEntities($sInput) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->unHtmlEntities($sInput);
    }

    /**
     * Replace HTML Entities
     * Replacement for html_entity_decode which is only available from PHP 4.3.0 onj
     *
     * @param string $sInput string to replace
     *
     * @return string
     */
    protected function unHtmlEntities($sInput)
    {
        $aTransTbl = array_flip(get_html_translation_table(HTML_ENTITIES));

        return strtr($sInput, $aTransTbl);
    }

    /**
     * Create valid Heap table name
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getHeapTableName" in next major
     */
    protected function _getHeapTableName() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getHeapTableName();
    }

    /**
     * Create valid Heap table name
     *
     * @return string
     */
    protected function getHeapTableName()
    {
        // table name must not start with any digit
        return "tmp_" . str_replace("0", "", md5(Registry::getSession()->getId()));
    }

    /**
     * generates table charset
     *
     * @param string $sMysqlVersion MySql version
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "generateTableCharSet" in next major
     */
    protected function _generateTableCharSet($sMysqlVersion) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->generateTableCharSet($sMysqlVersion);
    }

    /**
     * generates table charset
     *
     * @param string $sMysqlVersion MySql version
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function generateTableCharSet($sMysqlVersion)
    {
        $sTableCharset = "";

        //if MySQL >= 4.1.0 set charsets and collations
        if (version_compare($sMysqlVersion, '4.1.0', '>=') > 0) {
            $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            $oRs = $oDB->select("SHOW FULL COLUMNS FROM `oxarticles` WHERE field like 'OXID'");
            if (isset($oRs->fields['Collation']) && ($sMysqlCollation = $oRs->fields['Collation'])) {
                $oRs = $oDB->select("SHOW COLLATION LIKE '{$sMysqlCollation}'");
                if (isset($oRs->fields['Charset']) && ($sMysqlCharacterSet = $oRs->fields['Charset'])) {
                    $sTableCharset = "DEFAULT CHARACTER SET {$sMysqlCharacterSet} COLLATE {$sMysqlCollation}";
                }
            }
        }

        return $sTableCharset;
    }

    /**
     * creates heap-table
     *
     * @param string $sHeapTable table name
     * @param string $sTableCharset table charset
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "createHeapTable" in next major
     */
    protected function _createHeapTable($sHeapTable, $sTableCharset) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->createHeapTable($sHeapTable, $sTableCharset);
    }

    /**
     * creates heap-table
     *
     * @param string $sHeapTable table name
     * @param string $sTableCharset table charset
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function createHeapTable($sHeapTable, $sTableCharset)
    {
        $blDone = false;
        $oDB = DatabaseProvider::getDb();
        $sQ = "CREATE TABLE IF NOT EXISTS {$sHeapTable} ( `oxid` CHAR(32) NOT NULL default '' ) ENGINE=InnoDB {$sTableCharset}";
        if ($oDB->execute($sQ)) {
            $blDone = true;
            $oDB->execute("TRUNCATE TABLE {$sHeapTable}");
        }

        return $blDone;
    }

    /**
     * creates additional cat string
     *
     * @param array $aChosenCat Selected category array
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCatAdd" in next major
     */
    protected function _getCatAdd($aChosenCat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getCatAdd($aChosenCat);
    }

    /**
     * creates additional cat string
     *
     * @param array $aChosenCat Selected category array
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getCatAdd($aChosenCat)
    {
        $sCatAdd = null;
        if (is_array($aChosenCat) && count($aChosenCat)) {
            $oDB = DatabaseProvider::getDb();
            $sCatAdd = " and ( ";
            $blSep = false;
            foreach ($aChosenCat as $sCat) {
                if ($blSep) {
                    $sCatAdd .= " or ";
                }
                $sCatAdd .= "oxobject2category.oxcatnid = " . $oDB->quote($sCat);
                $blSep = true;
            }
            $sCatAdd .= ")";
        }

        return $sCatAdd;
    }

    /**
     * inserts articles into heap-table
     *
     * @param string $sHeapTable heap table name
     * @param string $sCatAdd category id filter (part of sql)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "insertArticles" in next major
     */
    protected function _insertArticles($sHeapTable, $sCatAdd) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->insertArticles($sHeapTable, $sCatAdd);
    }

    /**
     * inserts articles into heap-table
     *
     * @param string $sHeapTable heap table name
     * @param string $sCatAdd category id filter (part of sql)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function insertArticles($sHeapTable, $sCatAdd)
    {
        $oDB = DatabaseProvider::getDb();

        $iExpLang = Registry::getRequest()->getRequestEscapedParameter('iExportLanguage');
        if (!isset($iExpLang)) {
            $iExpLang = Registry::getSession()->getVariable("iExportLanguage");
        }

        $oArticle = oxNew(Article::class);
        $oArticle->setLanguage($iExpLang);

        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category', $iExpLang);
        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName("oxarticles", $iExpLang);

        $insertQuery = "insert into {$sHeapTable} select {$sArticleTable}.oxid from {$sArticleTable}, {$sO2CView} as oxobject2category where ";
        $insertQuery .= $oArticle->getSqlActiveSnippet();

        if (!Registry::getRequest()->getRequestEscapedParameter('blExportVars')) {
            $insertQuery .= " and {$sArticleTable}.oxid = oxobject2category.oxobjectid and {$sArticleTable}.oxparentid = '' ";
        } else {
            $insertQuery .= " and ( {$sArticleTable}.oxid = oxobject2category.oxobjectid or {$sArticleTable}.oxparentid = oxobject2category.oxobjectid ) ";
        }

        $sSearchString = Registry::getRequest()->getRequestEscapedParameter('search');
        if (isset($sSearchString)) {
            $insertQuery .= "and ( {$sArticleTable}.OXTITLE like " . $oDB->quote("%{$sSearchString}%");
            $insertQuery .= " or {$sArticleTable}.OXSHORTDESC like " . $oDB->quote("%$sSearchString%");
            $insertQuery .= " or {$sArticleTable}.oxsearchkeys like " . $oDB->quote("%$sSearchString%") . " ) ";
        }

        if ($sCatAdd) {
            $insertQuery .= $sCatAdd;
        }

        // add minimum stock value
        if (Registry::getConfig()->getConfigParam('blUseStock') && ($dMinStock = Registry::getRequest()->getRequestEscapedParameter('sExportMinStock'))) {
            $dMinStock = str_replace([";", " ", "/", "'"], "", $dMinStock);
            $insertQuery .= " and {$sArticleTable}.oxstock >= " . $oDB->quote($dMinStock);
        }

        $insertQuery .= " group by {$sArticleTable}.oxid";

        return (bool)$oDB->execute($insertQuery);
    }

    /**
     * removes parent articles so that we only have variants itself
     *
     * @param string $sHeapTable table name
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "removeParentArticles" in next major
     */
    protected function _removeParentArticles($sHeapTable) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->removeParentArticles($sHeapTable);
    }

    /**
     * removes parent articles so that we only have variants itself
     *
     * @param string $sHeapTable table name
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function removeParentArticles($sHeapTable)
    {
        if (!(Registry::getRequest()->getRequestEscapedParameter('blExportMainVars'))) {
            $oDB = DatabaseProvider::getDb();
            $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');

            // we need to remove again parent articles so that we only have the variants itself
            $sQ = "select $sHeapTable.oxid from $sHeapTable, $sArticleTable where
                          $sHeapTable.oxid = $sArticleTable.oxparentid group by $sHeapTable.oxid";

            $oRs = $oDB->select($sQ);
            $sDel = "delete from $sHeapTable where oxid in ( ";
            $blSep = false;
            if ($oRs && $oRs->count() > 0) {
                while (!$oRs->EOF) {
                    if ($blSep) {
                        $sDel .= ",";
                    }
                    $sDel .= $oDB->quote($oRs->fields[0]);
                    $blSep = true;
                    $oRs->fetchRow();
                }
            }
            $sDel .= " )";
            $oDB->execute($sDel);
        }
    }

    /**
     * stores some info in session
     * @deprecated underscore prefix violates PSR12, will be renamed to "setSessionParams" in next major
     */
    protected function _setSessionParams() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setSessionParams();
    }

    /**
     * stores some info in session
     */
    protected function setSessionParams()
    {
        // reset it from session
        Registry::getSession()->deleteVariable("sExportDelCost");
        $dDelCost = Registry::getRequest()->getRequestEscapedParameter('sExportDelCost');
        if (isset($dDelCost)) {
            $dDelCost = str_replace([";", " ", "/", "'"], "", $dDelCost);
            $dDelCost = str_replace(",", ".", $dDelCost);
            Registry::getSession()->setVariable("sExportDelCost", $dDelCost);
        }

        Registry::getSession()->deleteVariable("sExportMinPrice");
        $dMinPrice = Registry::getRequest()->getRequestEscapedParameter('sExportMinPrice');
        if (isset($dMinPrice)) {
            $dMinPrice = str_replace([";", " ", "/", "'"], "", $dMinPrice);
            $dMinPrice = str_replace(",", ".", $dMinPrice);
            Registry::getSession()->setVariable("sExportMinPrice", $dMinPrice);
        }

        // #827
        Registry::getSession()->deleteVariable("sExportCampaign");
        $sCampaign = Registry::getRequest()->getRequestEscapedParameter('sExportCampaign');
        if (isset($sCampaign)) {
            $sCampaign = str_replace([";", " ", "/", "'"], "", $sCampaign);
            Registry::getSession()->setVariable("sExportCampaign", $sCampaign);
        }

        // reset it from session
        Registry::getSession()->deleteVariable("blAppendCatToCampaign");
        // now retrieve it from get or post.
        $blAppendCatToCampaign = Registry::getRequest()->getRequestEscapedParameter('blAppendCatToCampaign');
        if ($blAppendCatToCampaign) {
            Registry::getSession()->setVariable("blAppendCatToCampaign", $blAppendCatToCampaign);
        }

        // reset it from session
        Registry::getSession()->deleteVariable("iExportLanguage");
        Registry::getSession()->setVariable("iExportLanguage", Registry::getRequest()->getRequestEscapedParameter('iExportLanguage'));

        //setting the custom header
        Registry::getSession()->setVariable("sExportCustomHeader", Registry::getRequest()->getRequestEscapedParameter('sExportCustomHeader'));
    }

    /**
     * Load all root cat's == all trees
     *
     * @return null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadRootCats" in next major
     */
    protected function _loadRootCats() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->loadRootCats();
    }

    /**
     * Load all root cat's == all trees
     *
     * @return null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function loadRootCats()
    {
        if ($this->_aCatLvlCache === null) {
            $this->_aCatLvlCache = [];

            $sCatView = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories');
            $oDb = DatabaseProvider::getDb();

            // Load all root cat's == all trees
            $sSQL = "select oxid from $sCatView where oxparentid = 'oxrootid'";
            $oRs = $oDb->select($sSQL);
            if ($oRs && $oRs->count() > 0) {
                while (!$oRs->EOF) {
                    // now load each tree
                    $sSQL = "SELECT s.oxid, s.oxtitle,
                             s.oxparentid, count( * ) AS LEVEL FROM $sCatView v,
                             $sCatView s WHERE s.oxrootid = :oxrootid and
                             v.oxrootid = :oxrootid and s.oxleft BETWEEN
                             v.oxleft AND v.oxright AND s.oxhidden = '0' GROUP BY s.oxleft order by level";

                    $oRs2 = $oDb->select($sSQL, [
                        ':oxrootid' => $oRs->fields[0]
                    ]);
                    if ($oRs2 && $oRs2->count() > 0) {
                        while (!$oRs2->EOF) {
                            // store it
                            $oCat = new stdClass();
                            $oCat->_sOXID = $oRs2->fields[0];
                            $oCat->oxtitle = $oRs2->fields[1];
                            $oCat->oxparentid = $oRs2->fields[2];
                            $oCat->ilevel = $oRs2->fields[3];
                            $this->_aCatLvlCache[$oCat->_sOXID] = $oCat;

                            $oRs2->fetchRow();
                        }
                    }
                    $oRs->fetchRow();
                }
            }
        }

        return $this->_aCatLvlCache;
    }

    /**
     * finds deepest category path
     *
     * @param Article $oArticle article object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "findDeepestCatPath" in next major
     */
    protected function _findDeepestCatPath($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->findDeepestCatPath($oArticle);
    }

    /**
     * finds deepest category path
     *
     * @param Article $oArticle article object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function findDeepestCatPath($oArticle)
    {
        $sRet = "";

        // find deepest
        $aIds = $oArticle->getCategoryIds();
        if (is_array($aIds) && count($aIds)) {
            if ($aCatLvlCache = $this->loadRootCats()) {
                $sIdMax = null;
                $dMaxLvl = 0;
                foreach ($aIds as $sCatId) {
                    if ($dMaxLvl < $aCatLvlCache[$sCatId]->ilevel) {
                        $dMaxLvl = $aCatLvlCache[$sCatId]->ilevel;
                        $sIdMax = $sCatId;
                        $sRet = $aCatLvlCache[$sCatId]->oxtitle;
                    }
                }

                // endless
                while (true) {
                    if (!isset($aCatLvlCache[$sIdMax]->oxparentid) || $aCatLvlCache[$sIdMax]->oxparentid == "oxrootid") {
                        break;
                    }
                    $sIdMax = $aCatLvlCache[$sIdMax]->oxparentid;
                    $sRet = $aCatLvlCache[$sIdMax]->oxtitle . "/" . $sRet;
                }
            }
        }

        return $sRet;
    }

    /**
     * initialize article
     *
     * @param string $sHeapTable heap table name
     * @param int $iCnt record number
     * @param bool $blContinue false is used to stop exporting
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     * @deprecated underscore prefix violates PSR12, will be renamed to "initArticle" in next major
     */
    protected function _initArticle($sHeapTable, $iCnt, &$blContinue) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->initArticle($sHeapTable, $iCnt, $blContinue);
    }

    /**
     * initialize article
     *
     * @param string $sHeapTable heap table name
     * @param int $iCnt record number
     * @param bool $blContinue false is used to stop exporting
     *
     * @return Article|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    protected function initArticle($sHeapTable, $iCnt, &$blContinue)
    {
        $oRs = $this->getDb()->selectLimit("select oxid from $sHeapTable", 1, $iCnt);
        if ($oRs && $oRs->count() > 0) {
            $oArticle = oxNew(Article::class);
            $oArticle->setLoadParentData(true);

            $oArticle->setLanguage(Registry::getSession()->getVariable("iExportLanguage"));

            if ($oArticle->load($oRs->fields[0])) {
                // if article exists, do not stop export
                $blContinue = true;
                // check price
                $dMinPrice = Registry::getRequest()->getRequestEscapedParameter('sExportMinPrice');
                if (!isset($dMinPrice) || ($oArticle->getPrice()->getBruttoPrice() >= $dMinPrice)) {
                    //Saulius: variant title added
                    $sTitle = $oArticle->oxarticles__oxvarselect->value ? " " . $oArticle->oxarticles__oxvarselect->value : "";
                    $oArticle->oxarticles__oxtitle->setValue($oArticle->oxarticles__oxtitle->value . $sTitle);

                    return $this->updateArticle($oArticle);
                }
            }
        }
    }

    /**
     * sets detail link for campaigns
     *
     * @param Article $oArticle article object
     *
     * @return Article
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "setCampaignDetailLink" in next major
     */
    protected function _setCampaignDetailLink($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->setCampaignDetailLink($oArticle);
    }

    /**
     * sets detail link for campaigns
     *
     * @param Article $oArticle article object
     *
     * @return Article
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function setCampaignDetailLink($oArticle)
    {
        // #827
        if ($sCampaign = Registry::getRequest()->getRequestEscapedParameter('sExportCampaign')) {
            // modify detail-link
            //#1166R - pangora - campaign
            $oArticle->appendLink("campaign={$sCampaign}");

            if (
                Registry::getRequest()->getRequestEscapedParameter('blAppendCatToCampaign') &&
                ($sCat = $this->getCategoryString($oArticle))
            ) {
                $oArticle->appendLink("/$sCat");
            }
        }

        return $oArticle;
    }

    /**
     * Returns view id ('dyn_interface')
     *
     * @return string
     */
    public function getViewId()
    {
        return 'dyn_interface';
    }

    /**
     * Updates Article object. Method is used for overriding.
     *
     * @param Article $article
     *
     * @return Article
     */
    protected function updateArticle($article)
    {
        return $article;
    }

    /**
     * Get the actual database.
     *
     * @return DatabaseInterface The database.
     * @throws DatabaseConnectionException
     */
    protected function getDb()
    {
        return DatabaseProvider::getDb();
    }
}
