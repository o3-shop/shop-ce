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
<textarea readonly="readonly" cols="180" rows="20" class="edittext" style="width: 98%; padding: 7px;"><?php echo $this->getViewParam("aLicenseText"); ?></textarea>
<form action="index.php" method="post">
  <input type="hidden" name="istep" value="<?php $this->getSetupStep('STEP_DB_INFO'); ?>">
  <input type="radio" name="iEula" value="1"><?php $this->getText('BUTTON_RADIO_LICENCE_ACCEPT'); ?><br>
  <input type="radio" name="iEula" value="0" checked><?php $this->getText('BUTTON_RADIO_LICENCE_NOT_ACCEPT'); ?><br><br>
  <input type="hidden" name="sid" value="<?php $this->getSid(); ?>">
  <input type="submit" id="step2Submit" class="edittext" value="<?php $this->getText('BUTTON_LICENCE'); ?>">
</form>
<?php require "_footer.php";
