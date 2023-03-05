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

require "_header.php";

// caching output
ob_flush();
require "_footer.php";
$sFooter = ob_get_contents();
ob_clean();

$this->getText('STEP_6_DESC');
$aPath = $this->getViewParam("aPath");
$aSetupConfig = $this->getViewParam("aSetupConfig");
$aDB = $this->getViewParam("aDB");
$blWritableConfig  = $this->getViewParam("blWritableConfig");
// This must be done here as it deletes setup and nothing can't be displayed after that.
$blRemoved = $this->isDeletedSetup($aSetupConfig, $aDB);
?>
<br><br>
<table cellspacing="5" cellpadding="5">
  <tr>
    <td><?php $this->getText('STEP_6_LINK_TO_SHOP'); ?>: </td>
    <td><a href="<?php echo($aPath['sShopURL']); ?>/" target="_blank" id="linkToShop" style="text-decoration: underline"><strong><?php $this->getText('STEP_6_TO_SHOP'); ?></strong></a></td>
  </tr>
  <tr>
    <td><?php $this->getText('STEP_6_LINK_TO_SHOP_ADMIN_AREA'); ?>: </td>
    <td><a href="<?php echo($aPath['sShopURL']); ?>/admin/" target="_blank" id="linkToAdmin" style="text-decoration: underline"><strong><?php $this->getText('STEP_6_TO_SHOP_ADMIN'); ?></strong></a></td>
  </tr>
</table>
<br>
<?php
//finalizing installation
if (!$blRemoved || $blWritableConfig) {
    ?><strong class="attention-title"><?php $this->getText('ATTENTION'); ?>:</strong><br><br><?php
}
if (!$blRemoved) {
    ?><strong class="attention-item"><?php $this->getText('SETUP_DIR_DELETE_NOTICE'); ?></strong><br><br><?php
}

if ($blWritableConfig) {
    ?><strong class="attention-item"><?php $this->getText('SETUP_CONFIG_PERMISSIONS'); ?></strong><br><?php
}
ob_flush();
echo $sFooter;
