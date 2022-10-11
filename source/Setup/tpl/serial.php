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
<b><?php $this->getText('STEP_5_DESC'); ?></b><br>
<br>
<form action="index.php" method="post">
<input type="hidden" name="istep" value="<?php $this->getSetupStep('STEP_SERIAL_SAVE'); ?>">

<table cellpadding="0" cellspacing="5" border="0">
  <tr>
    <td>&nbsp;&nbsp;<input type="submit" id="step5Submit" class="edittext" value="<?php $this->getText('BUTTON_WRITE_LICENCE'); ?>"></td>
  </tr>
</table>
<br>
<input type="hidden" name="sid" value="<?php $this->getSid(); ?>">
</form>
<?php
$this->getText('STEP_5_LICENCE_DESC');
require "_footer.php";
