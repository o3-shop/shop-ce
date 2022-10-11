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
<br><br>
<?php
$this->getText('STEP_4_DESC');
$aPath = $this->getViewParam("aPath");
$aSetupConfig = $this->getViewParam("aSetupConfig");
$aAdminData   = $this->getViewParam("aAdminData");
$sChecked = "";
if (!isset($aSetupConfig['blDelSetupDir']) || $aSetupConfig['blDelSetupDir']) {
    $sChecked = "1";
} else {
    $sChecked = "0";
}
?><br>
<br>
<form action="index.php" method="post">
<input type="hidden" name="istep" value="<?php $this->getSetupStep('STEP_DIRS_WRITE'); ?>">
<input type="hidden" name="aSetupConfig[blDelSetupDir]" type="checkbox" value="<?php echo($sChecked) ?>">

<table cellpadding="0" cellspacing="5" border="0">
  <tr>
    <td><?php $this->getText('STEP_4_SHOP_URL'); ?>:</td>
    <td>&nbsp;&nbsp;<input size="40" name="aPath[sShopURL]" class="editinput" value="<?php echo($aPath['sShopURL']);?>"> </td>
  </tr>
  <tr>
    <td><?php $this->getText('STEP_4_SHOP_DIR'); ?>:</td>
    <td>&nbsp;&nbsp;<input size="40" name="aPath[sShopDir]" class="editinput" value="<?php echo($aPath['sShopDir']);?>"> </td>
  </tr>
  <tr>
    <td><?php $this->getText('STEP_4_SHOP_TMP_DIR'); ?>:</td>
    <td>&nbsp;&nbsp;<input size="40" name="aPath[sCompileDir]" class="editinput" value="<?php echo($aPath['sCompileDir']);?>"> </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td><?php $this->getText('STEP_4_ADMIN_LOGIN_NAME'); ?>:</td>
    <td>&nbsp;&nbsp;<input size="40" name="aAdminData[sLoginName]" class="editinput" value="<?php echo($aAdminData['sLoginName']);?>"> </td>
  </tr>
  <tr>
    <td><?php $this->getText('STEP_4_ADMIN_PASS'); ?>:</td>
    <td>&nbsp;&nbsp;<input size="40" name="aAdminData[sPassword]" class="editinput" type="password"> <?php $this->getText('STEP_4_ADMIN_PASS_MINCHARS'); ?></td>
  </tr>
  <tr>
    <td><?php $this->getText('STEP_4_ADMIN_PASS_CONFIRM'); ?>:</td>
    <td>&nbsp;&nbsp;<input size="40" name="aAdminData[sPasswordConfirm]" class="editinput" type="password"> </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
</table>
<input type="hidden" name="sid" value="<?php $this->getSid(); ?>">
<input type="submit" id="step4Submit" class="edittext" value="<?php $this->getText('BUTTON_WRITE_DATA'); ?>">
</form>
<?php require "_footer.php";
