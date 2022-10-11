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

require "_header.php"; ?>
<strong><?php $this->getText('STEP_1_DESC'); ?></strong><br>
<br>
<form action="index.php" method="post">
<table cellpadding="1" cellspacing="0">
    <tr>
        <td style="padding-top: 5px;"><?php $this->getText('SELECT_DELIVERY_COUNTRY'); ?>: </td>
        <td>
            <table cellpadding="0" cellspacing="0" border="0" height="29">
                <tr>
                    <td>
                        <select name="country_lang" style="font-size: 11px;">
                            <?php
                                $aCountries   = $this->getViewParam("aCountries");
                                $sLanguage   = $this->getViewParam("sLanguage");
                                $sCountryLang = $this->getViewParam("sCountryLang");

                            if (isset($aCountries[$sLanguage])) {
                                foreach ($aCountries[$sLanguage] as $sKey => $sValue) {
                                    $sSelected = ($sCountryLang !== null && $sCountryLang == $sKey) ? 'selected' : ''; ?><option value="<?php echo $sKey; ?>" <?php echo $sSelected; ?>><?php echo $sValue; ?></option><?php
                                }
                            }
                            ?>
                        </select>
                    </td>
                    <td style="padding: 0px 5px;">
                        <a href="#" class="helpicon" onmouseover="document.getElementById('countryHelpBox').style.display = 'block';" onmouseout="document.getElementById('countryHelpBox').style.display = 'none';">?</a>
                        <div id="countryHelpBox" class="helpbox">
                            <?php $this->getText('SELECT_DELIVERY_COUNTRY_HINT'); ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding-top: 5px;"><?php $this->getText('SELECT_SHOP_LANG'); ?>: </td>
        <td>
            <table cellpadding="0" cellspacing="0" border="0" height="29">
                <tr>
                    <td>
                        <select name="sShopLang" style="font-size: 11px;">
                            <?php
                            $aLanguages = $this->getViewParam("aLanguages");
                            foreach ($aLanguages as $sLangId => $sLangTitle) {
                                ?>
                                <option value="<?php echo $sLangId; ?>" <?php if ($this->getViewParam("sShopLang") == $sLangId) {
                                    echo 'selected';
                                               } ?>><?php echo $sLangTitle; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </td>
                    <td style="padding: 0px 5px;">
                        <a href="#" class="helpicon" onmouseover="document.getElementById('langHelpBox').style.display = 'block';" onmouseout="document.getElementById('langHelpBox').style.display = 'none';">?</a>
                        <div id="langHelpBox" class="helpbox">
                            <?php $this->getText('SELECT_SHOP_LANG_HINT'); ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <input type="hidden" name="sid" value="<?php $this->getSid(); ?>">
   </table>
    <br><br>
    <?php $this->getText('STEP_1_TEXT'); ?>
    <br><br>
    <?php $this->getText('STEP_1_ADDRESS'); ?>
    <br>
    <input type="hidden" name="istep" value="<?php $this->getSetupStep('STEP_LICENSE'); ?>">
    <input type="hidden" name="sid" value="<?php $this->getSid(); ?>">
    <input type="submit" id="step1Submit" class="edittext" value="<?php $this->getText('BUTTON_BEGIN_INSTALL'); ?>">
</form>
<?php require "_footer.php";
