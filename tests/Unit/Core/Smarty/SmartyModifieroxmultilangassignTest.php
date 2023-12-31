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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Smarty;

use \Smarty;
use \oxField;
use \oxRegistry;

$filePath = oxRegistry::getConfig()->getConfigParam('sShopDir') . 'Core/Smarty/Plugin/modifier.oxmultilangassign.php';
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    require_once dirname(__FILE__) . '/../../../../source/Core/Smarty/Plugin/modifier.oxmultilangassign.php';
}

class SmartyModifieroxmultilangassignTest extends \OxidTestCase
{

    /**
     * Provides data to testSimpleAssignments
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array('FIRST_NAME', 0, 'Vorname'),
            array('FIRST_NAME', 1, 'First name'),
            array('VAT', 1, 'VAT')

        );
    }

    /**
     * Tests simple assignments, where only translation is fetched
     *
     * @dataProvider provider
     */
    public function testSimpleAssignments($sIndent, $iLang, $sResult)
    {
        $this->setLanguage($iLang);
        $this->assertEquals($sResult, smarty_modifier_oxmultilangassign($sIndent));
    }

    /**
     * Provides data to testAssignmentsWithArguments
     *
     * @return array
     */
    public function withArgumentsProvider()
    {
        return array(
            array('MANUFACTURER_S', 0, 'Opel', '| Hersteller: Opel'),
            array('MANUFACTURER_S', 1, 'Opel', 'Manufacturer: Opel'),
            array('INVITE_TO_SHOP', 0, array('Admin', 'OXID Shop'), 'Eine Einladung von Admin OXID Shop zu besuchen.'),
            array('INVITE_TO_SHOP', 1, array('Admin', 'OXID Shop'), 'An invitation from Admin to visit OXID Shop')
        );
    }

    /**
     * Tests value assignments when translating strings containing %s
     *
     * @dataProvider withArgumentsProvider
     */
    public function testAssignmentsWithArguments($sIndent, $iLang, $aArgs, $sResult)
    {
        $this->setLanguage($iLang);
        $this->assertEquals($sResult, smarty_modifier_oxmultilangassign($sIndent, $aArgs));
    }

    /**
     * testTranslateFrontend_isMissingTranslation data provider
     *
     * @return array
     */
    public function missingTranslationProviderFrontend()
    {
        return array(
            array(
                true,
                'MY_MISING_TRANSLATION',
                'MY_MISING_TRANSLATION',
            ),
            array(
                false,
                'ident' => 'MY_MISING_TRANSLATION',
                'ERROR: Translation for MY_MISING_TRANSLATION not found!',
            ),
        );
    }

    /**
     * @dataProvider missingTranslationProviderFrontend
     */
    public function testTranslateFrontend_isMissingTranslation($isProductiveMode, $sIndent, $sTranslation)
    {
        $this->setAdminMode(false);
        $oSmarty = new Smarty();

        $this->setLanguage(1);

        $oShop = $this->getConfig()->getActiveShop();
        $oShop->oxshops__oxproductive = new oxField($isProductiveMode);
        $oShop->save();

        $this->assertEquals($sTranslation, smarty_modifier_oxmultilangassign($sIndent, $oSmarty));
    }

    /**
     * testTranslateAdmin_isMissingTranslation data provider
     *
     * @return array
     */
    public function missingTranslationProviderAdmin()
    {
        return array(
            array(
                'MY_MISING_TRANSLATION',
                'ERROR: Translation for MY_MISING_TRANSLATION not found!',
            ),
        );
    }

    /**
     * @dataProvider missingTranslationProviderAdmin
     */
    public function testTranslateAdmin_isMissingTranslation($sIdent, $sTranslation)
    {
        $oSmarty = new Smarty();

        $this->setLanguage(1);
        $this->setAdminMode(true);

        $this->assertEquals($sTranslation, smarty_modifier_oxmultilangassign($sIdent, $oSmarty));
    }
}
